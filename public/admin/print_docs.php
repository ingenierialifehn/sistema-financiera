<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';

if (!isset($_GET['type']) || (!isset($_GET['id']) && !isset($_GET['ids']))) {
    die("Faltan parámetros");
}

$type = $_GET['type'];
$ids = [];
if (isset($_GET['ids'])) {
    $ids = explode(',', $_GET['ids']);
} elseif (isset($_GET['id'])) {
    $ids = [intval($_GET['id'])];
}

$db = getDB();
$loan = null;
$isNewPayment = false;

// Variables for Aggregated Receipt
$agg = [
    'capital' => 0,
    'interes' => 0,
    'gastos' => 0,
    'comision' => 0,
    'total' => 0,
    'conceptos' => [],
    'cuotas_nums' => []
];

if ($type === 'ticket_pago') {
    // 1. Fetch ALL involved records from 'cuotas'
    // We treat 'cuotas' rows as the payment records.
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $sql = "SELECT c.*, p.modalidad, p.plazo_meses, p.total_a_pagar, p.monto_capital, p.fecha_desembolso,
            cl.nombre_completo, cl.numero_documento, cl.direccion, p.id as prestamo_real_id
            FROM cuotas c
            JOIN prestamos p ON c.prestamo_id = p.id
            JOIN clientes cl ON p.id_cliente = cl.id
            WHERE c.id IN ($placeholders)";

    $stmt = $db->prepare($sql);
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) > 0) {
        $loan = $rows[0]; // Take client/loan info from first record
        $isNewPayment = true; // Force new format for breakdown

        foreach ($rows as $r) {
            $agg['total'] += floatval($r['monto_pagado']);
            // If fields are null (old data), treat as 0 or fallback? 
            // Assuming data written by process_payment has these fields.
            $agg['capital'] += floatval($r['capital_cuota'] ?? 0);
            $agg['interes'] += floatval($r['interes_cuota'] ?? 0);
            $agg['gastos'] += floatval($r['gastos_cuota'] ?? 0);
            $agg['comision'] += floatval($r['comision_cuota'] ?? 0);

            if ($r['numero_cuota'] == 0) {
                $agg['conceptos'][] = "Abono Capital";
            } else {
                $agg['cuotas_nums'][] = "#" . $r['numero_cuota'];
            }
        }

        // Finalize Concept String
        if (!empty($agg['cuotas_nums'])) {
            $agg['conceptos'][] = "Cuota(s) " . implode(', ', $agg['cuotas_nums']);
        }
        $loan['concepto_unificado'] = implode(' + ', $agg['conceptos']);

        // Calculate Remaining Balance (of the LOAN)
        // Sum of all non-paid quotas
        $stmtSaldo = $db->prepare("SELECT IFNULL(SUM(monto_cuota - monto_pagado),0) FROM cuotas WHERE prestamo_id = ? AND estado != 'pagada'");
        $stmtSaldo->execute([$loan['prestamo_real_id']]);
        $loan['saldo_restante'] = $stmtSaldo->fetchColumn();

        // Map aggregated values to keys used in template
        $loan['monto_total'] = $agg['total'];
        $loan['abono_capital'] = $agg['capital'];
        $loan['interes_pagado'] = $agg['interes'];
        $loan['gastos_financieros'] = $agg['gastos'];
        $loan['comision_papeleria'] = $agg['comision'];
        $loan['fecha_pago'] = $rows[0]['fecha_pago_real'] ?? date('Y-m-d H:i:s');
    }

} else {
    // Normal single-record logic for other types (Contrato, etc)
    $id = $ids[0];
    // ... existing logic for other types ...
    $stmt = $db->prepare("SELECT p.*, c.nombre_completo, c.numero_documento, c.direccion 
                          FROM prestamos p
                          JOIN clientes c ON p.id_cliente = c.id
                          WHERE p.id = ?");
    $stmt->execute([$id]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$loan)
    die("Registro no encontrado");

// Formateo Vars
if ($type !== 'ticket_pago') {
    $interes = number_format($loan['tasa_interes'] ?? 4.00, 2);
    $gastos = number_format($loan['tasa_gastos'] ?? 4.00, 2);
    $comision = number_format($loan['tasa_comision'] ?? 3.00, 2);
    $tasaTotal = number_format($loan['tasa_total'] ?? 11.00, 2);
    $monto = number_format($loan['monto_capital'], 2);
    $totalPagar = number_format($loan['total_a_pagar'], 2);
    $cuota = number_format($loan['valor_cuota'], 2);
    $plazo = $loan['plazo_meses'];
    $modalidad = $loan['modalidad'];
} else {
    $totalPagar = number_format($loan['total_a_pagar'], 2);
    $modalidad = $loan['modalidad'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Impresión de Documento</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            padding: 40px;
            line-height: 1.6;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            font-weight: bold;
            text-transform: uppercase;
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

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }

        .clause {
            margin-bottom: 15px;
        }

        .clause-title {
            font-weight: bold;
            text-decoration: underline;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px;">Imprimir</button>
    </div>

    <?php if ($type === 'contrato'): ?>
        <div class="header">CONTRATO DE PRÉSTAMO MERCANTIL</div>
        <div class="content">
            <p>Entre los suscritos, por una parte <strong>SISTEMA FINANCIERA</strong>, y por la otra
                <strong><?php echo $loan['nombre_completo']; ?></strong> con DNI
                <strong><?php echo $loan['numero_documento']; ?></strong> (en adelante "EL DEUDOR"), convienen celebrar el
                presente Contrato de Préstamo sujeto a las siguientes cláusulas:
            </p>
            <div class="clause"><span class="clause-title">PRIMERA (MONTO):</span> La Financiera otorga al Deudor un
                préstamo por la suma de <strong>L <?php echo $monto; ?></strong>.</div>
            <div class="clause"><span class="clause-title">SEGUNDA (PLAZO Y MODALIDAD):</span> El plazo para el pago total
                será de <strong><?php echo $plazo; ?> meses</strong>, pagaderos en cuotas
                <strong><?php echo $modalidad; ?>s</strong>.
            </div>
            <div class="clause"><span class="clause-title">TERCERA (INTERESES Y GASTOS):</span> El crédito devengará una
                Tasa Global del <strong><?php echo $tasaTotal; ?>%</strong> mensual, desglozada de la siguiente manera:
                <ul>
                    <li>Interés Corriente: <?php echo $interes; ?>%</li>
                    <li>Gastos Administrativos: <?php echo $gastos; ?>%</li>
                    <li>Comisión por Papelería: <?php echo $comision; ?>%</li>
                </ul>
            </div>
            <div class="clause"><span class="clause-title">CUARTA (PAGOS):</span> El Deudor se compromete a realizar el pago
                de cuotas consecutivas de <strong>L <?php echo $cuota; ?></strong> hasta cancelar la deuda, haciendo un
                total a pagar de <strong>L <?php echo $totalPagar; ?></strong>.</div>
            <div class="clause"><span class="clause-title">QUINTA (INCUMPLIMIENTO):</span> La falta de pago de dos o más
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
        $stmtCuotas = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id = ? ORDER BY numero_cuota ASC");
        $stmtCuotas->execute([$loan['id']]); // $loan es el prestamo aqui
        $cuotasReales = $stmtCuotas->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="header">PLAN DE PAGOS AUTORIZADO</div>
        <div class="content">
            <p><strong>Cliente:</strong> <?php echo $loan['nombre_completo']; ?></p>
            <p><strong>Deuda Total:</strong> L <?php echo $totalPagar; ?></p>
            <p><strong>Modalidad:</strong> <?php echo $modalidad; ?></p>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nº Cuota</th>
                        <th>Fecha Vencimiento</th>
                        <th>Valor Cuota</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($cuotasReales) > 0):
                        foreach ($cuotasReales as $c): ?>
                            <tr>
                                <td><?php echo $c['numero_cuota']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($c['fecha_vencimiento'])); ?></td>
                                <td>L <?php echo number_format($c['monto_cuota'], 2); ?></td>
                                <td><?php echo ucfirst($c['estado']); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="4">No se encontraron cuotas generadas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($type === 'recibo_entrega'): ?>
        <div class="header">RECIBO CONFORME DE DESEMBOLSO</div>
        <div class="content">
            <p style="text-align: right;"><strong>Fecha:</strong> <?php echo date('d/m/Y'); ?></p>
            <p style="text-align: right;"><strong>Monto Recibido: L
                    <?php echo number_format($loan['neto_entregar'] ?? $loan['monto_capital'], 2); ?></strong></p>
            <p>Yo, <strong><?php echo $loan['nombre_completo']; ?></strong>, con DNI
                <strong><?php echo $loan['numero_documento']; ?></strong>, declaro haber recibido de <strong>SISTEMA
                    FINANCIERA</strong> la cantidad de
                <strong><?php echo number_format($loan['neto_entregar'] ?? $loan['monto_capital'], 2); ?> Lempiras</strong>
                en efectivo / cheque / transferencia, correspondiente al desembolso del préstamo No.
                <?php echo str_pad($loan['id'], 6, '0', STR_PAD_LEFT); ?>.
            </p>
            <p>Firmo la presente en señal de conformidad y aceptación de los fondos recibidos.</p>
        </div>
        <div class="signatures">
            <div class="sig-block">ENTREGADO POR<br>OFICIAL DE DESEMBOLSOS</div>
            <div class="sig-block"><?php echo $loan['nombre_completo']; ?><br>RECIBÍ CONFORME<br>DNI:
                <?php echo $loan['numero_documento']; ?>
            </div>
        </div>

    <?php elseif ($type === 'ticket_pago'): ?>
        <div class="ticket"
            style="width: 300px; margin: 0 auto; border: 1px dashed black; padding: 10px; font-family: monospace;">
            <div class="header" style="margin-bottom: 10px;">
                SISTEMA FINANCIERA<br>COMPROBANTE DE PAGO
            </div>
            <div class="content" style="font-size: 14px;">
                <p>Fecha: <?php echo date('d/m/Y H:i', strtotime($loan['fecha_pago'])); ?><br>
                    Recibo #: <?php echo str_pad($ids[0], 6, '0', STR_PAD_LEFT); ?></p>
                <p>Cliente: <?php echo $loan['nombre_completo']; ?></p>

                <hr style="border-top: 1px dashed #000;">

                <p style="margin-bottom: 5px;">
                    <strong>Préstamo #<?php echo $loan['prestamo_real_id']; ?></strong> (<?php echo $loan['modalidad']; ?>)<br>
                    Monto Or.: L <?php echo number_format($loan['monto_capital'], 2); ?><br>
                    Fecha Ot.: <?php echo date('d/m/Y', strtotime($loan['fecha_desembolso'])); ?>
                </p>

                <hr style="border-top: 1px dashed #000;">

                <p><strong>Concepto:</strong><br>
                    <?php echo $loan['concepto_unificado']; ?>
                </p>

                <?php if ($isNewPayment): ?>
                    <!-- Desglose Nuevo -->
                    <table style="width:100%; font-size:12px; margin-bottom:10px; border-collapse: collapse;">
                        <tr>
                            <td style="border:none;">Capital:</td>
                            <td style="text-align:right; border:none;">L <?php echo number_format($loan['abono_capital'], 2); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none;">Interés:</td>
                            <td style="text-align:right; border:none;">L
                                <?php echo number_format($loan['interes_pagado'], 2); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none;">Gastos Admin:</td>
                            <td style="text-align:right; border:none;">L
                                <?php echo number_format($loan['gastos_financieros'], 2); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none;">Comisión:</td>
                            <td style="text-align:right; border:none;">L
                                <?php echo number_format($loan['comision_papeleria'], 2); ?>
                            </td>
                        </tr>
                    </table>
                <?php else: ?>
                    <p>Detalle: Pago Cuota #<?php echo $loan['numero_cuota'] ?? 'N/A'; ?></p>
                <?php endif; ?>

                <p style="text-align: right; font-size: 18px; margin: 10px 0;">
                    <strong>TOTAL: L <?php echo number_format($loan['monto_total'], 2); ?></strong>
                </p>

                <hr style="border-top: 1px dashed #000;">

                <p>Saldo Restante Préstamo:<br>
                    <strong>L <?php echo number_format($loan['saldo_restante'], 2); ?></strong>
                </p>

                <p style="text-align: center; margin-top: 20px; font-size: 12px;">*** GRACIAS POR SU PAGO ***</p>
            </div>
        </div>
    <?php endif; ?>

</body>

</html>