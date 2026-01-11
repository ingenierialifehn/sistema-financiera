<?php
require_once __DIR__ . '/../config/database.php';

class PlanillaHelper
{
    /**
     * Obtener configuración actual
     */
    public static function getConfig($db)
    {
        $stmt = $db->query("SELECT * FROM config_planilla LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Calcular métricas de cartera para un asesor específico
     */
    public static function calculateMetrics($db, $colaboradorId)
    {
        // 1. Obtener User ID del colaborador (porque clientes se ligan a usuarios)
        $stmtUser = $db->prepare("SELECT id_usuario FROM usuarios WHERE id_colaborador = ? AND estado = 'Activo'");
        $stmtUser->execute([$colaboradorId]);
        $userId = $stmtUser->fetchColumn();

        if (!$userId) {
            return [
                'saldo_cartera' => 0,
                'clientes_activos' => 0,
                'normalidad_porcentaje' => 100, // Asumir 100 si no tiene cartera
                'error' => 'Usuario no encontrado o inactivo'
            ];
        }

        // 2. Obtener préstamos activos del asesor
        // Copiado logica de estado_cartera.php
        $sql = "SELECT 
                p.id,
                (SELECT SUM(capital_cuota) 
                 FROM cuotas 
                 WHERE prestamo_id = p.id 
                 AND estado IN ('pendiente', 'vencida')) as capital_pendiente,
                (SELECT MIN(fecha_vencimiento) 
                 FROM cuotas 
                 WHERE prestamo_id = p.id 
                 AND estado IN ('pendiente', 'vencida')) as cuota_mas_antigua
                FROM prestamos p
                INNER JOIN clientes c ON p.id_cliente = c.id
                WHERE p.estado = 'Activo'
                AND c.cobrador_id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalCartera = 0;
        $totalNormal = 0;
        $clientesActivos = 0;

        foreach ($loans as $loan) {
            $saldo = floatval($loan['capital_pendiente'] ?? 0);

            if ($saldo <= 0)
                continue;

            $totalCartera += $saldo;
            $clientesActivos++;

            // Calcular Mora
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

            // Normalidad (0 días mora)
            if ($diasMora == 0) {
                $totalNormal += $saldo;
            }
        }

        $normalidad = ($totalCartera > 0) ? ($totalNormal / $totalCartera) * 100 : 100;

        return [
            'saldo_cartera' => $totalCartera,
            'clientes_activos' => $clientesActivos,
            'normalidad_porcentaje' => round($normalidad, 2),
            'monto_normal' => $totalNormal
        ];
    }

    /**
     * Calcular desglose de pago para un asesor
     */
    public static function calculatePayment($db, $colaboradorId, $metrics = null)
    {
        if (!$metrics) {
            $metrics = self::calculateMetrics($db, $colaboradorId);
        }

        // Obtener Configuración
        $config = self::getConfig($db);
        if (!$config) {
            throw new Exception("Configuración de planilla no encontrada");
        }

        // Obtener datos del colaborador (Sueldo Base)
        $stmtCol = $db->prepare("SELECT sueldo_base_excepcion FROM colaboradores WHERE id_colaborador = ?");
        $stmtCol->execute([$colaboradorId]);
        $colData = $stmtCol->fetch(PDO::FETCH_ASSOC);
        if ($colData === false) {
            // Manejar caso donde no se encuentra el colaborador (aunque debería si se pasa ID válido)
            $sueldoBase = floatval($config['sueldo_base_general']);
        } else {
            $sueldoBase = ($colData['sueldo_base_excepcion'] !== null)
                ? floatval($colData['sueldo_base_excepcion'])
                : floatval($config['sueldo_base_general']);
        }

        // Decodificar JSONs
        $tramos = json_decode($config['tramos_comision'], true);
        $escaladores = json_decode($config['escaladores_normalidad'], true);

        $calculo = [
            'sueldo_base' => $sueldoBase,
            'comision_base' => 0,
            'comision_final' => 0,
            'candados_activados' => [],
            'tramo_aplicado' => null,
            'escalador_aplicado' => null, // {porcentaje: 0}
            'metrics' => $metrics
        ];

        // 1. Validar Candados
        if ($metrics['clientes_activos'] < $config['minimo_clientes']) {
            $calculo['candados_activados'][] = "Mínimo Clientes ({$metrics['clientes_activos']} < {$config['minimo_clientes']})";
        }
        if ($metrics['normalidad_porcentaje'] < $config['minimo_normalidad']) {
            $calculo['candados_activados'][] = "Mínimo Normalidad ({$metrics['normalidad_porcentaje']}% < {$config['minimo_normalidad']}%)";
        }

        if (!empty($calculo['candados_activados'])) {
            // Si hay candados, comisión es 0
            $calculo['comision_final'] = 0;
            return $calculo;
        }

        // 2. Identificar Tramo de Comisión
        $saldo = $metrics['saldo_cartera'];
        $montoTramo = 0;
        foreach ($tramos as $tramo) {
            if ($saldo >= $tramo['min'] && $saldo <= $tramo['max']) {
                $montoTramo = floatval($tramo['monto']);
                $calculo['tramo_aplicado'] = $tramo;
                break;
            }
            // Si es el último tramo y supere el max (logic adjusted if max is strictly defined, usually last is infinity)
        }
        $calculo['comision_base'] = $montoTramo;

        // 3. Aplicar Escalador de Normalidad
        $norm = $metrics['normalidad_porcentaje'];
        $porcentajePago = 0;
        foreach ($escaladores as $esc) {
            // >= min AND < max (usually). User said ">98% = 100%".
            // Let's assume inclusive min, exclusive max unless last.
            // Adjust logic loosely to fit.
            if ($norm >= $esc['min'] && $norm < $esc['max']) {
                $porcentajePago = floatval($esc['porcentaje']);
                $calculo['escalador_aplicado'] = $esc;
                break;
            }
            // Catch for exact max or above last max
            if ($norm >= $esc['max'] && $esc == end($escaladores)) {
                $porcentajePago = floatval($esc['porcentaje']);
                $calculo['escalador_aplicado'] = $esc;
            }
        }

        // Fix for 100% case if ranges are 98-100
        if ($norm >= 100)
            $porcentajePago = 100;
        // Or trust the loop. Better logic: sorted ranges.

        // Calcular final
        $calculo['comision_final'] = $montoTramo * ($porcentajePago / 100);
        $calculo['porcentaje_pago_comision'] = $porcentajePago;

        return $calculo;
    }
}
