<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';

if (!isset($_GET['ids']) || !isset($_GET['types'])) {
    die("Faltan parámetros");
}

$ids = explode(',', $_GET['ids']);
$typesToPrint = explode(',', $_GET['types']);
$db = getDB();

// Fetch all loans at once
$placeholders = str_repeat('?,', count($ids) - 1) . '?';
$stmt = $db->prepare("SELECT p.*, c.nombre_completo, c.numero_documento, c.direccion 
                      FROM prestamos p
                      JOIN clientes c ON p.id_cliente = c.id
                      WHERE p.id IN ($placeholders)");
$stmt->execute($ids);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($loans)) die("No se encontraron registros");

// Mapping for clean display
$docTitles = [
    'contrato' => 'CONTRATO DE PRÉSTAMO MERCANTIL',
    'pagare' => 'PAGARÉ A LA ORDEN',
    'plan' => 'PLAN DE PAGOS (PRELIMINAR)'
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Impresión Masiva de Documentos</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            padding: 40px; /* Base padding */
            line-height: 1.6;
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 16px;
        }
        .content {
            margin-bottom: 40px;
            text-align: justify;
        }
        .signatures {
            margin-top: 100px;
            display: flex;
            justify-content: space-between;
        }
        .sig-block {
            border-top: 1px solid black;
            width: 40%;
            text-align: center;
            padding-top: 10px;
        }
        .page-break {
            page-break-after: always;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        .table th, .table td {
            border: 1px solid black;
            padding: 6px;
            text-align: center;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="position:fixed; top:20px; right:20px; background:white; padding:10px; border:1px solid #ccc; box-shadow:0 2px 10px rgba(0,0,0,0.1);">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor:pointer; background:#4F46E5; color:white; border:none; border-radius:4px;">
            <i class="fas fa-print"></i> Imprimir Todo
        </button>
    </div>

    <?php 
    $totalDocs = count($loans) * count($typesToPrint);
    $counter = 0;

    foreach ($loans as $loan): 
        // Pre-calculate common values
        $interes = number_format($loan['tasa_interes'] ?? 4.00, 2);
        $gastos = number_format($loan['tasa_gastos'] ?? 4.00, 2);
        $comision = number_format($loan['tasa_comision'] ?? 3.00, 2);
        $tasaTotal = number_format($loan['tasa_total'] ?? 11.00, 2);
        $monto = number_format($loan['monto_capital'], 2);
        $totalPagar = number_format($loan['total_a_pagar'], 2);
        $cuota = number_format($loan['valor_cuota'], 2);
        $plazo = $loan['plazo_meses'];
        $modalidad = $loan['modalidad'];
        
        // Loop through selected doc types for THIS user
        foreach ($typesToPrint as $type):
            $counter++;
            $isLast = ($counter == $totalDocs);
    ?>
        
        <div class="document-container">
            
            <?php if ($type === 'contrato'): ?>
                <div class="header">CONTRATO DE PRÉSTAMO MERCANTIL</div>
                <div class="content">
                    <p>Entre los suscritos, por una parte <strong>SISTEMA FINANCIERA</strong>, y por la otra
                        <strong><?php echo $loan['nombre_completo']; ?></strong> con DNI
                        <strong><?php echo $loan['numero_documento']; ?></strong> (en adelante "EL DEUDOR"), convienen celebrar el
                        presente Contrato de Préstamo sujeto a las siguientes cláusulas:
                    </p>
                    <div style="margin-bottom:15px;"><span style="font-weight:bold; text-decoration:underline;">PRIMERA (MONTO):</span> La Financiera otorga al Deudor un
                        préstamo por la suma de <strong>L <?php echo $monto; ?></strong>.</div>
                    <div style="margin-bottom:15px;"><span style="font-weight:bold; text-decoration:underline;">SEGUNDA (PLAZO Y MODALIDAD):</span> El plazo para el pago total
                        será de <strong><?php echo $plazo; ?> meses</strong>, pagaderos en cuotas
                        <strong><?php echo $modalidad; ?>s</strong>.
                    </div>
                    <div style="margin-bottom:15px;"><span style="font-weight:bold; text-decoration:underline;">TERCERA (INTERESES Y GASTOS):</span> El crédito devengará una
                        Tasa Global del <strong><?php echo $tasaTotal; ?>%</strong> mensual, desglozada de la siguiente manera:
                        <ul>
                            <li>Interés Corriente: <?php echo $interes; ?>%</li>
                            <li>Gastos Administrativos: <?php echo $gastos; ?>%</li>
                            <li>Comisión por Papelería: <?php echo $comision; ?>%</li>
                        </ul>
                    </div>
                    <div style="margin-bottom:15px;"><span style="font-weight:bold; text-decoration:underline;">CUARTA (PAGOS):</span> El Deudor se compromete a realizar el pago
                        de cuotas consecutivas de <strong>L <?php echo $cuota; ?></strong> hasta cancelar la deuda, haciendo un
                        total a pagar de <strong>L <?php echo $totalPagar; ?></strong>.</div>
                    <div style="margin-bottom:15px;"><span style="font-weight:bold; text-decoration:underline;">QUINTA (INCUMPLIMIENTO):</span> La falta de pago de dos o más
                        cuotas dará derecho a la La Financiera a dar por vencido el plazo y exigir la totalidad de la deuda.</div>
                </div>
                <div class="signatures">
                    <div class="sig-block">POR LA FINANCIERA</div>
                    <div class="sig-block"><?php echo $loan['nombre_completo']; ?><br>EL DEUDOR</div>
                </div>

            <?php elseif ($type === 'pagare'): ?>
                <div class="header">PAGARÉ A LA ORDEN</div>
                <div class="content">
                    <p style="text-align: right;"><strong>Monto: L <?php echo $totalPagar; ?></strong></p>
                    <p>Por este Pagaré, yo, <strong><?php echo $loan['nombre_completo']; ?></strong>, prometo pagar
                        incondicionalmente a la orden de <strong>SISTEMA FINANCIERA</strong> la suma principal de <strong>L
                            <?php echo $totalPagar; ?></strong>.</p>
                    <p>Este valor será pagado mediante cuotas <strong><?php echo $modalidad; ?>s</strong> de L
                        <?php echo $cuota; ?>.
                    </p>
                    <p>La falta de pago de cualquiera de las cuotas pactadas hará exigible el saldo total insoluto de inmediato, sin
                        necesidad de requerimiento judicial o extrajudicial.</p>
                    <p>En caso de cobro judicial, seré responsable de todas las costas procesales y honorarios de abogados.</p>
                </div>
                <div class="signatures">
                    <div class="sig-block"></div>
                    <div class="sig-block"><?php echo $loan['nombre_completo']; ?><br>Firma del Deudor<br>DNI:
                        <?php echo $loan['numero_documento']; ?>
                    </div>
                </div>

            <?php elseif ($type === 'plan'): 
                // Since quotas are NOT generated yet in this stage (Formalización), we render a PREVIEW
                // We use the same view logic as the modal preview
                ?>
                <div class="header">PLAN DE PAGOS (VISTA PREVIA)</div>
                <div class="content">
                    <p><strong>Cliente:</strong> <?php echo $loan['nombre_completo']; ?></p>
                    <p><strong>Monto:</strong> L <?php echo $monto; ?></p>
                    <p><strong>Total a Pagar:</strong> L <?php echo $totalPagar; ?></p>
                    <br>
                    <p><em>* Este es un plan de pagos preliminar basado en las condiciones pactadas. El calendario definitivo de fechas se generará al momento del desembolso oficial.</em></p>
                    
                    <table class="table" style="width:50%; margin:0 auto;">
                        <tr>
                            <th>Concepto</th>
                            <th>Detalle</th>
                        </tr>
                        <tr><td>Plazo</td><td><?php echo $plazo; ?> meses</td></tr>
                        <tr><td>Modalidad</td><td><?php echo $modalidad; ?></td></tr>
                        <tr><td>Cuota Estimada</td><td>L <?php echo $cuota; ?></td></tr>
                        <tr><td>Frecuencia</td><td>Cada periodo <?php echo $modalidad; ?></td></tr>
                    </table>
                </div>
            <?php endif; ?>

        </div>
        
        <?php if (!$isLast): ?>
            <div class="page-break"></div>
        <?php endif; ?>

    <?php endforeach; endforeach; ?>

</body>
</html>
