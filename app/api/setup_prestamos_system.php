<?php
require_once '../../app/config/db.php';

try {
    $conn = getDBConnection();

    // Create prestamos table
    $sql = "CREATE TABLE IF NOT EXISTS prestamos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        agencia_id INT NOT NULL,
        usuario_creacion_id INT NOT NULL,
        capital DECIMAL(10,2) NOT NULL,
        modalidad ENUM('Diario', 'Semanal', 'Catorcenal', 'Mensual') NOT NULL,
        plazo INT NOT NULL COMMENT 'Plazo en meses',
        tasa_interes DECIMAL(5,2) NOT NULL DEFAULT 4.00,
        tasa_gastos DECIMAL(5,2) NOT NULL DEFAULT 4.00,
        tasa_comision DECIMAL(5,2) NOT NULL DEFAULT 3.00,
        total_pagar DECIMAL(10,2) NOT NULL,
        valor_cuota DECIMAL(10,2) NOT NULL,
        estado ENUM('Pendiente', 'Activo', 'Finalizado', 'Rechazado') NOT NULL DEFAULT 'Pendiente',
        fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
        fecha_desembolso DATETIME NULL,
        fecha_finalizacion DATETIME NULL,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id),
        FOREIGN KEY (agencia_id) REFERENCES agencias(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $conn->exec($sql);
    echo "Tabla 'prestamos' creada o ya existe.\n";

    // Create cuotas_prestamos table
    $sql = "CREATE TABLE IF NOT EXISTS cuotas_prestamos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        prestamo_id INT NOT NULL,
        numero_cuota INT NOT NULL,
        fecha_vencimiento DATE NOT NULL,
        monto_cuota DECIMAL(10,2) NOT NULL,
        estado ENUM('Pendiente', 'Pagado', 'Vencido', 'Parcial') NOT NULL DEFAULT 'Pendiente',
        fecha_pago DATETIME NULL,
        FOREIGN KEY (prestamo_id) REFERENCES prestamos(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $conn->exec($sql);
    echo "Tabla 'cuotas_prestamos' creada o ya existe.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>