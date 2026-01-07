<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    echo "Iniciando configuración de módulo de Tesorería...<br>";

    // 1. Crear tabla bancos
    $sqlBancos = "
    CREATE TABLE IF NOT EXISTS bancos (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nombre_banco VARCHAR(100) NOT NULL,
        numero_cuenta VARCHAR(50) UNIQUE NOT NULL,
        tipo_cuenta VARCHAR(50) DEFAULT 'Ahorro',
        moneda VARCHAR(10) DEFAULT 'HNL',
        saldo_actual DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sqlBancos);
    echo "Tabla 'bancos' verificada/creada.<br>";

    // 2. Crear tabla movimientos_bancarios
    $sqlMovimientos = "
    CREATE TABLE IF NOT EXISTS movimientos_bancarios (
        id INT PRIMARY KEY AUTO_INCREMENT,
        banco_id INT NOT NULL,
        tipo_transaccion ENUM('ingreso', 'egreso', 'traspaso_caja') NOT NULL,
        monto DECIMAL(15,2) NOT NULL,
        saldo_anterior DECIMAL(15,2) NOT NULL,
        saldo_nuevo DECIMAL(15,2) NOT NULL,
        referencia VARCHAR(100) NULL,
        descripcion TEXT NULL,
        realizado_por INT NOT NULL,
        entidad_destino_tipo ENUM('usuario', 'agencia', 'banco', 'externo') NULL,
        entidad_destino_id INT NULL,
        fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (banco_id) REFERENCES bancos(id) ON DELETE CASCADE,
        FOREIGN KEY (realizado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
        INDEX idx_banco (banco_id),
        INDEX idx_fecha (fecha_hora)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sqlMovimientos);
    echo "Tabla 'movimientos_bancarios' verificada/creada.<br>";

    // 3. Alterar usuarios
    // Check if column exists
    $checkColUser = $db->query("SHOW COLUMNS FROM usuarios LIKE 'saldo_caja_virtual'");
    if ($checkColUser->rowCount() == 0) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN saldo_caja_virtual DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        echo "Columna 'saldo_caja_virtual' agregada a 'usuarios'.<br>";
    } else {
        echo "Columna 'saldo_caja_virtual' ya existe en 'usuarios'.<br>";
    }

    // 4. Alterar agencias
    // Check if column exists
    $checkColAgency = $db->query("SHOW COLUMNS FROM agencias LIKE 'saldo_caja'");
    if ($checkColAgency->rowCount() == 0) {
        $db->exec("ALTER TABLE agencias ADD COLUMN saldo_caja DECIMAL(15,2) NOT NULL DEFAULT 0.00");
        echo "Columna 'saldo_caja' agregada a 'agencias'.<br>";
    } else {
        echo "Columna 'saldo_caja' ya existe en 'agencias'.<br>";
    }

    echo "Configuración de Tesorería completada exitosamente.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
