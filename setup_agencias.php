<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();

    echo "Iniciando configuración de módulo de Agencias...<br>";

    // 1. Crear tabla agencias
    $sqlCreate = "
    CREATE TABLE IF NOT EXISTS agencias (
        id_agencia INT AUTO_INCREMENT PRIMARY KEY,
        nombre_agencia VARCHAR(100) NOT NULL UNIQUE,
        direccion VARCHAR(255),
        ciudad VARCHAR(100),
        telefono_agencia VARCHAR(20),
        estado ENUM('Activa', 'Inactiva') DEFAULT 'Activa',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ";

    $db->exec($sqlCreate);
    echo "Tabla 'agencias' verificada/creada.<br>";

    // 2. Insertar dato inicial
    $sqlInsert = "
    INSERT IGNORE INTO agencias (id_agencia, nombre_agencia, direccion, estado) 
    VALUES (1, 'Sede Central', 'Dirección General', 'Activa');
    ";

    $db->exec($sqlInsert);
    echo "Datos iniciales insertados en 'agencias'.<br>";

    // 3. Establecer relación con colaboradores
    // Primero verificamos si ya existe la constraint para evitar errores
    $checkFk = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'colaboradores' 
        AND CONSTRAINT_NAME = 'fk_colaborador_agencia' 
        AND TABLE_SCHEMA = DATABASE()
    ");

    if ($checkFk->rowCount() == 0) {
        // Asegurarse de que la columna id_agencia tenga el tipo correcto y compatibilidad
        // Asumo que id_agencia ya existe en colaboradores crea por scripts anteriores, 
        // pero vamos a asegurarnos de que sea INT si no lo es. 
        // Nota: El script de creación de colaboradores ya lo incluía.

        $sqlAlter = "
        ALTER TABLE colaboradores
        ADD CONSTRAINT fk_colaborador_agencia FOREIGN KEY (id_agencia) 
        REFERENCES agencias(id_agencia) ON DELETE RESTRICT;
        ";

        $db->exec($sqlAlter);
        echo "FK 'fk_colaborador_agencia' creada en tabla 'colaboradores'.<br>";
    } else {
        echo "La FK 'fk_colaborador_agencia' ya existe.<br>";
    }

    echo "Configuración completada exitosamente.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
