<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    // SEGURIDAD: Validar sesión (Administrador)
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception('Sesión no válida. Por favor, inicie sesión nuevamente.');
    }

    $agenciaId = $_GET['agencia_id'] ?? 'todas';

    $db = getDB();
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $fechaHoy = date('Y-m-d');

    // Determinar filtro de agencia
    $filtroAgenciaSql = "";
    $params = [];
    $nombreAgencia = "CONSOLIDADO (TODAS LAS AGENCIAS)";

    if ($agenciaId !== 'todas' && is_numeric($agenciaId)) {
        $filtroAgenciaSql = "AND c.id_agencia = ?";
        $params[] = $agenciaId;

        $stmt = $db->prepare("SELECT nombre_agencia FROM agencias WHERE id_agencia = ?");
        $stmt->execute([$agenciaId]);
        $nombreAgencia = $stmt->fetchColumn() ?: "Agencia Desconocida";
    }

    // CAPITAL TOTAL EN LA CALLE
    $sqlCapitalCalle = "SELECT 
                        IFNULL(SUM(cu.capital_cuota), 0) as capital_calle
                        FROM cuotas cu
                        INNER JOIN prestamos p ON cu.prestamo_id = p.id
                        INNER JOIN clientes c ON p.id_cliente = c.id
                        WHERE cu.estado IN ('pendiente', 'vencida')
                        AND p.estado = 'Activo'
                        $filtroAgenciaSql
                        AND cu.capital_cuota > 0";

    $stmtCapital = $db->prepare($sqlCapitalCalle);
    $stmtCapital->execute($params);
    $capitalCalle = floatval($stmtCapital->fetchColumn());

    // CONTEO POR MODALIDAD EN CARTERA ACTIVA
    $sqlModalidades = "SELECT 
                       SUM(CASE WHEN p.modalidad = 'Diario' THEN 1 ELSE 0 END) as diario,
                       SUM(CASE WHEN p.modalidad = 'Semanal' THEN 1 ELSE 0 END) as semanal,
                       SUM(CASE WHEN p.modalidad = 'Catorcenal' THEN 1 ELSE 0 END) as catorcenal,
                       SUM(CASE WHEN p.modalidad = 'Mensual' THEN 1 ELSE 0 END) as mensual
                       FROM prestamos p
                       INNER JOIN clientes c ON p.id_cliente = c.id
                       WHERE p.estado = 'Activo'
                       $filtroAgenciaSql";

    $stmtMod = $db->prepare($sqlModalidades);
    $stmtMod->execute($params);
    $modalidadesStats = $stmtMod->fetch(PDO::FETCH_ASSOC);

    $modalidadesStats = [
        'diario' => intval($modalidadesStats['diario'] ?? 0),
        'semanal' => intval($modalidadesStats['semanal'] ?? 0),
        'catorcenal' => intval($modalidadesStats['catorcenal'] ?? 0),
        'mensual' => intval($modalidadesStats['mensual'] ?? 0)
    ];

    // OBTENER TODOS LOS PRÉSTAMOS ACTIVOS Y CALCULAR RIESGO
    $sqlPrestamos = "SELECT 
                     p.id,
                     p.monto_capital,
                     p.total_a_pagar,
                     c.nombre_completo,
                     ag.nombre_agencia,
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
                     LEFT JOIN agencias ag ON c.id_agencia = ag.id_agencia
                     WHERE p.estado = 'Activo'
                     $filtroAgenciaSql";

    $stmtPrestamos = $db->prepare($sqlPrestamos);
    $stmtPrestamos->execute($params);
    $prestamos = $stmtPrestamos->fetchAll(PDO::FETCH_ASSOC);

    // Calcular categoría de riesgo
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

        if ($diasMora == 0) {
            $categoria = 'A';
        } elseif ($diasMora >= 1 && $diasMora <= 30) {
            $categoria = 'B';
        } elseif ($diasMora >= 31 && $diasMora <= 60) {
            $categoria = 'C';
        } elseif ($diasMora >= 61 && $diasMora <= 90) {
            $categoria = 'D';
        } else {
            $categoria = 'E';
        }

        $saldoPendiente = floatval($prestamo['capital_pendiente'] ?? 0);

        if ($saldoPendiente > 0) {
            $categoriasData[$categoria][] = [
                'prestamo_id' => $prestamo['id'],
                'nombre_cliente' => $prestamo['nombre_completo'],
                'agencia' => $prestamo['nombre_agencia'] ?? 'N/A',
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

    // Asegurar que todas las categorías aparezcan
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

    // CLIENTES CON MÁS DE 30 DÍAS DE ATRASO
    $sqlMora = "SELECT 
                c.nombre_completo,
                c.numero_documento,
                c.telefono,
                p.id as prestamo_id,
                p.monto_capital,
                p.total_a_pagar,
                ag.nombre_agencia,
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
                LEFT JOIN agencias ag ON c.id_agencia = ag.id_agencia
                WHERE p.estado = 'Activo'
                $filtroAgenciaSql";

    $stmtMora = $db->prepare($sqlMora);
    $stmtMora->execute($params);
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

        if ($diasMora > 30) {
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
                    'agencia' => $pm['nombre_agencia'] ?? 'N/A'
                ];
            }
        }
    }

    // DESGLOSE POR ASESOR
    $sqlAsesores = "SELECT 
                    p.id,
                    p.monto_capital,
                    p.estado,
                    c.cobrador_id,
                    u.username as nombre_asesor,
                    ag.nombre_agencia,
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
                    LEFT JOIN agencias ag ON c.id_agencia = ag.id_agencia
                    LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
                    WHERE p.estado NOT IN ('Rechazado', 'Pagado', 'Cancelado', 'Eliminado', 'Finalizado')
                    AND col.puesto_cargo = 'Asesor de Créditos'
                    $filtroAgenciaSql";

    $stmtAsesores = $db->prepare($sqlAsesores);
    $stmtAsesores->execute($params);
    $prestamosAsesor = $stmtAsesores->fetchAll(PDO::FETCH_ASSOC);

    $asesoresStats = [];

    // --- LÓGICA DE AGRUPACIÓN DINÁMICA ---
    // Si es "Todas", agrupamos por AGENCIA. Si es una específica, agrupamos por ASESOR.
    $agruparPorAgencia = ($agenciaId === 'todas');

    if ($agruparPorAgencia) {
        // 1. CARGA INICIAL: TODAS LAS AGENCIAS ACTIVAS
        $stmtAgencias = $db->query("SELECT nombre_agencia FROM agencias WHERE estado = 'Activa'");
        $allAgencias = $stmtAgencias->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allAgencias as $ag) {
            $nombre = $ag['nombre_agencia'];
            // Clave: Nombre de la agencia
            $asesoresStats[$nombre] = [
                'nombre' => $nombre,     // En vista "Todas", esta columna será la Agencia
                'agencia' => '',         // Dejamos vacío o repetimos, visualmente no importa tanto si el header cambia
                'total_cartera' => 0,
                'clientes_count' => 0,
                'clientes_tramite' => 0,
                'mora_normal' => 0,
                'mora_1_3' => 0,
                'mora_4_7' => 0,
                'mora_8_14' => 0,
                'mora_15_30' => 0,
                'mora_30_plus' => 0
            ];
        }

    } else {
        // 1. CARGA INICIAL: ASESORES DE LA AGENCIA SELECCIONADA
        $sqlUsuarios = "SELECT 
                        u.id_usuario, 
                        u.username,
                        ag.nombre_agencia
                        FROM usuarios u
                        LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
                        LEFT JOIN agencias ag ON c.id_agencia = ag.id_agencia
                        WHERE u.estado = 'Activo'
                        AND c.puesto_cargo = 'Asesor de Créditos'";

        $paramsUsuarios = [];
        // Filtro estricto por agencia si no es todas
        $sqlUsuarios .= " AND c.id_agencia = ?";
        $paramsUsuarios[] = $agenciaId;

        $stmtUsers = $db->prepare($sqlUsuarios);
        $stmtUsers->execute($paramsUsuarios);
        $allUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allUsers as $usr) {
            $agenciaNombre = $usr['nombre_agencia'] ?? 'N/A';
            $key = $usr['id_usuario'] . '_' . $agenciaNombre;

            if (!isset($asesoresStats[$key])) {
                $asesoresStats[$key] = [
                    'nombre' => $usr['username'],
                    'agencia' => $agenciaNombre,
                    'total_cartera' => 0,
                    'clientes_count' => 0,
                    'clientes_tramite' => 0,
                    'mora_normal' => 0,
                    'mora_1_3' => 0,
                    'mora_4_7' => 0,
                    'mora_8_14' => 0,
                    'mora_15_30' => 0,
                    'mora_30_plus' => 0
                ];
            }
        }
    }

    // 2. PROCESAR PRESTAMOS Y SUMAR EN LA BOLSA CORRESPONDIENTE
    foreach ($prestamosAsesor as $loan) {
        $cobradorId = $loan['cobrador_id'] ?? '0';
        $nombreAsesor = $loan['nombre_asesor'] ?? 'Sin Asignar';
        $agenciaAsesor = $loan['nombre_agencia'] ?? 'N/A';
        $saldo = floatval($loan['capital_pendiente'] ?? 0);
        $estado = $loan['estado'];

        // Si es Activo y no tiene saldo, lo ignoramos (ya pagado)
        if ($estado === 'Activo' && $saldo <= 0) {
            continue;
        }

        // Si no es activo, el saldo para el reporte financiero es 0
        if ($estado !== 'Activo') {
            $saldo = 0;
        }

        // --- DEFINIR CLAVE DE AGRUPACIÓN ---
        if ($agruparPorAgencia) {
            // Agrupamos por nombre de agencia
            $key = $agenciaAsesor;
            // Si la agencia de este préstamo no estaba en la lista inicial (ej. inactiva), la creamos al vuelo
            if (!isset($asesoresStats[$key])) {
                $asesoresStats[$key] = [
                    'nombre' => $agenciaAsesor,
                    'agencia' => '',
                    'total_cartera' => 0,
                    'clientes_count' => 0,
                    'clientes_tramite' => 0,
                    'mora_normal' => 0,
                    'mora_1_3' => 0,
                    'mora_4_7' => 0,
                    'mora_8_14' => 0,
                    'mora_15_30' => 0,
                    'mora_30_plus' => 0
                ];
            }
        } else {
            // Agrupamos por Asesor
            $key = $cobradorId . '_' . $agenciaAsesor;
            if (!isset($asesoresStats[$key])) {
                $asesoresStats[$key] = [
                    'nombre' => $nombreAsesor,
                    'agencia' => $agenciaAsesor,
                    'total_cartera' => 0,
                    'clientes_count' => 0,
                    'clientes_tramite' => 0,
                    'mora_normal' => 0,
                    'mora_1_3' => 0,
                    'mora_4_7' => 0,
                    'mora_8_14' => 0,
                    'mora_15_30' => 0,
                    'mora_30_plus' => 0
                ];
            }
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

        // --- LÓGICA DE CONTEO DIFERENCIADA ---
        if ($estado === 'Activo') {
            $asesoresStats[$key]['total_cartera'] += $saldo;
            $asesoresStats[$key]['clientes_count'] += 1;

            if ($diasMora == 0) {
                $asesoresStats[$key]['mora_normal'] += $saldo;
            } elseif ($diasMora >= 1 && $diasMora <= 3) {
                $asesoresStats[$key]['mora_1_3'] += $saldo;
            } elseif ($diasMora >= 4 && $diasMora <= 7) {
                $asesoresStats[$key]['mora_4_7'] += $saldo;
            } elseif ($diasMora >= 8 && $diasMora <= 14) {
                $asesoresStats[$key]['mora_8_14'] += $saldo;
            } elseif ($diasMora >= 15 && $diasMora <= 30) {
                $asesoresStats[$key]['mora_15_30'] += $saldo;
            } else {
                $asesoresStats[$key]['mora_30_plus'] += $saldo;
            }
        } else {
            // Cliente Nuevo / En Trámite
            $asesoresStats[$key]['clientes_tramite'] += 1;
        }
    }

    // Convertir a array y calcular porcentajes
    $desgloseAsesores = [];
    $consolidado = [
        'nombre' => 'TOTAL',
        'agencia' => $nombreAgencia,
        'es_total' => true,
        'total_cartera' => 0,
        'clientes_count' => 0,
        'clientes_tramite' => 0, // Nuevo
        'porcentaje_mora' => 0,
        'mora_normal' => 0,
        'mora_1_3' => 0,
        'mora_4_7' => 0,
        'mora_8_14' => 0,
        'mora_15_30' => 0,
        'mora_30_plus' => 0
    ];

    foreach ($asesoresStats as $stat) {
        $carteraVencida = $stat['total_cartera'] - $stat['mora_normal'];
        $porcentajeMora = 0;
        $porcentajeNormalidad = 0;

        if ($stat['total_cartera'] > 0) {
            $porcentajeMora = ($carteraVencida / $stat['total_cartera']) * 100;
            $porcentajeNormalidad = ($stat['mora_normal'] / $stat['total_cartera']) * 100;
        } else {
            // Si no hay cartera, se considera 100% normal (sanidad por defecto si no hay deuda)
            $porcentajeNormalidad = 100;
            $porcentajeMora = 0;
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

        $consolidado['total_cartera'] += $stat['total_cartera'];
        $consolidado['clientes_count'] += $stat['clientes_count'];
        $consolidado['clientes_tramite'] += $stat['clientes_tramite'];
        $consolidado['mora_normal'] += $stat['mora_normal'];
        $consolidado['mora_1_3'] += $stat['mora_1_3'];
        $consolidado['mora_4_7'] += $stat['mora_4_7'];
        $consolidado['mora_8_14'] += $stat['mora_8_14'];
        $consolidado['mora_15_30'] += $stat['mora_15_30'];
        $consolidado['mora_30_plus'] += $stat['mora_30_plus'];
    }

    $carteraVencidaTotal = $consolidado['total_cartera'] - $consolidado['mora_normal'];
    if ($consolidado['total_cartera'] > 0) {
        $consolidado['porcentaje_mora'] = round(($carteraVencidaTotal / $consolidado['total_cartera']) * 100, 2);
        $consolidado['porcentaje_normalidad'] = round(($consolidado['mora_normal'] / $consolidado['total_cartera']) * 100, 2);
    } else {
        $consolidado['porcentaje_mora'] = 0;
        $consolidado['porcentaje_normalidad'] = 100;
    }

    $desgloseAsesores[] = $consolidado;

    usort($clientesMora, function ($a, $b) {
        return $b['dias_mora'] - $a['dias_mora'];
    });

    echo json_encode([
        'success' => true,
        'data' => [
            'fecha' => $fechaHoy,
            'agencia' => $nombreAgencia,
            'id_agencia' => $agenciaId,
            'capital_calle' => round($capitalCalle, 2),
            'modalidades_activas' => $modalidadesStats,
            'categorias' => $categoriasCompletas,
            'clientes_mora' => $clientesMora,
            'total_clientes_mora' => count($clientesMora),
            'desglose_asesores' => $desgloseAsesores
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_detail' => 'Error en consolidado_cartera.php'
    ], JSON_PRETTY_PRINT);
}
?>