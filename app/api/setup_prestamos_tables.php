<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    // Tabla de Préstamos
    $sqlPrestamos = "CREATE TABLE IF NOT EXISTS prestamos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_cliente INT NOT NULL,
        monto_capital DECIMAL(12, 2) NOT NULL,
        modalidad ENUM('Diario', 'Semanal', 'Catorcenal', 'Mensual') NOT NULL,
        plazo_meses INT NOT NULL,
        tasa_total DECIMAL(5, 2) NOT NULL,
        valor_cuota DECIMAL(12, 2) NOT NULL,
        total_a_pagar DECIMAL(12, 2) NOT NULL,
        estado ENUM('Pendiente', 'Activo', 'Finalizado') DEFAULT 'Pendiente',
        fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sqlPrestamos);
    echo "Tabla 'prestamos' creada exitosamente.<br>";

    // Tabla de Cuotas
    $sqlCuotas = "CREATE TABLE IF NOT EXISTS prestamos_cuotas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_prestamo INT NOT NULL,
        numero_cuota INT NOT NULL,
        fecha_vencimiento DATE NOT NULL,
        monto DECIMAL(12, 2) NOT NULL,
        estado_pago ENUM('Pendiente', 'Pagado', 'Parcial', 'Mora') DEFAULT 'Pendiente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id_prestamo) REFERENCES prestamos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sqlCuotas);
    echo "Tabla 'prestamos_cuotas' creada exitosamente.<br>";

} catch (PDOException $e) {
    echo "Error Creating Tables: " . $e->getMessage();
}
?>