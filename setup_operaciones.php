<?php
/**
 * Setup: Agregar estado 'aprobado' a préstamos
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    echo "Actualizando tabla de préstamos...<br>";

    // Modificar el ENUM de estado para incluir 'aprobado'
    $sql = "ALTER TABLE prestamos 
            MODIFY COLUMN estado ENUM('pendiente', 'aprobado', 'activo', 'completado', 'cancelado', 'en_mora') 
            NOT NULL DEFAULT 'pendiente'";

    $db->exec($sql);
    echo "Estado 'aprobado' agregado exitosamente a la tabla préstamos.<br>";

    // Agregar columna id_agencia si no existe
    $checkCol = $db->query("SHOW COLUMNS FROM prestamos LIKE 'id_agencia'");
    if ($checkCol->rowCount() == 0) {
        $db->exec("ALTER TABLE prestamos ADD COLUMN id_agencia INT NULL AFTER cliente_id");
        $db->exec("ALTER TABLE prestamos ADD FOREIGN KEY (id_agencia) REFERENCES agencias(id_agencia) ON DELETE SET NULL");
        $db->exec("ALTER TABLE prestamos ADD INDEX idx_agencia (id_agencia)");
        echo "Columna 'id_agencia' agregada a 'prestamos'.<br>";
    } else {
        echo "Columna 'id_agencia' ya existe en 'prestamos'.<br>";
    }

    // Agregar columna id_agencia a clientes si no existe
    $checkColCliente = $db->query("SHOW COLUMNS FROM clientes LIKE 'id_agencia'");
    if ($checkColCliente->rowCount() == 0) {
        $db->exec("ALTER TABLE clientes ADD COLUMN id_agencia INT NULL AFTER cobrador_id");
        $db->exec("ALTER TABLE clientes ADD FOREIGN KEY (id_agencia) REFERENCES agencias(id_agencia) ON DELETE SET NULL");
        $db->exec("ALTER TABLE clientes ADD INDEX idx_agencia_cliente (id_agencia)");
        echo "Columna 'id_agencia' agregada a 'clientes'.<br>";
    } else {
        echo "Columna 'id_agencia' ya existe en 'clientes'.<br>";
    }

    echo "<br><strong>Configuración completada exitosamente.</strong>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
