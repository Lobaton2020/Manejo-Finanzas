<?php
/**
 * Restaura la cuenta root@% y deja las vistas legibles, usando la CONFIG
 * INTERNA de la app (el .env), sin sudo ni SSH y sin borrar nada.
 *
 * Que arregla:
 *   - Las 9 vistas de las bases (3 en finanzas, 6 en la copia de tomanotas)
 *     tienen DEFINER=root@% y esa cuenta no existe: por eso /main,
 *     /budget e /investment tiran 1449 y leer cualquier vista da
 *     "Access denied".
 *   - El .env de la app dice DB_USER=root, asi que con la cuenta viva la
 *     app vuelve a entrar y las vistas vuelven a leerse.
 *
 * Que NO hace: no toca ninguna tabla, no borra ninguna vista, no hace DROP.
 * CREATE USER IF NOT EXISTS + ALTER USER + GRANT, nada mas.
 *
 * Uso:
 *   php scripts/fix_root_password.php              # solo diagnostico (default)
 *   php scripts/fix_root_password.php --apply      # aplica el arreglo
 *   php scripts/fix_root_password.php --apply --env /ruta/al/prod.env
 *
 * El --env es para cuando la app corre en otro servidor: se le pasa el .env
 * que usa el pod (el del Secret de k8s), que es la unica config que entra.
 *
 * Conexion: usa PDO/mysqli si estan disponibles; si no, cae al cliente
 * `mariadb`/`mysql` de la linea de comandos.
 */

if (PHP_SAPI !== "cli") {
    exit("Este script es solo de linea de comandos.\n");
}

$root = dirname(__DIR__);

$apply = in_array("--apply", $argv, true);
$envPath = $root . "/.env";
foreach ($argv as $i => $arg) {
    if ($arg === "--env" && isset($argv[$i + 1])) {
        $envPath = $argv[$i + 1];
    }
}

/** Lee un .env igual que app/config/config.php. */
function readEnv(string $path): array
{
    if (!is_file($path)) {
        fwrite(STDERR, "No existe el .env: {$path}\n");
        exit(1);
    }

    $out = [];
    foreach (explode("\n", (string) file_get_contents($path)) as $line) {
        $line = trim($line);
        if ($line === "" || strpos($line, "#") === 0 || strpos($line, "=") === false) {
            continue;
        }
        $parts = explode("=", $line, 2);
        $out[trim($parts[0])] = trim($parts[1]);
    }

    return $out;
}

/** Escapa un valor para meterlo dentro de comillas simples en SQL. */
function sqlQuote(string $value): string
{
    return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $value) . "'";
}

/**
 * Correlacion de SQL. Usa PDO si hay driver de mysql; si no, el cliente de
 * linea de comandos (mariadb/mysql).
 */
class Db
{
    private $pdo = null;
    private $cnfFile = null;
    private $binary = null;

    public function __construct(string $host, string $user, string $pass, string $db)
    {
        if (in_array("mysql", PDO::getAvailableDrivers(), true)) {
            $this->pdo = new PDO(
                "mysql:host={$host};dbname={$db};charset=utf8mb4",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            return;
        }

        foreach (["mariadb", "mysql"] as $bin) {
            $path = trim((string) shell_exec("command -v {$bin} 2>/dev/null"));
            if ($path !== "") {
                $this->binary = $path;
                break;
            }
        }
        if ($this->binary === null) {
            throw new RuntimeException("No hay pdo_mysql ni cliente mariadb/mysql en esta maquina.");
        }

        $this->cnfFile = tempnam(sys_get_temp_dir(), "dbconf");
        file_put_contents($this->cnfFile, "[client]\nhost={$host}\nuser={$user}\npassword={$pass}\ndatabase={$db}\n");
        chmod($this->cnfFile, 0600);
    }

    /** @return array<int, array<int, string>> */
    public function rows(string $sql): array
    {
        if ($this->pdo !== null) {
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_NUM);
        }

        $out = $this->exec($sql);
        if ($out["code"] !== 0) {
            throw new RuntimeException($out["err"]);
        }

        $rows = [];
        foreach (explode("\n", trim($out["out"])) as $line) {
            if ($line === "") {
                continue;
            }
            $rows[] = explode("\t", $line);
        }

        return $rows;
    }

    /** @return array{code: int, out: string, err: string} */
    public function exec(string $sql): array
    {
        if ($this->pdo !== null) {
            try {
                $this->pdo->exec($sql);
                return ["code" => 0, "out" => "", "err" => ""];
            } catch (Throwable $e) {
                return ["code" => 1, "out" => "", "err" => $e->getMessage()];
            }
        }

        $cmd = escapeshellcmd($this->binary)
            . " --defaults-extra-file=" . escapeshellarg($this->cnfFile)
            . " --batch --skip-column-names -e " . escapeshellarg($sql) . " 2>&1";

        $descriptors = [1 => ["pipe", "w"], 2 => ["pipe", "w"]];
        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            return ["code" => 1, "out" => "", "err" => "no se pudo ejecutar el cliente mysql"];
        }

        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        return ["code" => $code, "out" => $out, "err" => $err !== "" ? $err : $out];
    }

    public function close(): void
    {
        if ($this->cnfFile !== null && is_file($this->cnfFile)) {
            @unlink($this->cnfFile);
        }
    }
}

$env = readEnv($envPath);

$host = $env["DB_HOST"] ?? "";
$name = $env["DB_NAME"] ?? "";
$user = $env["DB_USER"] ?? "";
$pass = $env["DB_PASWORD"] ?? ($env["DB_PASSWORD"] ?? "");

echo "== Config interna usada ==\n";
echo "  .env    : {$envPath}\n";
echo "  host    : {$host}\n";
echo "  base    : {$name}\n";
echo "  usuario : {$user}\n";
echo "  clave   : " . (strlen($pass) ? "si (" . strlen($pass) . " chars)" : "NO") . "\n";
echo "  modo    : " . ($apply ? "APLICAR" : "solo diagnostico") . "\n\n";

try {
    $db = new Db($host, $user, $pass, $name);
} catch (Throwable $e) {
    echo "!! " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $row = $db->rows("SELECT USER(), CURRENT_USER(), @@hostname, VERSION()");
    echo "== Conexion ==\n";
    echo "  usuario de la sesion : {$row[0][0]}\n";
    echo "  cuenta efectiva      : {$row[0][1]}\n";
    echo "  servidor             : {$row[0][2]}\n";
    echo "  motor                : {$row[0][3]}\n\n";

    echo "== Vistas y sus definidores ==\n";
    $views = $db->rows(
        "SELECT TABLE_SCHEMA, TABLE_NAME, DEFINER, SECURITY_TYPE FROM information_schema.VIEWS "
        . "WHERE TABLE_SCHEMA NOT IN ('information_schema','mysql','performance_schema') "
        . "ORDER BY TABLE_SCHEMA, TABLE_NAME"
    );
    foreach ($views as $v) {
        echo "  {$v[0]}.{$v[1]}  definer={$v[2]}  security={$v[3]}\n";
    }
    if (empty($views)) {
        echo "  (no se ven vistas: el usuario no tiene privilegios sobre ellas)\n";
    }
    echo "\n";

    echo "== Cuentas existentes (si el usuario las puede leer) ==\n";
    try {
        $users = $db->rows("SELECT user, host FROM mysql.user ORDER BY user, host");
        foreach ($users as $u) {
            echo "  {$u[0]}@{$u[1]}\n";
        }
        if (empty($users)) {
            echo "  (mysql.user vacio o sin permiso)\n";
        }
    } catch (Throwable $e) {
        echo "  (no se puede leer mysql.user con este usuario)\n";
    }
    echo "\n";

    if (!$apply) {
        echo "Solo diagnostico. Para aplicar: php scripts/fix_root_password.php --apply\n";
        exit(0);
    }

    if ($user === "" || strlen($pass) < 8) {
        echo "!! El .env no trae un usuario/clave utilizables. Abortando.\n";
        exit(1);
    }

    echo "== Aplicando (no borra nada) ==\n";
    $quotedUser = sqlQuote($user);
    $quotedPass = sqlQuote($pass);
    $statements = [
        "CREATE USER IF NOT EXISTS {$quotedUser}@'%' IDENTIFIED BY {$quotedPass}",
        "ALTER USER {$quotedUser}@'%' IDENTIFIED BY {$quotedPass}",
        "GRANT ALL PRIVILEGES ON `finanzas`.*  TO {$quotedUser}@'%'",
        "GRANT ALL PRIVILEGES ON `tomanotas`.* TO {$quotedUser}@'%'",
        "FLUSH PRIVILEGES",
    ];
    foreach ($statements as $sql) {
        $res = $db->exec($sql);
        echo "  " . ($res["code"] === 0 ? "OK  : " : "!!  : ")
            . preg_replace("/\s+/", " ", $sql) . "\n";
        if ($res["code"] !== 0) {
            echo "        -> " . trim($res["err"]) . "\n";
        }
    }

    echo "\n== Verificacion: definidores ==\n";
    foreach ($db->rows(
        "SELECT TABLE_SCHEMA, TABLE_NAME, DEFINER FROM information_schema.VIEWS "
        . "WHERE TABLE_SCHEMA IN ('finanzas','tomanotas') ORDER BY TABLE_SCHEMA, TABLE_NAME"
    ) as $v) {
        echo "  {$v[0]}.{$v[1]}  definer={$v[2]}\n";
    }

    echo "\n== Verificacion: lectura de vistas (aca fallaba antes) ==\n";
    foreach (["finanzas.view_budget", "finanzas.view_temporal_budgets", "finanzas.investments_view"] as $view) {
        $res = $db->exec("SELECT COUNT(*) FROM {$view}");
        echo "  {$view} -> " . ($res["code"] === 0 ? trim($res["out"]) : "ERROR: " . trim($res["err"])) . "\n";
    }
} catch (Throwable $e) {
    echo "!! Error: " . $e->getMessage() . "\n";
} finally {
    $db->close();
}
