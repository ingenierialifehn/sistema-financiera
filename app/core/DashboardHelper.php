<?php
/**
 * DashboardHelper - Funciones helper para el dashboard
 */

require_once __DIR__ . '/../config/database.php';

class DashboardHelper
{

    /**
     * Obtener resumen de métricas del dashboard
     */
    public static function getSummary()
    {
        $db = getDB();

        try {
            // Total préstamos activos
            $stmt = $db->query("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'activo'");
            $totalPrestamosActivos = $stmt->fetch()['total'] ?? 0;

            // Cartera total (suma de saldos pendientes)
            // Calcular saldo como: monto_total - monto_pagado_total de todas las cuotas
            $stmt = $db->query("
                SELECT 
                    IFNULL(SUM(p.monto_total), 0) as total_prestado,
                    IFNULL(SUM(COALESCE(cu.monto_pagado, 0)), 0) as total_pagado
                FROM prestamos p
                LEFT JOIN cuotas cu ON p.id = cu.prestamo_id
                WHERE p.estado = 'activo'
            ");
            $totals = $stmt->fetch();
            $carteraTotal = floatval($totals['total_prestado'] ?? 0) - floatval($totals['total_pagado'] ?? 0);

            // Cobros de hoy
            $stmt = $db->query("
                SELECT IFNULL(SUM(monto_pagado), 0) as cobros_hoy 
                FROM pagos 
                WHERE DATE(fecha_pago) = CURDATE() 
                AND estado = 'confirmado'
            ");
            $cobrosHoy = floatval($stmt->fetch()['cobros_hoy'] ?? 0);

            // Cuotas vencidas
            $stmt = $db->query("
                SELECT COUNT(*) as cuotas_vencidas 
                FROM cuotas 
                WHERE fecha_vencimiento < CURDATE() 
                AND estado IN ('pendiente', 'en_mora')
            ");
            $cuotasVencidas = $stmt->fetch()['cuotas_vencidas'] ?? 0;

            // Cobradores activos
            $stmt = $db->query("
                SELECT COUNT(*) as cobradores_activos 
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE r.nombre_rol = 'Cobrador' 
                AND u.estado = 'Activo'
            ");
            $cobradoresActivos = $stmt->fetch()['cobradores_activos'] ?? 0;

            return [
                'total_prestamos_activos' => (int) $totalPrestamosActivos,
                'cartera_total' => round($carteraTotal, 2),
                'cobros_hoy' => round($cobrosHoy, 2),
                'cuotas_vencidas' => (int) $cuotasVencidas,
                'cobradores_activos' => (int) $cobradoresActivos
            ];

        } catch (Exception $e) {
            error_log("Error en getSummary: " . $e->getMessage());
            return [
                'total_prestamos_activos' => 0,
                'cartera_total' => 0,
                'cobros_hoy' => 0,
                'cuotas_vencidas' => 0,
                'cobradores_activos' => 0
            ];
        }
    }

    /**
     * Obtener cobros por día (últimos N días)
     */
    public static function getPaymentsLastNDays($days = 30)
    {
        $db = getDB();

        try {
            $stmt = $db->prepare("
                SELECT 
                    DATE(fecha_pago) as dia, 
                    IFNULL(SUM(monto_pagado), 0) as total
                FROM pagos
                WHERE fecha_pago >= DATE_SUB(CURDATE(), INTERVAL :days DAY) 
                AND estado = 'confirmado'
                GROUP BY DATE(fecha_pago)
                ORDER BY DATE(fecha_pago) ASC
            ");
            $stmt->execute(['days' => $days]);
            $results = $stmt->fetchAll();

            // Crear array de todos los días (llenar días sin pagos con 0)
            $data = [];
            $labels = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('d/m', strtotime($date));
                $data[] = 0; // Por defecto 0
            }

            // Llenar con datos reales
            foreach ($results as $row) {
                $dia = $row['dia'];
                $total = floatval($row['total']);

                // Buscar el índice de este día
                $index = array_search(date('d/m', strtotime($dia)), $labels);
                if ($index !== false) {
                    $data[$index] = $total;
                }
            }

            return [
                'labels' => $labels,
                'data' => $data
            ];

        } catch (Exception $e) {
            error_log("Error en getPaymentsLastNDays: " . $e->getMessage());
            return [
                'labels' => [],
                'data' => []
            ];
        }
    }

    /**
     * Obtener últimos pagos
     */
    public static function getLatestPayments($limit = 20)
    {
        $db = getDB();

        try {
            $stmt = $db->prepare("
                SELECT 
                    p.id,
                    p.monto_pagado,
                    p.monto_mora,
                    p.fecha_pago,
                    p.comprobante_url,
                    p.estado,
                    p.created_at as fecha_registro_server,
                    c.nombre_completo as cliente_nombre,
                    c.codigo_cliente,
                    col.nombre_completo as cobrador_nombre,
                    pr.numero_prestamo
                FROM pagos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN prestamos pr ON p.prestamo_id = pr.id
                LEFT JOIN usuarios u ON p.cobrado_por = u.id_usuario
                LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
                ORDER BY p.created_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $payments = [];
            while ($row = $stmt->fetch()) {
                $payments[] = [
                    'id' => (int) $row['id'],
                    'cliente_nombre' => $row['cliente_nombre'],
                    'codigo_cliente' => $row['codigo_cliente'],
                    'monto' => floatval($row['monto_pagado']),
                    'monto_mora' => floatval($row['monto_mora']),
                    'fecha_pago' => $row['fecha_pago'],
                    'fecha_registro_server' => $row['fecha_registro_server'],
                    'url_foto_comprobante' => $row['comprobante_url'],
                    'cobrador_nombre' => $row['cobrador_nombre'] ?? 'N/A',
                    'estado' => $row['estado'],
                    'numero_prestamo' => $row['numero_prestamo']
                ];
            }

            return $payments;

        } catch (Exception $e) {
            error_log("Error en getLatestPayments: " . $e->getMessage());
            return [];
        }
    }
}

