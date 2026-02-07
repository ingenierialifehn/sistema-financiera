<?php
/**
 * Estado de Cuenta del Cliente (Vista de Impresión)
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/config/database.php';

AuthMiddleware::requireAuth();

$clienteId = $_GET['cliente_id'] ?? $_GET['id'] ?? null;
$prestamoId = $_GET['prestamo_id'] ?? null;

if (!$clienteId && !$prestamoId) {
    die('ID de cliente o préstamo requerido');
}

$db = getDB();

$prestamos = [];

if ($prestamoId) {
    // Caso 1: Imprimir un solo préstamo
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = ?");
    $stmt->execute([$prestamoId]);
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($prestamos)) {
        die('Préstamo no encontrado');
    }

    // Asignar cliente ID desde el préstamo
    $clienteId = $prestamos[0]['id_cliente'];
} else {
    // Caso 2: Imprimir todos los préstamos del cliente
    $stmt = $db->prepare("
        SELECT * FROM prestamos 
        WHERE id_cliente = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$clienteId]);
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 1. Obtener Datos del Cliente
$stmt = $db->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$clienteId]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die('Cliente no encontrado');
}

// Función auxiliar para calcular detalles del préstamo (lógica de get_detalle.php)
function calcularDetallesPrestamo($prestamo, $db)
{
    // Obtener cuotas
    $stmt = $db->prepare("
        SELECT * FROM cuotas 
        WHERE prestamo_id = ? 
        ORDER BY numero_cuota ASC
    ");
    $stmt->execute([$prestamo['id']]);
    $cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular Capital Restante y Balance
    $capital_pagado = 0;
    $total_pagado = 0;
    $monto_mora = 0;
    $dias_mora = 0;
    $cuotas_pagadas_list = [];

    $hoy = new DateTime();

    foreach ($cuotas as $c) {
        $monto_pagado = floatval($c['monto_pagado'] ?? 0);
        $monto_cuota = floatval($c['monto_cuota']);

        // Calcular Capital Pagado (proporcional)
        if ($monto_pagado > 0 && $monto_cuota > 0) {
            $proporcion = $monto_pagado / $monto_cuota;
            $capital_pagado += floatval($c['capital_cuota'] ?? 0) * $proporcion;
            $total_pagado += $monto_pagado;

            $cuotas_pagadas_list[] = [
                'numero' => $c['numero_cuota'],
                'fecha_pago' => $c['fecha_pago_real'],
                'monto' => $monto_pagado,
                'vencimiento' => $c['fecha_vencimiento']
            ];
        }

        // Calcular Mora
        if ($c['estado'] !== 'pagada') {
            $fecha_venc = new DateTime($c['fecha_vencimiento']);
            $fecha_venc->setTime(0, 0, 0);
            $hoy->setTime(0, 0, 0);

            if ($fecha_venc < $hoy) {
                // Monto pendiente de la cuota
                $pendiente_cuota = $monto_cuota - $monto_pagado;
                $monto_mora += $pendiente_cuota;

                $interval = $hoy->diff($fecha_venc);
                $dias = $interval->days;
                if ($dias > $dias_mora) {
                    $dias_mora = $dias;
                }
            }
        }
    }

    $capital_restante = floatval($prestamo['monto_capital']) - $capital_pagado;
    // Ajuste por si el cálculo flotante da negativo muy pequeño
    if ($capital_restante < 0)
        $capital_restante = 0;

    $balance_pendiente = floatval($prestamo['total_a_pagar'] ?? 0) - $total_pagado;
    if ($balance_pendiente < 0)
        $balance_pendiente = 0;

    return [
        'info' => $prestamo,
        'capital_restante' => $capital_restante,
        'balance_pendiente' => $balance_pendiente,
        'total_pagado' => $total_pagado,
        'dias_mora' => $dias_mora,
        'monto_mora' => $monto_mora,
        'cuotas_pagadas_list' => $cuotas_pagadas_list
    ];
}

$prestamosProcesados = [];
foreach ($prestamos as $p) {
    if (in_array($p['estado'], ['rechazado', 'solicitado']))
        continue; // Opcional: filtrar solicitudes
    $prestamosProcesados[] = calcularDetallesPrestamo($p, $db);
}

// Configuración de visualización
$fechaImpresion = date('d/m/Y H:i A');
$empresa = "SISTEMA FINANCIERA"; // Cambiar por el nombre real de la empresa
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Cuenta -
        <?php echo htmlspecialchars($cliente['nombre_completo']); ?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-after: always;
            }
        }

        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 p-8 print:p-0 print:bg-white text-sm text-gray-800">

    <div class="max-w-4xl mx-auto bg-white p-8 shadow-lg print:shadow-none print:w-full print:max-w-none">

        <!-- Botón Imprimir -->
        <div class="no-print flex justify-end mb-6">
            <button onclick="window.print()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold shadow flex items-center">
                <i class="fas fa-print mr-2"></i> Imprimir Estado de Cuenta
            </button>
        </div>

        <!-- Encabezado -->
        <div class="border-b-2 border-gray-800 pb-4 mb-6 flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold uppercase tracking-wider text-gray-900">Estado de Cuenta</h1>
                <p class="text-gray-500 mt-1"><?php echo htmlspecialchars($empresa); ?></p>
                <p class="text-gray-500 text-xs">Fecha de Emisión:
                    <?php echo $fechaImpresion; ?>
                </p>
            </div>
            <div class="text-right">
                <div class="bg-gray-100 rounded p-2 px-4 inline-block">
                    <p class="text-xs font-bold text-gray-500 uppercase">Cliente</p>
                    <p class="text-lg font-bold text-gray-900">
                        <?php echo htmlspecialchars($cliente['nombre_completo']); ?>
                    </p>
                    <p class="font-mono text-sm">
                        <?php echo htmlspecialchars($cliente['numero_documento']); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Datos del Cliente (Detalle) -->
        <div class="mb-8 p-4 bg-gray-50 rounded border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <span class="block text-xs font-bold text-gray-500 uppercase">Código Cliente</span>
                    <span class="font-semibold">
                        <?php echo $cliente['codigo_cliente'] ?: 'N/A'; ?>
                    </span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-500 uppercase">Teléfono</span>
                    <span class="font-semibold">
                        <?php echo $cliente['telefono'] ?: 'N/A'; ?>
                    </span>
                </div>
                <div class="md:col-span-1">
                    <span class="block text-xs font-bold text-gray-500 uppercase">Dirección</span>
                    <span class="font-semibold text-xs">
                        <?php echo $cliente['direccion'] ?: 'N/A'; ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if (empty($prestamosProcesados)): ?>
            <div class="text-center py-12 border-2 border-dashed border-gray-300 rounded-lg">
                <p class="text-gray-500">No hay préstamos activos o registrados para este cliente.</p>
            </div>
        <?php else: ?>

            <?php foreach ($prestamosProcesados as $index => $data): ?>
                <?php
                $p = $data['info'];
                $cuotasPagadas = $data['cuotas_pagadas_list'];
                // Definir color de estado
                $estadoColor = match ($p['estado']) {
                    'Activo' => 'text-green-700 bg-green-50',
                    'En Mora' => 'text-red-700 bg-red-50',
                    'Finalizado' => 'text-gray-700 bg-gray-50',
                    default => 'text-blue-700 bg-blue-50'
                };
                ?>

                <div
                    class="mb-8 border border-gray-300 rounded-lg overflow-hidden <?php echo ($index > 0) ? 'page-break' : ''; ?>">
                    <!-- Encabezado del Préstamo -->
                    <div class="bg-gray-100 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div>
                            <span class="text-xs font-bold text-gray-500 uppercase">Préstamo</span>
                            <h2 class="text-xl font-bold text-gray-900">#
                                <?php echo $p['id']; ?>
                            </h2>
                        </div>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide <?php echo $estadoColor; ?>">
                            <?php echo $p['estado']; ?>
                        </span>
                    </div>

                    <div class="p-6">
                        <!-- Resumen Financiero -->
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Monto Otorgado</p>
                                <p class="text-lg font-bold text-gray-800">L
                                    <?php echo number_format($p['monto_capital'], 2); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Capital Restante</p>
                                <p class="text-lg font-bold text-blue-700">L
                                    <?php echo number_format($data['capital_restante'], 2); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Total Pagado</p>
                                <p class="text-lg font-bold text-green-700">L
                                    <?php echo number_format($data['total_pagado'], 2); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Balance Pendiente</p>
                                <p class="text-lg font-bold text-orange-700">L
                                    <?php echo number_format($data['balance_pendiente'], 2); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase mb-1">Mora Acumulada</p>
                                <div class="flex flex-col">
                                    <span
                                        class="<?php echo $data['dias_mora'] > 0 ? 'text-red-600 font-bold' : 'text-gray-800'; ?>">
                                        <?php echo $data['dias_mora']; ?> Días
                                    </span>
                                    <?php if ($data['monto_mora'] > 0): ?>
                                        <span class="text-xs text-red-600 font-semibold">L
                                            <?php echo number_format($data['monto_mora'], 2); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Desglose de Pagos -->
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 uppercase border-b border-gray-200 pb-2 mb-4">
                                Desglose de Pagos Realizados
                            </h3>

                            <?php if (empty($cuotasPagadas)): ?>
                                <p class="text-sm text-gray-500 italic py-2">No se han registrado pagos para este préstamo.</p>
                            <?php else: ?>
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 text-left">
                                            <th class="py-2 px-3 font-semibold text-gray-600">Cuota #</th>
                                            <th class="py-2 px-3 font-semibold text-gray-600">Fecha Vencimiento</th>
                                            <th class="py-2 px-3 font-semibold text-gray-600">Fecha Pago</th>
                                            <th class="py-2 px-3 font-semibold text-gray-600 text-right">Monto Pagado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <?php foreach ($cuotasPagadas as $pago): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="py-2 px-3 text-gray-800">
                                                    <?php echo $pago['numero']; ?>
                                                </td>
                                                <td class="py-2 px-3 text-gray-600">
                                                    <?php echo date('d/m/Y', strtotime($pago['vencimiento'])); ?>
                                                </td>
                                                <td class="py-2 px-3 text-gray-800 font-medium">
                                                    <?php echo $pago['fecha_pago'] ? date('d/m/Y H:i', strtotime($pago['fecha_pago'])) : 'N/A'; ?>
                                                </td>
                                                <td class="py-2 px-3 text-green-700 font-bold text-right">
                                                    L
                                                    <?php echo number_format($pago['monto'], 2); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <!-- Fila de Total -->
                                        <tr class="bg-gray-50 font-bold">
                                            <td colspan="3" class="py-3 px-3 text-right text-gray-700 uppercase">Total Pagado</td>
                                            <td class="py-3 px-3 text-right text-green-800">L
                                                <?php echo number_format($data['total_pagado'], 2); ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

        <!-- Footer -->
        <div class="mt-12 border-t pt-4 text-center text-xs text-gray-400">
            <p>Este documento es un comprobante informativo del estado de cuenta.</p>
            <p>Generado por Sistema Financiero v1.0</p>
        </div>

    </div>

    <?php if (isset($_GET['autoprint'])): ?>
        <script>
            window.onload = function () { window.print(); }
        </script>
    <?php endif; ?>
</body>

</html>