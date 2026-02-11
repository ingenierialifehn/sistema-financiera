<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();

    // Check if garantia template exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM plantillas_documentos WHERE tipo = 'garantia'");
    $stmt->execute();

    if ($stmt->fetchColumn() == 0) {
        $defaultGarantia = '<h3 style="text-align: center;">DACIÓN DE GARANTÍA</h3>
<p>En la ciudad de {{ciudad_agencia}}, a los {{dia_actual_letras}} días del mes de {{mes_actual}} del año {{anio_actual}}.</p>

<p>Yo, <strong>{{nombre_cliente}}</strong>, con DNI: <strong>{{dni_cliente}}</strong>, en mi calidad de DEUDOR del préstamo otorgado por LA FINANCIERA por la suma de <strong>L. {{monto_prestamo}} ({{monto_letras}})</strong>, por medio del presente documento:</p>

<h4>DECLARO:</h4>
<p>Que en garantía del pago del crédito antes mencionado, ofrezco los siguientes bienes:</p>

<ol>
    <li>Descripción del bien 1: _______________________________</li>
    <li>Descripción del bien 2: _______________________________</li>
</ol>

<p>Los bienes anteriormente descritos quedan en prenda a favor de LA FINANCIERA hasta la cancelación total del préstamo.</p>

<p><strong>ACEPTO</strong> que en caso de incumplimiento en el pago de las cuotas pactadas, LA FINANCIERA podrá ejecutar la garantía conforme a la ley.</p>

<br><br>
<p>_______________________________</p>
<p>Firma del Deudor</p>
<p>{{nombre_cliente}}</p>
<p>DNI: {{dni_cliente}}</p>';

        $db->prepare("INSERT INTO plantillas_documentos (nombre, tipo, contenido) VALUES (?, ?, ?)")
            ->execute(['Dación de Garantía Estándar', 'garantia', $defaultGarantia]);

        echo "Plantilla de Garantía creada exitosamente.\n";
    } else {
        echo "La plantilla de Garantía ya existe.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>