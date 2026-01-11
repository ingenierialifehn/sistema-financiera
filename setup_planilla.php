<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();

    // 1. Add sueldo_base column to usuarios if it doesn't exist
    $stmt = $db->query("SHOW COLUMNS FROM usuarios LIKE 'sueldo_base'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN sueldo_base DECIMAL(10,2) DEFAULT 0.00 AFTER email");
        echo "Column 'sueldo_base' added to 'usuarios'.<br>";
    } else {
        echo "Column 'sueldo_base' already exists in 'usuarios'.<br>";
    }

    // 2. Create config_planilla table
    $db->exec("CREATE TABLE IF NOT EXISTS config_planilla (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sueldo_base_general DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        minimo_clientes INT NOT NULL DEFAULT 60,
        minimo_normalidad DECIMAL(5,2) NOT NULL DEFAULT 92.00,
        tramos_comision JSON,
        escaladores_normalidad JSON,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table 'config_planilla' created/verified.<br>";

    // Initialize config if empty
    $stmt = $db->query("SELECT count(*) FROM config_planilla");
    if ($stmt->fetchColumn() == 0) {
        $defaultTramos = json_encode([
            ['min' => 0, 'max' => 399999, 'comision' => 0],
            ['min' => 400000, 'max' => 499999, 'comision' => 1000],
            ['min' => 500000, 'max' => 749999, 'comision' => 2300],
            ['min' => 750000, 'max' => 999999999, 'comision' => 3500]
        ]);
        $defaultEscaladores = json_encode([
            ['min' => 92, 'max' => 95, 'porcentaje' => 50],
            ['min' => 95, 'max' => 98, 'porcentaje' => 75],
            ['min' => 98, 'max' => 101, 'porcentaje' => 100]
        ]);

        $insert = $db->prepare("INSERT INTO config_planilla (sueldo_base_general, tramos_comision, escaladores_normalidad) VALUES (0, ?, ?)");
        $insert->execute([$defaultTramos, $defaultEscaladores]);
        echo "Default configuration inserted.<br>";
    }

    // 3. Create planilla_encabezado table
    $db->exec("CREATE TABLE IF NOT EXISTS planilla_encabezado (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mes_correspondiente DATE NOT NULL,
        fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        total_pagado DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        estado ENUM('borrador', 'confirmado') DEFAULT 'borrador',
        id_usuario_generador INT,
        observaciones TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table 'planilla_encabezado' created/verified.<br>";

    // 4. Create planilla_detalle table
    $db->exec("CREATE TABLE IF NOT EXISTS planilla_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        planilla_id INT NOT NULL,
        id_asesor INT NOT NULL,
        saldo_cartera DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        normalidad DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        clientes_activos INT NOT NULL DEFAULT 0,
        sueldo_base DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        comision_calculada DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        gastos_campo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_pagar DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        detalles_calculo JSON,
        FOREIGN KEY (planilla_id) REFERENCES planilla_encabezado(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table 'planilla_detalle' created/verified.<br>";

    echo "Database setup completed successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
