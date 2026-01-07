<?php
// create_ingresos_bancos_agencia.php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

$sql = "
CREATE TABLE IF NOT EXISTS ingresos_bancos_agencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    banco_id INT NOT NULL,
    agencia_id INT NOT NULL,
    monto DECIMAL(15, 2) NOT NULL,
    referencia VARCHAR(255),
    saldo_anterior_banco DECIMAL(15, 2),
    saldo_nuevo_banco DECIMAL(15, 2),
    saldo_anterior_agencia DECIMAL(15, 2),
    saldo_nuevo_agencia DECIMAL(15, 2),
    realizado_por INT NOT NULL,
    observaciones TEXT,
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (banco_id) REFERENCES bancos(id),
    FOREIGN KEY (agencia_id) REFERENCES agencias(id_agencia),
    FOREIGN KEY (realizado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $db->exec($sql);
    echo "Table ingresos_bancos_agencia created successfully or already exists.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
