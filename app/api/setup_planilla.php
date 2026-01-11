<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    echo "Iniciando instalación del Módulo de Planillas...\n";

    // 1. Tabla Configuración General
    $sqlConfig = "CREATE TABLE IF NOT EXISTS config_planilla (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sueldo_base_general DECIMAL(10,2) DEFAULT 0.00,
        minimo_clientes INT DEFAULT 60,
        minimo_normalidad DECIMAL(5,2) DEFAULT 92.00,
        tramos_comision TEXT, -- JSON con rangos y montos
        escaladores_normalidad TEXT, -- JSON con rangos y porcentajes
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sqlConfig);
    echo "Tabla config_planilla creada o verificada.\n";

    // Insertar configuración por defecto si no existe
    $checkConfig = $db->query("SELECT COUNT(*) FROM config_planilla")->fetchColumn();
    if ($checkConfig == 0) {
        $defaultTramos = json_encode([
            ['min' => 0, 'max' => 400000, 'monto' => 0],
            ['min' => 400001, 'max' => 500000, 'monto' => 1000],
            ['min' => 500001, 'max' => 750000, 'monto' => 2300],
            ['min' => 750001, 'max' => 999999999, 'monto' => 3500]
        ]);

        $defaultEscaladores = json_encode([
            ['min' => 92, 'max' => 95, 'porcentaje' => 50],
            ['min' => 95, 'max' => 98, 'porcentaje' => 75],
            ['min' => 98, 'max' => 100, 'porcentaje' => 100]
        ]);

        $stmt = $db->prepare("INSERT INTO config_planilla (sueldo_base_general, minimo_clientes, minimo_normalidad, tramos_comision, escaladores_normalidad) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([0.00, 60, 92.00, $defaultTramos, $defaultEscaladores]);
        echo "Configuración por defecto insertada.\n";
    }

    // 2. Tabla Histórico de Planillas
    $sqlHistorico = "CREATE TABLE IF NOT EXISTS historico_planillas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        colaborador_id INT NOT NULL,
        mes INT NOT NULL,
        anio INT NOT NULL,
        fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        sueldo_base DECIMAL(10,2) DEFAULT 0.00,
        comision_calculada DECIMAL(10,2) DEFAULT 0.00,
        gastos_campo DECIMAL(10,2) DEFAULT 0.00,
        total_pagar DECIMAL(10,2) DEFAULT 0.00,
        clientes_activos INT DEFAULT 0,
        saldo_cartera DECIMAL(12,2) DEFAULT 0.00,
        normalidad_porcentaje DECIMAL(5,2) DEFAULT 0.00,
        detalle_calculo TEXT, -- JSON con explicaciones (candados activados, tramo aplicado, etc)
        estado ENUM('borrador', 'pagado') DEFAULT 'borrador',
        FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id_colaborador)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sqlHistorico);
    echo "Tabla historico_planillas creada o verificada.\n";

    // 3. Modificar Tabla Colaboradores (Excepciones de Sueldo)
    // Verificar si existe la columna primero
    $colCheck = $db->query("SHOW COLUMNS FROM colaboradores LIKE 'sueldo_base_excepcion'");
    if ($colCheck->rowCount() == 0) {
        $db->exec("ALTER TABLE colaboradores ADD COLUMN sueldo_base_excepcion DECIMAL(10,2) DEFAULT NULL");
        echo "Columna sueldo_base_excepcion agregada a colaboradores.\n";
    } else {
        echo "Columna sueldo_base_excepcion ya existe.\n";
    }

    // 4. Integracion con Movimientos Bancarios y Gastos (Verificar tablas existan)
    // Se asume existen 'gastos_operativos', 'movimientos_bancarios', 'movimientos_internos_agencia' por el prompt.

    echo "Instalación completada correctamente.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
