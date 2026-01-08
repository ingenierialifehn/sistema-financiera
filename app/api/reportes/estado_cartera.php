<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    // SEGURIDAD: Obtener id_agencia de la sesión
    $idAgencia = $_SESSION['id_agencia'] ?? null;

    if (!$idAgencia) {
        throw new Exception('No se pudo determinar la agencia del usuario');
    }

    $db = getDB();
    $fechaHoy = date('Y-m-d');

    // CAPITAL TOTAL EN LA CALLE (Solo capital pendiente, sin intereses)
    // Sumamos el capital_cuota de todas las cuotas pendientes
    $sqlCapitalCalle = "SELECT 
                        IFNULL(SUM(cu.capital_cuota), 0) as capital_calle
                        FROM cuotas cu
                        JOIN prestamos p ON cu.prestamo_id = p.id
                        JOIN clientes c ON p.id_cliente = c.id
                        WHERE cu.estado != 'pagada'
                        AND p.estado = 'Activo'
                        AND c.id_agencia = ?";

    $stmtCapital = $db->prepare($sqlCapitalCalle);
    $stmtCapital->execute([$idAgencia]);
    $capitalCalle = floatval($stmtCapital->fetchColumn());

    // OBTENER TODOS LOS PRÉSTAMOS ACTIVOS Y CALCULAR RIESGO
    $sqlPrestamos = "SELECT 
                     p.id,
                     p.monto_capital,
                     p.total_a_pagar,
                     (SELECT MIN(fecha_vencimiento) FROM cuotas WHERE prestamo_id = p.id AND estado != 'pagada') as cuota_mas_antigua,
                     (SELECT SUM(capital_cuota) FROM cuotas WHERE prestamo_id = p.id AND estado != 'pagada') as capital_pendiente
                     FROM prestamos p
                     JOIN clientes c ON p.id_cliente = c.id
                     WHERE p.estado = 'Activo'
                     AND c.id_agencia = ?";

    $stmtPrestamos = $db->prepare($sqlPrestamos);
    $stmtPrestamos->execute([$idAgencia]);
    $prestamos = $stmtPrestamos->fetchAll(PDO::FETCH_ASSOC);

    // Calcular categoría de riesgo para cada préstamo
    $categoriasData = ['A' => [], 'B' => [], 'C' => [], 'D' => [], 'E' => []];

    foreach ($prestamos as $prestamo) {
        $diasMora = 0;
        if ($prestamo['cuota_mas_antigua']) {
            $venc = new DateTime($prestamo['cuota_mas_antigua']);
            $hoy = new DateTime();
            $hoy->setTime(0, 0, 0);
            $venc->setTime(0, 0, 0);

            if ($venc < $hoy) {
                $diff = $hoy->diff($venc);
                $diasMora = $diff->days;
            }
        }

        // Determinar categoría
        if ($diasMora == 0) {
            $categoria = 'A';
        } elseif ($diasMora <= 30) {
            $categoria = 'B';
        } elseif ($diasMora <= 60) {
            $categoria = 'C';
        } elseif ($diasMora <= 90) {
            $categoria = 'D';
        } else {
            $categoria = 'E';
        }

        // Usar solo el capital pendiente (sin intereses)
        $saldoPendiente = floatval($prestamo['capital_pendiente'] ?? 0);

        $categoriasData[$categoria][] = [
            'prestamo_id' => $prestamo['id'],
            'dias_mora' => $diasMora,
            'saldo_pendiente' => $saldoPendiente
        ];
    }

    // Generar resumen por categorías
    $categorias = [];
    foreach ($categoriasData as $cat => $datos) {
        if (count($datos) > 0) {
            $totalMora = array_sum(array_column($datos, 'dias_mora'));
            $categorias[] = [
                'categoria_riesgo' => $cat,
                'cantidad_clientes' => count($datos),
                'monto_riesgo' => array_sum(array_column($datos, 'saldo_pendiente')),
                'promedio_dias_mora' => count($datos) > 0 ? $totalMora / count($datos) : 0
            ];
        }
    }

    // CLIENTES CON MÁS DE 30 DÍAS DE ATRASO (Categorías C, D, E)
    $sqlMora = "SELECT 
                c.nombre_completo,
                c.numero_documento,
                c.telefono,
                p.id as prestamo_id,
                p.monto_capital,
                p.total_a_pagar,
                (SELECT MIN(fecha_vencimiento) FROM cuotas WHERE prestamo_id = p.id AND estado != 'pagada') as cuota_mas_antigua,
                (SELECT SUM(capital_cuota) FROM cuotas WHERE prestamo_id = p.id AND estado != 'pagada') as capital_pendiente,
                (SELECT MIN(fecha_vencimiento) FROM cuotas WHERE prestamo_id = p.id AND estado != 'pagada') as proxima_cuota
                FROM prestamos p
                JOIN clientes c ON p.id_cliente = c.id
                WHERE p.estado = 'Activo'
                AND c.id_agencia = ?";

    $stmtMora = $db->prepare($sqlMora);
    $stmtMora->execute([$idAgencia]);
    $prestamosMora = $stmtMora->fetchAll(PDO::FETCH_ASSOC);

    $clientesMora = [];
    foreach ($prestamosMora as $pm) {
        $diasMora = 0;
        if ($pm['cuota_mas_antigua']) {
            $venc = new DateTime($pm['cuota_mas_antigua']);
            $hoy = new DateTime();
            $hoy->setTime(0, 0, 0);
            $venc->setTime(0, 0, 0);

            if ($venc < $hoy) {
                $diff = $hoy->diff($venc);
                $diasMora = $diff->days;
            }
        }

        // Solo incluir si tiene más de 30 días de mora
        if ($diasMora > 30) {
            // Determinar categoría
            if ($diasMora <= 60) {
                $categoria = 'C';
            } elseif ($diasMora <= 90) {
                $categoria = 'D';
            } else {
                $categoria = 'E';
            }

            $clientesMora[] = [
                'nombre_completo' => $pm['nombre_completo'],
                'numero_documento' => $pm['numero_documento'],
                'telefono' => $pm['telefono'],
                'prestamo_id' => $pm['prestamo_id'],
                'monto_capital' => $pm['monto_capital'],
                'categoria_riesgo' => $categoria,
                'dias_mora' => $diasMora,
                'saldo_pendiente' => floatval($pm['capital_pendiente'] ?? 0),
                'proxima_cuota' => $pm['proxima_cuota']
            ];
        }
    }

    // Ordenar por días de mora descendente
    usort($clientesMora, function ($a, $b) {
        return $b['dias_mora'] - $a['dias_mora'];
    });

    // Obtener nombre de la agencia
    $sqlAgencia = "SELECT nombre_agencia FROM agencias WHERE id_agencia = ?";
    $stmtAgencia = $db->prepare($sqlAgencia);
    $stmtAgencia->execute([$idAgencia]);
    $nombreAgencia = $stmtAgencia->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'fecha' => $fechaHoy,
            'agencia' => $nombreAgencia,
            'capital_calle' => $capitalCalle,
            'categorias' => $categorias,
            'clientes_mora' => $clientesMora
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>