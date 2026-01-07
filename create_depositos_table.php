<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    $sql = "
    CREATE TABLE IF NOT EXISTS depositos_bancos_agencia (
        id INT AUTO_INCREMENT PRIMARY KEY,
        banco_id INT NOT NULL,
        agencia_id INT NOT NULL,
        monto DECIMAL(15,2) NOT NULL,
        referencia VARCHAR(100),
        saldo_anterior_banco DECIMAL(15,2),
        saldo_nuevo_banco DECIMAL(15,2),
        saldo_anterior_agencia DECIMAL(15,2),
        saldo_nuevo_agencia DECIMAL(15,2),
        realizado_por INT NOT NULL,
        observaciones TEXT,
        fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (banco_id) REFERENCES bancos(id),
        FOREIGN KEY (agencia_id) REFERENCES agencias(id),
        FOREIGN KEY (realizado_por) REFERENCES usuarios(id_usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $db->exec($sql);
    echo "Table 'depositos_bancos_agencia' created or already exists.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>