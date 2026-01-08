<?php
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/core/Auth.php';
require_once __DIR__ . '/../../../app/core/ClienteHelper.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();
Auth::checkSession();
$user = Auth::getCurrentUser();

$agenciaId = $_GET['agencia_id'] ?? 'todas';

// Permission Check
$canViewAll = (stripos($user['rol_nombre'], 'Administrador') !== false || stripos($user['rol_nombre'], 'Gerente') !== false);
if (!$canViewAll) {
    $agenciaId = $_SESSION['id_agencia'];
}

$db = getDB();

// Build Query
$whereClause = "";
$params = [];

if ($agenciaId !== 'todas') {
    // Get Agency Name
    $stmt = $db->prepare("SELECT nombre_agencia FROM agencias WHERE id_agencia = ?");
    $stmt->execute([$agenciaId]);
    $agName = $stmt->fetchColumn();
    $nombreAgencia = $agName ?: "Agencia Seleccionada";

    $whereClause = " AND c.id_agencia = ?";
    $params[] = $agenciaId;
} else {
    $nombreAgencia = "Todas las Agencias";
}

$sql = "
    SELECT 
        p.id as prestamo_id,
        c.id as cliente_id,
        c.nombre_completo,
        c.numero_documento,
        c.direccion,
        p.monto_capital,
        p.modalidad,
        p.plazo_meses,
        
        -- Calculated Fields via Subqueries
        
        -- Saldo Actual (Balance Total)
        (p.total_a_pagar - (SELECT IFNULL(SUM(monto_pagado),0) FROM cuotas WHERE prestamo_id = p.id)) as saldo_pendiente
        
    FROM prestamos p
    JOIN clientes c ON p.id_cliente = c.id
    WHERE p.estado = 'Activo'
    $whereClause
    ORDER BY c.nombre_completo ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$cartera = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Risk in PHP
foreach ($cartera as &$row) {
    $riesgo = ClienteHelper::calcularCategoriaRiesgo($db, $row['cliente_id']);
    $row['categoria'] = $riesgo['categoria'];
    $row['dias_mora'] = $riesgo['dias_mora'];
}
unset($row);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Estado de Cartera</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                -webkit-print-color-adjust: exact;
            }

            table {
                font-size: 10px;
            }
        }
    </style>
</head>

<body class="bg-gray-100 p-8">

    <div class="max-w-6xl mx-auto bg-white p-8 shadow-lg">
        <!-- Header -->
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Estado de Cartera de Créditos</h1>
                <p class="text-gray-600">
                    <?php echo $nombreAgencia; ?>
                </p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500">Fecha de Corte</p>
                <p class="text-lg font-bold">
                    <?php echo date('d/m/Y'); ?>
                </p>
            </div>
        </div>

        <!-- Table -->
        <table class="w-full text-xs text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b border-gray-300">
                    <th class="p-2">Cliente</th>
                    <th class="p-2">DNI</th>
                    <th class="p-2">Préstamo</th>
                    <th class="p-2 text-right">Monto Original</th>
                    <th class="p-2 text-right">Saldo Pendiente</th>
                    <th class="p-2 text-center">Riesgo</th>
                    <th class="p-2 text-center">Días Mora</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalCapital = 0;
                $totalSaldo = 0;
                if (empty($cartera)): ?>
                    <tr>
                        <td colspan="7" class="p-4 text-center text-gray-500 italic">No hay créditos activos en este
                            reporte.</td>
                    </tr>
                <?php else:
                    foreach ($cartera as $c):
                        $totalCapital += $c['monto_capital'];
                        $totalSaldo += $c['saldo_pendiente'];

                        // Risk Color
                        $bgClass = '';
                        if ($c['categoria'] == 'A')
                            $bgClass = 'bg-green-50 text-green-700';
                        elseif ($c['categoria'] == 'B')
                            $bgClass = 'bg-yellow-50 text-yellow-700 font-bold';
                        elseif ($c['categoria'] == 'C')
                            $bgClass = 'bg-orange-50 text-orange-700 font-bold';
                        else
                            $bgClass = 'bg-red-50 text-red-700 font-bold';
                        ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="p-2 font-bold">
                                <?php echo $c['nombre_completo']; ?>
                            </td>
                            <td class="p-2">
                                <?php echo $c['numero_documento']; ?>
                            </td>
                            <td class="p-2">#
                                <?php echo $c['prestamo_id']; ?> (
                                <?php echo $c['modalidad']; ?>)
                            </td>
                            <td class="p-2 text-right">L
                                <?php echo number_format($c['monto_capital'], 2); ?>
                            </td>
                            <td class="p-2 text-right font-medium">L
                                <?php echo number_format($c['saldo_pendiente'], 2); ?>
                            </td>
                            <td class="p-2 text-center">
                                <span class="px-2 py-0.5 rounded <?php echo $bgClass; ?>">
                                    <?php echo $c['categoria']; ?>
                                </span>
                            </td>
                            <td class="p-2 text-center">
                                <?php echo $c['dias_mora']; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>

                <!-- Totales -->
                <tr class="bg-gray-50 font-bold border-t-2 border-gray-300">
                    <td colspan="3" class="p-3 text-right">TOTALES</td>
                    <td class="p-3 text-right">L
                        <?php echo number_format($totalCapital, 2); ?>
                    </td>
                    <td class="p-3 text-right">L
                        <?php echo number_format($totalSaldo, 2); ?>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        <div class="mt-4 text-xs text-gray-500">
            Total de Clientes:
            <?php echo count($cartera); ?>
        </div>

        <div class="mt-8 text-center no-print">
            <button onclick="window.print()"
                class="bg-teal-600 text-white px-6 py-2 rounded font-bold hover:bg-teal-700">
                <i class="fas fa-print mr-2"></i> Imprimir Reporte
            </button>
        </div>
    </div>
</body>

</html>