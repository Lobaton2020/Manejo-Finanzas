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
-- view_budget (app/models/BudgetView.php -> "view_budget").
--
-- COMO CORRERLO: conectate a la MISMA base que usa el pod, con las MISMAS
-- credenciales que usa la app (el secret de k8s "manejo-finanzas-secrets").
-- El definidor nuevo va a quedar como ese usuario, que es el que realmente
-- existe. Correrlo con otro usuario solo cambia de problema.
--
--   mysql -h <host> -u <usuario> -p <base> < scripts/fix_view_definers.sql
--
-- ------------------------------------------------------------------
-- 1) DIAGNOSTICO (solo lectura)
-- ------------------------------------------------------------------
SELECT TABLE_NAME, DEFINER, SECURITY_TYPE
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- Las cuentas que existen de verdad. Si el DEFINER de arriba no aparece aqui
-- (salvo '*' o '%'), ese es el culpable.
SELECT user, host FROM mysql.user ORDER BY user, host;

-- ------------------------------------------------------------------
-- 2) ARREGLO (DDL): recrea las vistas con el definidor actual
-- ------------------------------------------------------------------
-- Se toma la definicion EXACTA que ya tiene cada vista en
-- information_schema (que no incluye el DEFINER roto) y se vuelve a crear con
-- CREATE OR REPLACE, de modo que el definidor pasa a ser el usuario de esta
-- conexion (que existe). Las columnas y los datos no cambian.
--
-- Si preferis hacerlo a mano, el equivalente por vista es:
--   CREATE OR REPLACE SQL SECURITY DEFINER VIEW view_budget AS <definicion>;
-- taking la definicion de: SELECT VIEW_DEFINITION FROM information_schema.VIEWS
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'view_budget';

DROP PROCEDURE IF EXISTS fix_view_definers;

DELIMITER $$
CREATE PROCEDURE fix_view_definers()
BEGIN
    DECLARE finished INT DEFAULT 0;
    DECLARE v_name  VARCHAR(64);
    DECLARE v_def  TEXT;
    DECLARE cur CURSOR FOR
        SELECT TABLE_NAME, VIEW_DEFINITION
        FROM information_schema.VIEWS
        WHERE TABLE_SCHEMA = DATABASE();
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = 1;

    OPEN cur;
    loop_views: LOOP
        FETCH cur INTO v_name, v_def;
        IF finished = 1 THEN
            LEAVE loop_views;
        END IF;

        SET @ddl = CONCAT('CREATE OR REPLACE SQL SECURITY DEFINER VIEW `', v_name, '` AS ', v_def);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur;
END$$
DELIMITER ;

CALL fix_view_definers();
DROP PROCEDURE IF EXISTS fix_view_definers;

-- ------------------------------------------------------------------
-- 3) VERIFICACION: los definidores ahora deben coincidir con CURRENT_USER()
-- ------------------------------------------------------------------
SELECT TABLE_NAME, DEFINER, SECURITY_TYPE
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

SELECT CURRENT_USER() AS conexion_actual;

-- Smoke test: leer las vistas (esto es lo que reventaba con 1449).
SELECT COUNT(*) FROM view_budget;
