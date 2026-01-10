<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    // SEGURIDAD: Validar sesión y obtener id_agencia
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception('Sesión no válida. Por favor, inicie sesión nuevamente.');
    }

    $idAgencia = $_SESSION['id_agencia'] ?? null;

    if (!$idAgencia || empty($idAgencia)) {
        throw new Exception('No se pudo determinar la agencia del usuario. Verifique su perfil.');
    }

    $db = getDB();
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $fechaHoy = date('Y-m-d');

    // Verificar que la agencia existe
    $sqlVerifAgencia = "SELECT nombre_agencia FROM agencias WHERE id_agencia = ?";
    $stmtVerif = $db->prepare($sqlVerifAgencia);
    $stmtVerif->execute([$idAgencia]);
    $nombreAgencia = $stmtVerif->fetchColumn();

    if (!$nombreAgencia) {
        throw new Exception('La agencia asignada no existe en el sistema');
    }

    // CAPITAL TOTAL EN LA CALLE (Solo capital pendiente, sin intereses)
    // Sumamos el capital_cuota de todas las cuotas pendientes de préstamos activos
    $sqlCapitalCalle = "SELECT 
                        IFNULL(SUM(cu.capital_cuota), 0) as capital_calle
                        FROM cuotas cu
                        INNER JOIN prestamos p ON cu.prestamo_id = p.id
                        INNER JOIN clientes c ON p.id_cliente = c.id
                        WHERE cu.estado IN ('pendiente', 'vencida')
                        AND p.estado = 'Activo'
                        AND c.id_agencia = ?
                        AND cu.capital_cuota > 0";

    $stmtCapital = $db->prepare($sqlCapitalCalle);
    $stmtCapital->execute([$idAgencia]);
    $capitalCalle = floatval($stmtCapital->fetchColumn());

    // OBTENER TODOS LOS PRÉSTAMOS ACTIVOS Y CALCULAR RIESGO
    $sqlPrestamos = "SELECT 
                     p.id,
                     p.monto_capital,
                     p.total_a_pagar,
                     c.nombre_completo,
                     (SELECT MIN(fecha_vencimiento) 
                      FROM cuotas 
                      WHERE prestamo_id = p.id 
                      AND estado IN ('pendiente', 'vencida')) as cuota_mas_antigua,
                     (SELECT SUM(capital_cuota) 
                      FROM cuotas 
                      WHERE prestamo_id = p.id 
                      AND estado IN ('pendiente', 'vencida')) as capital_pendiente
                     FROM prestamos p
                     INNER JOIN clientes c ON p.id_cliente = c.id
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
                $diasMora = (int) $diff->days;
            }
        }

        // Determinar categoría según días de mora
        if ($diasMora == 0) {
            $categoria = 'A';  // Al día
        } elseif ($diasMora >= 1 && $diasMora <= 30) {
            $categoria = 'B';  // 1-30 días
        } elseif ($diasMora >= 31 && $diasMora <= 60) {
            $categoria = 'C';  // 31-60 días
        } elseif ($diasMora >= 61 && $diasMora <= 90) {
            $categoria = 'D';  // 61-90 días
        } else {
            $categoria = 'E';  // Más de 90 días
        }

        // Usar solo el capital pendiente (sin intereses)
        $saldoPendiente = floatval($prestamo['capital_pendiente'] ?? 0);

        if ($saldoPendiente > 0) {
            $categoriasData[$categoria][] = [
                'prestamo_id' => $prestamo['id'],
                'nombre_cliente' => $prestamo['nombre_completo'],
                'dias_mora' => $diasMora,
                'saldo_pendiente' => $saldoPendiente
            ];
        }
    }

    // Generar resumen por categorías
    $categorias = [];
    foreach ($categoriasData as $cat => $datos) {
        if (count($datos) > 0) {
            $totalMora = array_sum(array_column($datos, 'dias_mora'));
            $categorias[] = [
                'categoria_riesgo' => $cat,
                'cantidad_clientes' => count($datos),
                'monto_riesgo' => round(array_sum(array_column($datos, 'saldo_pendiente')), 2),
                'promedio_dias_mora' => count($datos) > 0 ? round($totalMora / count($datos), 1) : 0
            ];
        }
    }

    // Asegurar que todas las categorías aparezcan, incluso con 0
    $categoriasCompletas = [];
    foreach (['A', 'B', 'C', 'D', 'E'] as $cat) {
        $encontrada = false;
        foreach ($categorias as $catData) {
            if ($catData['categoria_riesgo'] === $cat) {
                $categoriasCompletas[] = $catData;
                $encontrada = true;
                break;
            }
        }
        if (!$encontrada) {
            $categoriasCompletas[] = [
                'categoria_riesgo' => $cat,
                'cantidad_clientes' => 0,
                'monto_riesgo' => 0,
                'promedio_dias_mora' => 0
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
                (SELECT MIN(fecha_vencimiento) 
                 FROM cuotas 
                 WHERE prestamo_id = p.id 
                 AND estado IN ('pendiente', 'vencida')) as cuota_mas_antigua,
                (SELECT SUM(capital_cuota) 
                 FROM cuotas 
                 WHERE prestamo_id = p.id 
                 AND estado IN ('pendiente', 'vencida')) as capital_pendiente,
                (SELECT MIN(fecha_vencimiento) 
                 FROM cuotas 
                 WHERE prestamo_id = p.id 
                 AND estado IN ('pendiente', 'vencida')
                 AND fecha_vencimiento >= CURDATE()) as proxima_cuota
                FROM prestamos p
                INNER JOIN clientes c ON p.id_cliente = c.id
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
                $diasMora = (int) $diff->days;
            }
        }

        // Solo incluir si tiene más de 30 días de mora
        if ($diasMora > 30) {
            // Determinar categoría
            if ($diasMora >= 31 && $diasMora <= 60) {
                $categoria = 'C';
            } elseif ($diasMora >= 61 && $diasMora <= 90) {
                $categoria = 'D';
            } else {
                $categoria = 'E';
            }

            $saldoPendiente = floatval($pm['capital_pendiente'] ?? 0);

            if ($saldoPendiente > 0) {
                $clientesMora[] = [
                    'nombre_completo' => $pm['nombre_completo'],
                    'numero_documento' => $pm['numero_documento'] ?? 'N/A',
                    'telefono' => $pm['telefono'] ?? 'N/A',
                    'prestamo_id' => $pm['prestamo_id'],
                    'monto_capital' => round(floatval($pm['monto_capital']), 2),
                    'categoria_riesgo' => $categoria,
                    'dias_mora' => $diasMora,
                    'saldo_pendiente' => round($saldoPendiente, 2),
                    'proxima_cuota' => $pm['proxima_cuota']
                ];
            }
        }
    }

    // ... (Código anterior de clientes mora 30+)

    // DESGLOSE POR ASESOR
    $sqlAsesores = "SELECT 
                    p.id,
                    p.monto_capital,
                    c.cobrador_id,
                    u.username as nombre_asesor,
                    u.id_colaborador,
                    (SELECT MIN(fecha_vencimiento) 
                     FROM cuotas 
                     WHERE prestamo_id = p.id 
                     AND estado IN ('pendiente', 'vencida')) as cuota_mas_antigua,
                     (SELECT SUM(capital_cuota) 
                      FROM cuotas 
                      WHERE prestamo_id = p.id 
                      AND estado IN ('pendiente', 'vencida')) as capital_pendiente
                    FROM prestamos p
                    INNER JOIN clientes c ON p.id_cliente = c.id
                    LEFT JOIN usuarios u ON c.cobrador_id = u.id_usuario
                    WHERE p.estado = 'Activo'
                    AND c.id_agencia = ?";

    $stmtAsesores = $db->prepare($sqlAsesores);
    $stmtAsesores->execute([$idAgencia]);
    $prestamosAsesor = $stmtAsesores->fetchAll(PDO::FETCH_ASSOC);

    $asesoresStats = [];

    foreach ($prestamosAsesor as $loan) {
        $cobradorId = $loan['cobrador_id'] ?? '0';
        $nombreAsesor = $loan['nombre_asesor'] ?? 'Sin Asignar';
        $saldo = floatval($loan['capital_pendiente'] ?? 0);

        if ($saldo <= 0)
            continue;

        // Inicializar asesor si no existe
        if (!isset($asesoresStats[$cobradorId])) {
            $asesoresStats[$cobradorId] = [
                'nombre' => $nombreAsesor,
                'total_cartera' => 0,
                'clientes_count' => 0,
                'mora_normal' => 0, // 0 dias
                'mora_1_3' => 0,
                'mora_4_7' => 0,
                'mora_8_14' => 0,
                'mora_15_30' => 0, // Agregado para cobertura completa
                'mora_30_plus' => 0
            ];
        }

        // Determinar días de atraso
        $diasMora = 0;
        if ($loan['cuota_mas_antigua']) {
            $venc = new DateTime($loan['cuota_mas_antigua']);
            $hoy = new DateTime();
            $hoy->setTime(0, 0, 0);
            $venc->setTime(0, 0, 0);

            if ($venc < $hoy) {
                $diff = $hoy->diff($venc);
                $diasMora = (int) $diff->days;
            }
        }

        // Acumulares generales
        $asesoresStats[$cobradorId]['total_cartera'] += $saldo;
        $asesoresStats[$cobradorId]['clientes_count'] += 1; // Simplificación: cuenta préstamos, idealmente unique clientes

        // Buckets
        if ($diasMora == 0) {
            $asesoresStats[$cobradorId]['mora_normal'] += $saldo;
        } elseif ($diasMora >= 1 && $diasMora <= 3) {
            $asesoresStats[$cobradorId]['mora_1_3'] += $saldo;
        } elseif ($diasMora >= 4 && $diasMora <= 7) {
            $asesoresStats[$cobradorId]['mora_4_7'] += $saldo;
        } elseif ($diasMora >= 8 && $diasMora <= 14) {
            $asesoresStats[$cobradorId]['mora_8_14'] += $saldo;
        } elseif ($diasMora >= 15 && $diasMora <= 30) {
            $asesoresStats[$cobradorId]['mora_15_30'] += $saldo;
        } else {
            $asesoresStats[$cobradorId]['mora_30_plus'] += $saldo;
        }
    }

    // Convertir a array indexado y redondear
    $desgloseAsesores = [];
    $consolidado = [
        'nombre' => 'TOTAL AGENCIA',
        'es_total' => true,
        'total_cartera' => 0,
        'clientes_count' => 0,
        'porcentaje_mora' => 0, // Inicializar
        'mora_normal' => 0,
        'mora_1_3' => 0,
        'mora_4_7' => 0,
        'mora_8_14' => 0,
        'mora_15_30' => 0,
        'mora_30_plus' => 0
    ];

    foreach ($asesoresStats as $stat) {
        // Calcular porcentaje de mora (Todo lo que NO es normal)
        $carteraVencida = $stat['total_cartera'] - $stat['mora_normal'];
        $porcentajeMora = 0;
        $porcentajeNormalidad = 0;

        if ($stat['total_cartera'] > 0) {
            $porcentajeMora = ($carteraVencida / $stat['total_cartera']) * 100;
            $porcentajeNormalidad = ($stat['mora_normal'] / $stat['total_cartera']) * 100;
        }
        $stat['porcentaje_mora'] = round($porcentajeMora, 2);
        $stat['porcentaje_normalidad'] = round($porcentajeNormalidad, 2);

        $stat['total_cartera'] = round($stat['total_cartera'], 2);
        $stat['mora_normal'] = round($stat['mora_normal'], 2);
        $stat['mora_1_3'] = round($stat['mora_1_3'], 2);
        $stat['mora_4_7'] = round($stat['mora_4_7'], 2);
        $stat['mora_8_14'] = round($stat['mora_8_14'], 2);
        $stat['mora_15_30'] = round($stat['mora_15_30'], 2);
        $stat['mora_30_plus'] = round($stat['mora_30_plus'], 2);

        $desgloseAsesores[] = $stat;

        // Sumar al consolidado
        $consolidado['total_cartera'] += $stat['total_cartera'];
        $consolidado['clientes_count'] += $stat['clientes_count'];
        $consolidado['mora_normal'] += $stat['mora_normal'];
        $consolidado['mora_1_3'] += $stat['mora_1_3'];
        $consolidado['mora_4_7'] += $stat['mora_4_7'];
        $consolidado['mora_8_14'] += $stat['mora_8_14'];
        $consolidado['mora_15_30'] += $stat['mora_15_30'];
        $consolidado['mora_30_plus'] += $stat['mora_30_plus'];
    }

    // Calcular % Mora Global del Consolidado
    $carteraVencidaTotal = $consolidado['total_cartera'] - $consolidado['mora_normal'];
    if ($consolidado['total_cartera'] > 0) {
        $consolidado['porcentaje_mora'] = round(($carteraVencidaTotal / $consolidado['total_cartera']) * 100, 2);
        $consolidado['porcentaje_normalidad'] = round(($consolidado['mora_normal'] / $consolidado['total_cartera']) * 100, 2);
    } else {
        $consolidado['porcentaje_mora'] = 0;
        $consolidado['porcentaje_normalidad'] = 0;
    }

    // Agregar consolidado al final
    $desgloseAsesores[] = $consolidado;

    // Ordenar por días de mora descendente (el sort anterior de clientesMora)
    usort($clientesMora, function ($a, $b) {
        return $b['dias_mora'] - $a['dias_mora'];
    });

    echo json_encode([
        'success' => true,
        'data' => [
            'fecha' => $fechaHoy,
            'agencia' => $nombreAgencia,
            'id_agencia' => $idAgencia,
            'capital_calle' => round($capitalCalle, 2),
            'categorias' => $categoriasCompletas,
            'clientes_mora' => $clientesMora,
            'total_clientes_mora' => count($clientesMora),
            'desglose_asesores' => $desgloseAsesores // Nuevo campo
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_detail' => 'Error en estado_cartera.php'
    ], JSON_PRETTY_PRINT);
}
?>