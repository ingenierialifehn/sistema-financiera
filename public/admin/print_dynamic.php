<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/TextHelper.php';

// Get params
$loanId = $_GET['loan_id'] ?? null;
$templateId = $_GET['template_id'] ?? null;

if (!$loanId || !$templateId) {
    die("Faltan parámetros loan_id o template_id");
}

$db = getDB();

// 1. Get Template
$stmt = $db->prepare("SELECT contenido, nombre FROM plantillas_documentos WHERE id = ?");
$stmt->execute([$templateId]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template)
    die("Plantilla no encontrada");

// 2. Get Data (Loan + Client + Agency)
$sql = "SELECT p.*, 
        c.nombre_completo as nombre_cliente, c.numero_documento as dni_cliente, c.direccion as direccion_cliente, c.telefono as telefono_cliente,
        a.nombre_agencia, a.ciudad as ciudad_agencia, a.direccion as direccion_agencia
        FROM prestamos p
        JOIN clientes c ON p.id_cliente = c.id
        LEFT JOIN agencias a ON c.id_agencia = a.id_agencia
        WHERE p.id = ?";

$stmtData = $db->prepare($sql);
$stmtData->execute([$loanId]);
$data = $stmtData->fetch(PDO::FETCH_ASSOC);

if (!$data)
    die("Préstamo no encontrado");

// 3. Prepare Variables
$vars = [];

// Contract/Loan Identification
$vars['{{numero_contrato}}'] = str_pad($data['id'], 6, '0', STR_PAD_LEFT); // 000013
$vars['{{numero_prestamo}}'] = $data['id']; // 13
$vars['{{id_prestamo}}'] = $data['id']; // 13

// Client
$vars['{{nombre_cliente}}'] = strtoupper($data['nombre_cliente']);
$vars['{{dni_cliente}}'] = $data['dni_cliente'];
$vars['{{direccion_cliente}}'] = $data['direccion_cliente'];
$vars['{{telefono_cliente}}'] = $data['telefono_cliente'];

// Loan - Amounts
$vars['{{monto_prestamo}}'] = number_format($data['monto_capital'], 2);
$vars['{{monto_letras}}'] = TextHelper::numToLetras($data['monto_capital']);
$vars['{{tasa_interes}}'] = $data['tasa_interes'];
$vars['{{tasa_interes_porcentaje}}'] = $data['tasa_interes'] . '%';

// Loan - Term/Period
$vars['{{plazo}}'] = $data['plazo_meses']; // Solo número: 12
$vars['{{plazo_meses}}'] = $data['plazo_meses'] . ' meses'; // 12 meses
$vars['{{plazo_letras}}'] = convertNumberToText($data['plazo_meses']) . ' meses'; // doce meses

// Loan - Installments
$totalCuotas = calculateTotalInstallments($data['plazo_meses'], $data['modalidad']);
$vars['{{total_cuotas}}'] = $totalCuotas; // Solo número: 48
$vars['{{total_cuotas_texto}}'] = $totalCuotas . ' cuotas'; // 48 cuotas
$vars['{{total_cuotas_letras}}'] = convertNumberToText($totalCuotas) . ' cuotas'; // cuarenta y ocho cuotas

// Loan - Payment Frequency/Modality
$modalidadTexto = [
    'diario' => 'Diaria',
    'semanal' => 'Semanal',
    'quincenal' => 'Quincenal',
    'mensual' => 'Mensual'
];
$modalidadKey = strtolower($data['modalidad']);
$vars['{{modalidad}}'] = ucfirst($data['modalidad']); // Semanal
$vars['{{frecuencia}}'] = $modalidadTexto[$modalidadKey] ?? ucfirst($data['modalidad']); // Semanal
$vars['{{frecuencia_minuscula}}'] = strtolower($modalidadTexto[$modalidadKey] ?? $data['modalidad']); // semanal

// Loan - Installment Amount
$vars['{{cuota}}'] = number_format($data['valor_cuota'], 2); // 1300.00
$vars['{{cuota_letras}}'] = TextHelper::numToLetras($data['valor_cuota']); // MIL TRESCIENTOS LEMPIRAS...
$vars['{{valor_cuota}}'] = 'L. ' . number_format($data['valor_cuota'], 2); // L. 1300.00

// Loan - Dates
$vars['{{fecha_desembolso}}'] = $data['fecha_desembolso'] ? date('d/m/Y', strtotime($data['fecha_desembolso'])) : '[FECHA PENDIENTE]';

// Get first installment date from cuotas table
$stmtCuota = $db->prepare("SELECT fecha_vencimiento FROM cuotas WHERE prestamo_id = ? ORDER BY numero_cuota ASC LIMIT 1");
$stmtCuota->execute([$loanId]);
$primeraCuota = $stmtCuota->fetch(PDO::FETCH_ASSOC);

if ($primeraCuota && $primeraCuota['fecha_vencimiento']) {
    $fechaPrimeraCuota = strtotime($primeraCuota['fecha_vencimiento']);
    $vars['{{fecha_primera_cuota}}'] = date('d/m/Y', $fechaPrimeraCuota);
    $vars['{{dia_primera_cuota}}'] = date('d', $fechaPrimeraCuota);
    $vars['{{mes_primera_cuota}}'] = $months[date('n', $fechaPrimeraCuota) - 1];
    $vars['{{anio_primera_cuota}}'] = date('Y', $fechaPrimeraCuota);
} else {
    // Fallback if no cuotas exist yet
    $vars['{{fecha_primera_cuota}}'] = '[PENDIENTE]';
    $vars['{{dia_primera_cuota}}'] = '[PENDIENTE]';
    $vars['{{mes_primera_cuota}}'] = '[PENDIENTE]';
    $vars['{{anio_primera_cuota}}'] = '[PENDIENTE]';
}

// Agency / Date
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');
$currentDate = time();
$vars['{{nombre_agencia}}'] = $data['nombre_agencia'] ?? 'Agencia Central';
$vars['{{ciudad_agencia}}'] = $data['ciudad_agencia'] ?? 'Tegucigalpa';
$vars['{{fecha_actual}}'] = date('d/m/Y');
$vars['{{dia_actual}}'] = date('d');
$vars['{{dia_actual_letras}}'] = convertNumberToText(date('d'));
$vars['{{mes_actual}}'] = strftime('%B', $currentDate);
// Fallback for month
$months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$vars['{{mes_actual}}'] = $months[date('n') - 1];
$vars['{{anio_actual}}'] = date('Y');

// Helper functions
function calculateTotalInstallments($plazoMeses, $modalidad)
{
    $modalidad = strtolower($modalidad);
    switch ($modalidad) {
        case 'diario':
            return $plazoMeses * 30; // Aproximado
        case 'semanal':
            return $plazoMeses * 4; // 4 semanas por mes
        case 'quincenal':
            return $plazoMeses * 2;
        case 'mensual':
            return $plazoMeses;
        default:
            return $plazoMeses;
    }
}

function convertNumberToText($num)
{
    $numbers = [
        1 => 'un',
        2 => 'dos',
        3 => 'tres',
        4 => 'cuatro',
        5 => 'cinco',
        6 => 'seis',
        7 => 'siete',
        8 => 'ocho',
        9 => 'nueve',
        10 => 'diez',
        11 => 'once',
        12 => 'doce',
        13 => 'trece',
        14 => 'catorce',
        15 => 'quince',
        16 => 'dieciséis',
        17 => 'diecisiete',
        18 => 'dieciocho',
        19 => 'diecinueve',
        20 => 'veinte',
        21 => 'veintiuno',
        22 => 'veintidós',
        23 => 'veintitrés',
        24 => 'veinticuatro',
        25 => 'veinticinco',
        26 => 'veintiséis',
        27 => 'veintisiete',
        28 => 'veintiocho',
        29 => 'veintinueve',
        30 => 'treinta',
        36 => 'treinta y seis',
        40 => 'cuarenta',
        48 => 'cuarenta y ocho',
        50 => 'cincuenta',
        60 => 'sesenta',
        72 => 'setenta y dos',
        80 => 'ochenta',
        90 => 'noventa',
        100 => 'cien'
    ];

    return $numbers[$num] ?? (string) $num;
}

// Logo
$logoPath = BASE_URL . '/public/admin/assets/img/logo_empresa.png?v=' . time();

// 4. Replace
$content = $template['contenido'];
foreach ($vars as $key => $val) {
    $content = str_replace($key, $val, $content);
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>
        <?php echo htmlspecialchars($template['nombre']); ?>
    </title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.5;
            padding: 40px;
        }

        .header {
            text-align: right;
            margin-bottom: 20px;
        }

        .logo {
            max-height: 80px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 20px; text-align: center; background: #eee; padding: 10px;">
        <button onclick="window.print()" style="font-size: 16px; padding: 10px 20px; cursor: pointer;">Imprimir
            Documento</button>
    </div>

    <div class="header">
        <img src="<?php echo $logoPath; ?>" alt="Logo" class="logo" onerror="this.style.display='none'">
    </div>

    <div class="content">
        <?php echo $content; ?>
    </div>
</body></html>