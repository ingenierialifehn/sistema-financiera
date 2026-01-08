<?php
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/core/Auth.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();
Auth::checkSession();
$user = Auth::getCurrentUser();

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$agenciaId = $_GET['agencia_id'] ?? 'todas';

// Permission Check
$canViewAll = (stripos($user['rol_nombre'], 'Administrador') !== false || stripos($user['rol_nombre'], 'Gerente') !== false);
if (!$canViewAll) {
    $agenciaId = $_SESSION['id_agencia'];
}

$db = getDB();

// Build Query Logic
$whereAgencia = "";
$params = [];

// 1. Fetch Agency Info
$agenciaNombre = "Todas las Agencias";
if ($agenciaId !== 'todas') {
    $stmt = $db->prepare("SELECT nombre_agencia FROM agencias WHERE id_agencia = ?");
    $stmt->execute([$agenciaId]);
    $agName = $stmt->fetchColumn();
    if ($agName)
        $agenciaNombre = $agName;

    // We filter by client agency for all ops
    $whereAgencia = " AND c.id_agencia = ?";
}

// 2. Fetch INGRESOS (Pagos)
$sqlIngresos = "SELECT 
        SUM(cu.monto_pagado) as total_ingreso,
        COUNT(cu.id) as cantidad_pagos
    FROM cuotas cu
    JOIN prestamos p ON cu.prestamo_id = p.id
    JOIN clientes c ON p.id_cliente = c.id
    WHERE DATE(cu.fecha_pago_real) = ? 
    AND cu.monto_pagado > 0
    " . $whereAgencia;

$paramsIngresos = [$fecha];
if ($agenciaId !== 'todas')
    $paramsIngresos[] = $agenciaId;

$stmtIng = $db->prepare($sqlIngresos);
$stmtIng->execute($paramsIngresos);
$ingresos = $stmtIng->fetch(PDO::FETCH_ASSOC);

// 3. Fetch EGRESOS (Desembolsos)
$sqlEgresos = "SELECT 
        SUM(p.monto_capital) as total_desembolso,
        COUNT(p.id) as cantidad_creditos
    FROM prestamos p
    JOIN clientes c ON p.id_cliente = c.id
    WHERE DATE(p.fecha_desembolso) = ?
    " . $whereAgencia; // Reusing variable

$paramsEgresos = [$fecha];
if ($agenciaId !== 'todas')
    $paramsEgresos[] = $agenciaId;

$stmtEgr = $db->prepare($sqlEgresos);
$stmtEgr->execute($paramsEgresos);
$egresos = $stmtEgr->fetch(PDO::FETCH_ASSOC);

$totalIngresos = floatval($ingresos['total_ingreso'] ?? 0);
$totalEgresos = floatval($egresos['total_desembolso'] ?? 0);
$balance = $totalIngresos - $totalEgresos;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Cierre Diario -
        <?php echo $fecha; ?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body class="bg-gray-100 p-8">

    <div class="max-w-4xl mx-auto bg-white p-8 shadow-lg">
        <!-- Header -->
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Cierre de Caja Diario</h1>
                <p class="text-gray-600">
                    <?php echo $agenciaNombre; ?>
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Fecha</p>
                <p class="text-xl font-bold">
                    <?php echo date('d/m/Y', strtotime($fecha)); ?>
                </p>
            </div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-green-50 p-4 rounded border border-green-200">
                <p class="text-xs uppercase font-bold text-green-800">Total Ingresos</p>
                <p class="text-2xl font-bold text-green-700">L
                    <?php echo number_format($totalIngresos, 2); ?>
                </p>
                <p class="text-xs text-green-600">
                    <?php echo $ingresos['cantidad_pagos']; ?> Transacciones
                </p>
            </div>
            <div class="bg-red-50 p-4 rounded border border-red-200">
                <p class="text-xs uppercase font-bold text-red-800">Total Egresos</p>
                <p class="text-2xl font-bold text-red-700">L
                    <?php echo number_format($totalEgresos, 2); ?>
                </p>
                <p class="text-xs text-red-600">
                    <?php echo $egresos['cantidad_creditos']; ?> Créditos
                </p>
            </div>
            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                <p class="text-xs uppercase font-bold text-gray-800">Balance Neto</p>
                <p class="text-2xl font-bold <?php echo $balance >= 0 ? 'text-blue-700' : 'text-red-700'; ?>">
                    L
                    <?php echo number_format($balance, 2); ?>
                </p>
            </div>
        </div>

        <!-- Detail Table -->
        <div class="mb-4">
            <h3 class="font-bold text-gray-700 mb-2 border-b pb-1">Desglose General</h3>
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="text-left p-2">Concepto</th>
                        <th class="text-right p-2">Cantidad</th>
                        <th class="text-right p-2">Monto Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b">
                        <td class="p-2">Recaudación (Pagos / Abonos)</td>
                        <td class="text-right p-2">
                            <?php echo $ingresos['cantidad_pagos']; ?>
                        </td>
                        <td class="text-right p-2 font-bold text-green-600">L
                            <?php echo number_format($totalIngresos, 2); ?>
                        </td>
                    </tr>
                    <tr class="border-b">
                        <td class="p-2">Colocación (Desembolsos)</td>
                        <td class="text-right p-2">
                            <?php echo $egresos['cantidad_creditos']; ?>
                        </td>
                        <td class="text-right p-2 font-bold text-red-600">L
                            <?php echo number_format($totalEgresos, 2); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Signatures -->
        <div class="mt-12 grid grid-cols-2 gap-8 pt-8 border-t">
            <div class="text-center">
                <div class="border-b border-gray-300 w-2/3 mx-auto mb-2"></div>
                <p class="text-sm font-bold">Gerente de Agencia</p>
            </div>
            <div class="text-center">
                <div class="border-b border-gray-300 w-2/3 mx-auto mb-2"></div>
                <p class="text-sm font-bold">Cajero / Operaciones</p>
            </div>
        </div>

        <div class="mt-8 text-center no-print">
            <button onclick="window.print()"
                class="bg-indigo-600 text-white px-6 py-2 rounded font-bold hover:bg-indigo-700">
                <i class="fas fa-print mr-2"></i> Imprimir Reporte
            </button>
        </div>
    </div>
</body>

</html>