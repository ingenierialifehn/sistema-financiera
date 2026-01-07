<?php
/**
 * Setup Completo: Módulos Bóveda y Operaciones
 * Ejecutar este script para configurar todas las tablas necesarias
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    echo "<h2>Iniciando configuración completa...</h2>";

    // ============================================
    // 1. VERIFICAR Y CREAR COLUMNA saldo_efectivo EN AGENCIAS
    // ============================================
    echo "<h3>1. Configurando tabla agencias...</h3>";

    $checkSaldoEfectivo = $db->query("SHOW COLUMNS FROM agencias LIKE 'saldo_efectivo'");
    if ($checkSaldoEfectivo->rowCount() == 0) {
        // Verificar si existe saldo_caja
        $checkSaldoCaja = $db->query("SHOW COLUMNS FROM agencias LIKE 'saldo_caja'");
        if ($checkSaldoCaja->rowCount() > 0) {
            // Renombrar saldo_caja a saldo_efectivo
            $db->exec("ALTER TABLE agencias CHANGE COLUMN saldo_caja saldo_efectivo DECIMAL(15,2) NOT NULL DEFAULT 0.00");
            echo "✓ Columna 'saldo_caja' renombrada a 'saldo_efectivo' en 'agencias'.<br>";
        } else {
            // Crear saldo_efectivo
            $db->exec("ALTER TABLE agencias ADD COLUMN saldo_efectivo DECIMAL(15,2) NOT NULL DEFAULT 0.00");
            echo "✓ Columna 'saldo_efectivo' creada en 'agencias'.<br>";
        }
    } else {
        echo "✓ Columna 'saldo_efectivo' ya existe en 'agencias'.<br>";
    }

    // ============================================
    // 2. CREAR TABLA ingresos_bancos_agencia
    // ============================================
    echo "<h3>2. Configurando tabla ingresos_bancos_agencia...</h3>";

    $sqlIngresos = "
    CREATE TABLE IF NOT EXISTS ingresos_bancos_agencia (
        id INT PRIMARY KEY AUTO_INCREMENT,
        banco_id INT NOT NULL,
        agencia_id INT NOT NULL,
        monto DECIMAL(15,2) NOT NULL,
        referencia VARCHAR(100) NULL,
        saldo_anterior_banco DECIMAL(15,2) NOT NULL,
        saldo_nuevo_banco DECIMAL(15,2) NOT NULL,
        saldo_anterior_agencia DECIMAL(15,2) NOT NULL,
        saldo_nuevo_agencia DECIMAL(15,2) NOT NULL,
        realizado_por INT NOT NULL,
        fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        observaciones TEXT NULL,
        FOREIGN KEY (banco_id) REFERENCES bancos(id) ON DELETE RESTRICT,
        FOREIGN KEY (agencia_id) REFERENCES agencias(id_agencia) ON DELETE RESTRICT,
        FOREIGN KEY (realizado_por) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
        INDEX idx_banco (banco_id),
        INDEX idx_agencia (agencia_id),
        INDEX idx_fecha (fecha_hora)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sqlIngresos);
    echo "✓ Tabla 'ingresos_bancos_agencia' verificada/creada.<br>";

    // ============================================
    // 3. MODIFICAR ENUM DE ESTADO EN PRESTAMOS
    // ============================================
    echo "<h3>3. Actualizando tabla prestamos...</h3>";

    // Verificar si el estado 'aprobado' ya existe
    $checkEstado = $db->query("SHOW COLUMNS FROM prestamos LIKE 'estado'");
    $estadoInfo = $checkEstado->fetch(PDO::FETCH_ASSOC);

    if ($estadoInfo && strpos($estadoInfo['Type'], 'aprobado') === false) {
        $sql = "ALTER TABLE prestamos 
                MODIFY COLUMN estado ENUM('pendiente', 'aprobado', 'activo', 'completado', 'cancelado', 'en_mora') 
                NOT NULL DEFAULT 'pendiente'";
        $db->exec($sql);
        echo "✓ Estado 'aprobado' agregado a la tabla préstamos.<br>";
    } else {
        echo "✓ Estado 'aprobado' ya existe en préstamos.<br>";
    }

    // ============================================
    // 4. AGREGAR id_agencia A PRESTAMOS
    // ============================================
    echo "<h3>4. Agregando id_agencia a prestamos...</h3>";

    $checkColPrestamo = $db->query("SHOW COLUMNS FROM prestamos LIKE 'id_agencia'");
    if ($checkColPrestamo->rowCount() == 0) {
        $db->exec("ALTER TABLE prestamos ADD COLUMN id_agencia INT NULL AFTER cliente_id");
        $db->exec("ALTER TABLE prestamos ADD FOREIGN KEY (id_agencia) REFERENCES agencias(id_agencia) ON DELETE SET NULL");
        $db->exec("ALTER TABLE prestamos ADD INDEX idx_agencia (id_agencia)");
        echo "✓ Columna 'id_agencia' agregada a 'prestamos'.<br>";
    } else {
        echo "✓ Columna 'id_agencia' ya existe en 'prestamos'.<br>";
    }

    // ============================================
    // 5. AGREGAR id_agencia A CLIENTES
    // ============================================
    echo "<h3>5. Agregando id_agencia a clientes...</h3>";

    $checkColCliente = $db->query("SHOW COLUMNS FROM clientes LIKE 'id_agencia'");
    if ($checkColCliente->rowCount() == 0) {
        $db->exec("ALTER TABLE clientes ADD COLUMN id_agencia INT NULL AFTER cobrador_id");
        $db->exec("ALTER TABLE clientes ADD FOREIGN KEY (id_agencia) REFERENCES agencias(id_agencia) ON DELETE SET NULL");
        $db->exec("ALTER TABLE clientes ADD INDEX idx_agencia_cliente (id_agencia)");
        echo "✓ Columna 'id_agencia' agregada a 'clientes'.<br>";
    } else {
        echo "✓ Columna 'id_agencia' ya existe en 'clientes'.<br>";
    }

    echo "<br><h2 style='color: green;'>✓ Configuración completada exitosamente!</h2>";
    echo "<p><strong>Próximos pasos:</strong></p>";
    echo "<ol>";
    echo "<li>Configurar permisos en <strong>Roles y Permisos</strong> para los módulos 'Bóveda' y 'Operaciones'</li>";
    echo "<li>Asignar agencias a los usuarios en su perfil de colaborador</li>";
    echo "<li>Asignar agencias a los clientes y préstamos existentes (si aplica)</li>";
    echo "</ol>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
