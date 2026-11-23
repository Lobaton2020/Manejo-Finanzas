-- Diagnostico y arreglo del error MySQL 1449:
--   "The user specified as a definer ('user'@'host') does not exist"
--
-- Que pasa: las vistas (view_budget, view_temporal_budgets, investments_view)
-- se crearon en 2023 SIN clausula DEFINER explicita, asi que MySQL grabo en
-- la definicion de la vista el usuario que las creo en ese momento. Cuando la
-- base se restauro/migro o esa cuenta se borro, el definidor quedo huerfano y
-- CUALQUIER select sobre la vista revienta con 1449.
--
-- En la app esto se ve en /main: es la unica pantalla que consulta
-- view_budget (app/models/BudgetView.php -> "view_budget"). Tambien rompen
-- /budget (view_temporal_budgets) e /investment (investments_view).
--
-- ------------------------------------------------------------------
-- RIESGO PARA LOS DATOS: NINGUNO
-- ------------------------------------------------------------------
-- Las vistas NO guardan datos: son proyecciones (SELECT) sobre las tablas
-- reales (budget, outflows, users, investments, temporal_budgets).
-- CREATE OR REPLACE VIEW solo reemplaza la consulta almacenada; no escribe,
-- no borra y no migra ninguna fila de esas tablas.
--
-- Lo que SI hay que cuidar:
--   1. Correrlo con las MISMAS credenciales que usa la app (el Secret de
--      k8s "manejo-finanzas-secrets"). Asi el definidor nuevo es exactamente
--      la cuenta que ya existe y que la app usa. Con otra cuenta el
--      definidor queda de nuevo huerfano y el error vuelve.
--   2. Si cambiaste la clave de MySQL, actualiza el Secret y reinicia el
--      pod. Si no, la app se queda sin acceso a la base.
--   3. Haci el backup de estructura de abajo ANTES de aplicar. Es el
--      cinturon de seguridad: permite volver atras.
--
-- ------------------------------------------------------------------
-- 0) BACKUP (estructura, sin datos) — hacelo SIEMPRE antes de aplicar
-- ------------------------------------------------------------------
-- mysqldump -h <host> -u <usuario> -p --no-data --routines --triggers \
--            finanzas > finanzas_estructura_$(date +%Y%m%d_%H%M%S).sql
--
-- ------------------------------------------------------------------
-- 1) DIAGNOSTICO (solo lectura)
-- ------------------------------------------------------------------
SELECT TABLE_NAME, DEFINER, SECURITY_TYPE
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- Las cuentas que existen de verdad. Si el DEFINER de arriba no aparece
-- (salvo '*' o '%'), ese es el culpable.
SELECT user, host FROM mysql.user ORDER BY user, host;

-- ------------------------------------------------------------------
-- 2) ARREGLO
-- ------------------------------------------------------------------
-- Se toma la definicion EXACTA que ya tiene cada vista en
-- information_schema (que no incluye el DEFINER roto ni la cláusula de
-- seguridad) y se vuelve a crear con CREATE OR REPLACE, de modo que el
-- definidor pasa a ser el usuario de esta conexion.
--
-- Por defecto lo deja en SIMULACION (solo imprime el SQL). Para aplicar,
-- poné @do_execute = 1.
--
-- Modo simulacion: @do_execute = 0
-- Modo aplicar:     @do_execute = 1

SET @do_execute = 0;

DROP PROCEDURE IF EXISTS fix_view_definers;

DELIMITER $$
CREATE PROCEDURE fix_view_definers(IN do_execute TINYINT)
BEGIN
    DECLARE finished INT DEFAULT 0;
    DECLARE v_name    VARCHAR(64);
    DECLARE v_def    TEXT;
    DECLARE v_sec    VARCHAR(16);
    DECLARE v_sql    TEXT;
    DECLARE cur CURSOR FOR
        SELECT TABLE_NAME, VIEW_DEFINITION, IFNULL(SECURITY_TYPE, 'DEFINER')
        FROM information_schema.VIEWS
        WHERE TABLE_SCHEMA = DATABASE();
    -- Si una vista falla, se sigue con la siguiente y se reporta al final.
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = 1;

    DROP TEMPORARY TABLE IF EXISTS tmp_fix_views;
    CREATE TEMPORARY TABLE tmp_fix_views (
        view_name   VARCHAR(64) NOT NULL,
        old_definer VARCHAR(64) NOT NULL,
        new_definer VARCHAR(64) NOT NULL,
        state       VARCHAR(32) NOT NULL,
        detail      TEXT
    );

    OPEN cur;
    loop_views: LOOP
        FETCH cur INTO v_name, v_def, v_sec;
        IF finished = 1 THEN
            LEAVE loop_views;
        END IF;

        -- Se respeta el modo de seguridad original de cada vista.
        SET v_sql = CONCAT(
            'CREATE OR REPLACE SQL SECURITY ', v_sec, ' VIEW `', v_name, '` AS ', v_def
        );

        IF do_execute = 0 THEN
            INSERT INTO tmp_fix_views VALUES (v_name, '', CURRENT_USER(), 'simulado', 'cambio el SQL; no se ejecuto');
        ELSE
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    GET DIAGNOSTICS CONDITION 1
                        @err = MESSAGE_TEXT, @err_no = MYSQL_ERRNO;
                    INSERT INTO tmp_fix_views VALUES (v_name, '', CURRENT_USER(), 'ERROR', @err);
                END;

                SET @ddl = v_sql;
                PREPARE stmt FROM @ddl;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;

                INSERT INTO tmp_fix_views VALUES (v_name, '', CURRENT_USER(), 'ok', 'vista recreada');
            END;
        END IF;
    END LOOP;
    CLOSE cur;

    SELECT view_name, old_definer, new_definer, state, detail FROM tmp_fix_views ORDER BY view_name;
END$$
DELIMITER ;

CALL fix_view_definers(@do_execute);
DROP PROCEDURE IF EXISTS fix_view_definers;

-- ------------------------------------------------------------------
-- 3) VERIFICACION (solo lectura)
-- ------------------------------------------------------------------
-- Los definidores ahora deben coincidir con CURRENT_USER() (o ser una
-- cuenta que exista en mysql.user).
SELECT TABLE_NAME, DEFINER, SECURITY_TYPE
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

SELECT CURRENT_USER() AS conexion_actual;

-- Smoke test: leer las vistas (esto es lo que reventaba con 1449).
SELECT COUNT(*) FROM view_budget;
SELECT COUNT(*) FROM view_temporal_budgets;
SELECT COUNT(*) FROM investments_view;
