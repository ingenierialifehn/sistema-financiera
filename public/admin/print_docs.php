<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';

if (!isset($_GET['type']) || !isset($_GET['id'])) {
    die("Faltan parámetros");
}

$type = $_GET['type'];
$prestamoId = intval($_GET['id']);

$db = getDB();
$stmt = $db->prepare("SELECT p.*, c.nombre_completo, c.numero_documento, c.direccion 
                      FROM prestamos p
                      JOIN clientes c ON p.id_cliente = c.id
                      WHERE p.id = ?");
$stmt->execute([$prestamoId]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loan)
    die("Préstamo no encontrado");

// Breakdown
$interes = number_format($loan['tasa_interes'] ?? 4.00, 2);
$gastos = number_format($loan['tasa_gastos'] ?? 4.00, 2);
$comision = number_format($loan['tasa_comision'] ?? 3.00, 2);
$tasaTotal = number_format($loan['tasa_total'], 2);
$monto = number_format($loan['monto_capital'], 2);
$totalPagar = number_format($loan['total_a_pagar'], 2);
$cuota = number_format($loan['valor_cuota'], 2);
$plazo = $loan['plazo_meses'];
$modalidad = $loan['modalidad'];

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
            <p>Entre los suscritos, por una parte <strong>SISTEMA FINANCIERA</strong>, y por la otra <strong>
                    <?php echo $loan['nombre_completo']; ?>
                </strong> con DNI <strong>
                    <?php echo $loan['numero_documento']; ?>
                </strong> (en adelante "EL DEUDOR"), convienen celebrar el presente Contrato de Préstamo sujeto a las
                siguientes cláusulas:</p>

            <div class="clause">
                <span class="clause-title">PRIMERA (MONTO):</span> La Financiera otorga al Deudor un préstamo por la suma de
                <strong>L
                    <?php echo $monto; ?>
                </strong>.
            </div>

            <div class="clause">
                <span class="clause-title">SEGUNDA (PLAZO Y MODALIDAD):</span> El plazo para el pago total será de <strong>
                    <?php echo $plazo; ?> meses
                </strong>, pagaderos en cuotas <strong>
                    <?php echo $modalidad; ?>s
                </strong>.
            </div>

            <div class="clause">
                <span class="clause-title">TERCERA (INTERESES Y GASTOS):</span> El crédito devengará una Tasa Global del
                <strong>
                    <?php echo $tasaTotal; ?>%
                </strong> mensual, desglozada de la siguiente manera:
                <ul>
                    <li>Interés Corriente:
                        <?php echo $interes; ?>%
                    </li>
                    <li>Gastos Administrativos:
                        <?php echo $gastos; ?>%
                    </li>
                    <li>Comisión por Papelería:
                        <?php echo $comision; ?>%
                    </li>
                </ul>
            </div>

            <div class="clause">
                <span class="clause-title">CUARTA (PAGOS):</span> El Deudor se compromete a realizar el pago de cuotas
                consecutivas de <strong>L
                    <?php echo $cuota; ?>
                </strong> hasta cancelar la deuda, haciendo un total a pagar de <strong>L
                    <?php echo $totalPagar; ?>
                </strong>.
            </div>

            <div class="clause">
                <span class="clause-title">QUINTA (INCUMPLIMIENTO):</span> La falta de pago de dos o más cuotas dará derecho
                a la Financiera a dar por vencido el plazo y exigir la totalidad de la deuda.
            </div>
        </div>

        <div class="signatures">
            <div class="sig-block">POR LA FINANCIERA</div>
            <div class="sig-block">
                <?php echo $loan['nombre_completo']; ?><br>EL DEUDOR
            </div>
        </div>

    <?php elseif ($type === 'pagare'): ?>
        <div class="header">PAGARÉ A LA ORDEN</div>
        <div class="content">
            <p style="text-align: right;"><strong>Monto: L
                    <?php echo $totalPagar; ?>
                </strong></p>
            <p>Por este Pagaré, yo, <strong>
                    <?php echo $loan['nombre_completo']; ?>
                </strong>, prometo pagar incondicionalmente a la orden de <strong>SISTEMA FINANCIERA</strong> la suma
                principal de <strong>L
                    <?php echo $totalPagar; ?>
                </strong>.</p>

            <p>Este valor será pagado mediante cuotas <strong>
                    <?php echo $modalidad; ?>s
                </strong> de L
                <?php echo $cuota; ?>.
            </p>

            <p>La falta de pago de cualquiera de las cuotas pactadas hará exigible el saldo total insoluto de inmediato, sin
                necesidad de requerimiento judicial o extrajudicial.</p>

            <p>En caso de cobro judicial, seré responsable de todas las costas procesales y honorarios de abogados.</p>
        </div>

        <div class="signatures">
            <div class="sig-block"></div>
            <div class="sig-block">
                <?php echo $loan['nombre_completo']; ?><br>Firma del Deudor<br>DNI:
                <?php echo $loan['numero_documento']; ?>
            </div>
        </div>

    <?php elseif ($type === 'plan'): ?>
        <div class="header">PLAN DE PAGOS PROYECTADO</div>
        <div class="content">
            <p><strong>Cliente:</strong>
                <?php echo $loan['nombre_completo']; ?>
            </p>
            <p><strong>Deuda Total:</strong> L
                <?php echo $totalPagar; ?>
            </p>
            <p><strong>Cuota
                    <?php echo $modalidad; ?>:
                </strong> L
                <?php echo $cuota; ?>
            </p>

            <table class="table">
                <thead>
                    <tr>
                        <th>Nº Cuota</th>
                        <th>Fecha Vencimiento</th>
                        <th>Valor Cuota</th>
                        <th>Saldo Pendiente</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Logic to generate projected schedule skipping weekends
                    $currentDate = new DateTime();
                    $saldo = floatval($loan['total_a_pagar']);
                    $cuotaVal = floatval($loan['valor_cuota']);

                    // Simple estimation of number of quotas based on modality
                    $numCuotas = 0;
                    $daysAdd = 1;

                    switch ($loan['modalidad']) {
                        case 'Diario':
                            $numCuotas = $plazo * 20;
                            $daysAdd = 1;
                            break;
                        case 'Semanal':
                            $numCuotas = $plazo * 4;
                            $daysAdd = 7;
                            break;
                        case 'Catorcenal':
                            $numCuotas = $plazo * 2;
                            $daysAdd = 14;
                            break;
                        case 'Mensual':
                            $numCuotas = $plazo * 1;
                            $daysAdd = 30;
                            break; // Approx
                    }

                    // Loop
                    for ($i = 1; $i <= $numCuotas; $i++) {
                        // Logic to skip weekends
                        do {
                            $currentDate->modify("+$daysAdd day");
                            $dow = $currentDate->format('N'); // 1 (Mon) to 7 (Sun)
                        } while ($loan['modalidad'] === 'Diario' && ($dow == 6 || $dow == 7));

                        // For other modalities, user usually wants simple jumps, but let's ensure not Sunday if Daily?
                        // Usually Daily implies "business days". Weekly usually implies "Same day next week".
                        // Let's stick to simple logic: Daily skips Sat/Sun. Others are fixed intervals.
                
                        $saldo -= $cuotaVal;
                        if ($saldo < 0)
                            $saldo = 0;
                        ?>
                        <tr>
                            <td>
                                <?php echo $i; ?>
                            </td>
                            <td>
                                <?php echo $currentDate->format('d/m/Y'); ?>
                            </td>
                            <td>L
                                <?php echo number_format($cuotaVal, 2); ?>
                            </td>
                            <td>L
                                <?php echo number_format($saldo, 2); ?>
                            </td>
                        </tr>
                        <?php
                        if ($saldo <= 0)
                            break;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</body>

</html>