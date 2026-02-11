<?php
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
        $defaultContrato = '<h3 style="text-align: center;">CONTRATO DE PRÉSTAMO MERCANTIL</h3>
<p>En la ciudad de {{ciudad_agencia}}, a los {{dia_actual_letras}} días del mes de {{mes_actual}} del año {{anio_actual}}, comparecen:</p>
<p>Por una parte, <strong>LA FINANCIERA</strong>, y por otra parte, <strong>{{nombre_cliente}}</strong>, mayor de edad, con DNI <strong>{{dni_cliente}}</strong>, en adelante EL DEUDOR.</p>
<p><strong>CLÁUSULA PRIMERA (MONTO):</strong> LA FINANCIERA otorga un préstamo por la cantidad de <strong>L. {{monto_prestamo}} ({{monto_letras}})</strong>.</p>
<p><strong>CLÁUSULA SEGUNDA (PLAZO):</strong> El plazo será de <strong>{{plazo}} {{frecuencia}}es</strong>.</p>
<p><strong>CLÁUSULA TERCERA (INTERÉS):</strong> Se pacta una tasa de interés del {{tasa_interes}}%.</p>';
        $db->prepare("INSERT INTO plantillas_documentos (nombre, tipo, contenido) VALUES (?, ?, ?)")->execute(['Contrato Estándar', 'contrato', $defaultContrato]);
        echo "Default Contrato inserted.\n";
    }

    // Insert Default Pagaré if empty
    $stmt = $db->prepare("SELECT COUNT(*) FROM plantillas_documentos WHERE tipo = 'pagare'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $defaultPagare = '<h3 style="text-align: center;">PAGARÉ</h3>
<p>Yo, <strong>{{nombre_cliente}}</strong>, con DNI: <strong>{{dni_cliente}}</strong>, por este Pagaré prometo pagar incondicionalmente a la orden de LA FINANCIERA la suma de:</p>
<h2 style="text-align: center;">L. {{monto_prestamo}}</h2>
<p style="text-align: center;">(SON: {{monto_letras}})</p>
<p>El pago se realizará mediante {{plazo}} cuotas de L. {{cuota}} cada una, pagaderas de forma {{frecuencia}}.</p>';
        $db->prepare("INSERT INTO plantillas_documentos (nombre, tipo, contenido) VALUES (?, ?, ?)")->execute(['Pagaré Notarial', 'pagare', $defaultPagare]);
        echo "Default Pagaré inserted.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>