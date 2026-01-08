<?php
/**
 * Migración: Crear/Actualizar tabla de pagos
 * Fecha: 2026-01-08
 * Descripción: Crea la tabla pagos con la estructura correcta para el sistema de cobranza
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    echo "=== Iniciando migración: Tabla de Pagos ===\n\n";

    // Verificar si la tabla existe
    $stmt = $db->query("SHOW TABLES LIKE 'pagos'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "→ La tabla 'pagos' ya existe. Verificando estructura...\n";

        // Verificar si tiene las columnas necesarias
        $stmt = $db->query("SHOW COLUMNS FROM pagos");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $requiredColumns = [
            'prestamo_id',
            'usuario_id',
            'monto_total',
            'abono_capital',
            'interes_pagado',
            'gastos_financieros',
            'comision_papeleria',
            'fecha_pago'
        ];

        $hasAllColumns = true;
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $columns)) {
                $hasAllColumns = false;
                echo "   ✗ Falta columna: $col\n";
            }
        }

        if (!$hasAllColumns) {
            echo "\n→ Recreando tabla con estructura correcta...\n";
            $db->exec("DROP TABLE IF EXISTS pagos");
            $exists = false;
        } else {
            echo "   ✓ Estructura correcta\n";
        }
    }

    if (!$exists) {
        echo "→ Creando tabla 'pagos'...\n";

        $sql = "
            CREATE TABLE IF NOT EXISTS pagos (
                id INT PRIMARY KEY AUTO_INCREMENT,
                prestamo_id INT NOT NULL,
                usuario_id INT NOT NULL COMMENT 'Usuario que registró el pago',
                monto_total DECIMAL(15,2) NOT NULL COMMENT 'Monto total del pago',
                abono_capital DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Parte que va a capital',
                interes_pagado DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Parte de interés (4/11)',
                gastos_financieros DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Parte de gastos (4/11)',
                comision_papeleria DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Parte de comisión (3/11)',
                fecha_pago DATETIME NOT NULL,
                metodo_pago ENUM('efectivo', 'transferencia', 'deposito', 'otro') DEFAULT 'efectivo',
                observaciones TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE RESTRICT,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
                INDEX idx_prestamo (prestamo_id),
                INDEX idx_usuario (usuario_id),
                INDEX idx_fecha_pago (fecha_pago)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Registro de pagos con desglose detallado'
        ";

        $db->exec($sql);
        echo "✓ Tabla 'pagos' creada exitosamente.\n";
    }

    // Verificar que la tabla esté lista
    $stmt = $db->query("DESCRIBE pagos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\n→ Estructura final de la tabla 'pagos':\n";
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }

    echo "\n=== Migración completada exitosamente ===\n";

} catch (Exception $e) {
    echo "✗ Error en la migración: " . $e->getMessage() . "\n";
    exit(1);
}
