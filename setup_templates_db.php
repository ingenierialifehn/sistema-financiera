<?php
require_once __DIR__ . '/public/admin/includes/layout.php'; // Reuse layout context for config/db
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();

    // Create Table
    $sql = "CREATE TABLE IF NOT EXISTS plantillas_documentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        tipo ENUM('contrato', 'pagare', 'garantia', 'recibo', 'otro') NOT NULL,
        contenido LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        estado ENUM('activo', 'inactivo') DEFAULT 'activo',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "Table 'plantillas_documentos' created or exists.\n";

    // Insert Default Contrato if empty
    $stmt = $db->prepare("SELECT COUNT(*) FROM plantillas_documentos WHERE tipo = 'contrato'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $defaultContrato = '<h1>CONTRATO DE PRÉSTAMO</h1>
<p>Entre los suscritos, <strong>{{nombre_cliente}}</strong>, mayor de edad, con DNI <strong>{{dni_cliente}}</strong>, y la Financiera, celebran el presente contrato...</p>
<p>Monto: L. {{monto_prestamo}}</p>';
        $db->prepare("INSERT INTO plantillas_documentos (nombre, tipo, contenido) VALUES (?, ?, ?)")->execute(['Contrato Estándar', 'contrato', $defaultContrato]);
        echo "Default Contrato inserted.\n";
    }

    // Insert Default Pagaré if empty
    $stmt = $db->prepare("SELECT COUNT(*) FROM plantillas_documentos WHERE tipo = 'pagare'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $defaultPagare = '<h1>PAGARÉ</h1>
<p>Yo, {{nombre_cliente}}, prometo pagar incondicionalmente a la orden de LA FINANCIERA la suma de L. {{monto_prestamo}} ({{monto_letras}}).</p>';
        $db->prepare("INSERT INTO plantillas_documentos (nombre, tipo, contenido) VALUES (?, ?, ?)")->execute(['Pagaré Notarial', 'pagare', $defaultPagare]);
        echo "Default Pagaré inserted.\n";
    }

    // Insert Default Garantia if empty
    $stmt = $db->prepare("SELECT COUNT(*) FROM plantillas_documentos WHERE tipo = 'garantia'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $defaultGarantia = '<h1>DACIÓN DE GARANTÍA</h1>
<p>En garantía del pago del crédito por L. {{monto_prestamo}}, el DEUDOR ofrece...</p>';
        $db->prepare("INSERT INTO plantillas_documentos (nombre, tipo, contenido) VALUES (?, ?, ?)")->execute(['Garantía Prendaria', 'garantia', $defaultGarantia]);
        echo "Default Garantía inserted.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>