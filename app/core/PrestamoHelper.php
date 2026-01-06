<?php
/**
 * PrestamoHelper - Funciones helper para préstamos
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Helpers.php';

class PrestamoHelper {
    
    /**
     * Generar cuotas automáticamente para un préstamo (mensual por compatibilidad)
     */
    public static function generateCuotas($db, $prestamoId, $montoCuota, $periodoMeses, $fechaInicio, $diaPago) {
        try {
            // Calcular fecha de inicio del primer pago
            $fechaInicioObj = new DateTime($fechaInicio);
            $fechaPrimerPago = clone $fechaInicioObj;
            $fechaPrimerPago->modify('+' . $periodoMeses . ' month');
            $fechaPrimerPago->setDate(
                $fechaPrimerPago->format('Y'),
                $fechaPrimerPago->format('m'),
                min($diaPago, 28) // Asegurar que el día no exceda 28
            );
            
            // Generar cuotas
            for ($i = 1; $i <= $periodoMeses; $i++) {
                $fechaVencimiento = clone $fechaPrimerPago;
                $fechaVencimiento->modify('+' . ($i - 1) . ' month');
                
                // Ajustar día si es necesario (para evitar problemas con meses de 30/31 días)
                $dia = min($diaPago, 28);
                $fechaVencimiento->setDate(
                    $fechaVencimiento->format('Y'),
                    $fechaVencimiento->format('m'),
                    $dia
                );
                
                $stmt = $db->prepare("
                    INSERT INTO cuotas (
                        prestamo_id, numero_cuota, monto_cuota, fecha_vencimiento, estado
                    ) VALUES (
                        :prestamo_id, :numero_cuota, :monto_cuota, :fecha_vencimiento, 'pendiente'
                    )
                ");
                
                $stmt->execute([
                    'prestamo_id' => $prestamoId,
                    'numero_cuota' => $i,
                    'monto_cuota' => $montoCuota,
                    'fecha_vencimiento' => $fechaVencimiento->format('Y-m-d')
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error generando cuotas: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Calcular número de cuotas según modalidad
     */
    public static function calculateNumeroCuotas($periodoMeses, $modalidad) {
        switch ($modalidad) {
            case 'diario':
                $diasLaborales = getConfig('mes_laboral_dias', 20);
                return max(1, intval($periodoMeses) * intval($diasLaborales));
            case 'semanal':
                return max(1, intval($periodoMeses) * 4);
            case 'catorcenal':
                return max(1, intval($periodoMeses) * 2);
            case 'mensual':
            default:
                return max(1, intval($periodoMeses));
        }
    }

    /**
     * Calcular última fecha de vencimiento por modalidad (sin escribir en BD)
     */
    public static function calcularUltimaFechaVencimiento($periodoMeses, $fechaInicio, $diaPago, $modalidad) {
        if ($modalidad === 'mensual') {
            $fechaInicioObj = new DateTime($fechaInicio);
            $fechaPrimerPago = clone $fechaInicioObj;
            $fechaPrimerPago->modify('+' . $periodoMeses . ' month');
            $fechaPrimerPago->setDate(
                $fechaPrimerPago->format('Y'),
                $fechaPrimerPago->format('m'),
                min($diaPago, 28)
            );
            $ultima = clone $fechaPrimerPago;
            $ultima->modify('+' . ($periodoMeses - 1) . ' month');
            $ultima->setDate($ultima->format('Y'), $ultima->format('m'), min($diaPago, 28));
            return $ultima->format('Y-m-d');
        }

        $fecha = new DateTime($fechaInicio);
        $numeroCuotas = self::calculateNumeroCuotas($periodoMeses, $modalidad);
        $ultima = clone $fecha;

        if ($modalidad === 'diario') {
            $fecha->modify('+1 day');
            while (in_array($fecha->format('N'), [6,7])) { $fecha->modify('+1 day'); }
            $cont = 0;
            while ($cont < $numeroCuotas) {
                if (!in_array($fecha->format('N'), [6,7])) {
                    $ultima = clone $fecha;
                    $cont++;
                }
                $fecha->modify('+1 day');
            }
        } elseif ($modalidad === 'semanal') {
            $fecha->modify('+7 day');
            for ($i = 1; $i <= $numeroCuotas; $i++) {
                $ultima = clone $fecha;
                $fecha->modify('+7 day');
            }
        } elseif ($modalidad === 'catorcenal') {
            $fecha->modify('+14 day');
            for ($i = 1; $i <= $numeroCuotas; $i++) {
                $ultima = clone $fecha;
                $fecha->modify('+14 day');
            }
        }

        return $ultima->format('Y-m-d');
    }

    /**
     * Generar cuotas por modalidad
     */
    public static function generateCuotasModalidad($db, $prestamoId, $montoCuota, $periodoMeses, $fechaInicio, $diaPago, $modalidad) {
        if ($modalidad === 'mensual') {
            return self::generateCuotas($db, $prestamoId, $montoCuota, $periodoMeses, $fechaInicio, $diaPago);
        }

        try {
            $fecha = new DateTime($fechaInicio);
            $numeroCuotas = self::calculateNumeroCuotas($periodoMeses, $modalidad);
            $i = 1;

            // Punto de inicio por modalidad
            if ($modalidad === 'diario') {
                // iniciar el siguiente día hábil
                $fecha->modify('+1 day');
                while (in_array($fecha->format('N'), [6,7])) { // 6=sábado, 7=domingo
                    $fecha->modify('+1 day');
                }
                while ($i <= $numeroCuotas) {
                    // Insertar solo L-V
                    if (!in_array($fecha->format('N'), [6,7])) {
                        self::insertCuota($db, $prestamoId, $i, $montoCuota, $fecha);
                        $i++;
                    }
                    $fecha->modify('+1 day');
                }
            } elseif ($modalidad === 'semanal') {
                // iniciar a la semana siguiente
                $fecha->modify('+7 day');
                for ($i = 1; $i <= $numeroCuotas; $i++) {
                    self::insertCuota($db, $prestamoId, $i, $montoCuota, $fecha);
                    $fecha->modify('+7 day');
                }
            } elseif ($modalidad === 'catorcenal') {
                // iniciar a los 14 días
                $fecha->modify('+14 day');
                for ($i = 1; $i <= $numeroCuotas; $i++) {
                    self::insertCuota($db, $prestamoId, $i, $montoCuota, $fecha);
                    $fecha->modify('+14 day');
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Error generando cuotas por modalidad: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Insert helper
     */
    private static function insertCuota($db, $prestamoId, $numeroCuota, $montoCuota, DateTime $fechaVencimiento) {
        $stmt = $db->prepare("
            INSERT INTO cuotas (
                prestamo_id, numero_cuota, monto_cuota, fecha_vencimiento, estado
            ) VALUES (
                :prestamo_id, :numero_cuota, :monto_cuota, :fecha_vencimiento, 'pendiente'
            )
        ");
        $stmt->execute([
            'prestamo_id' => $prestamoId,
            'numero_cuota' => $numeroCuota,
            'monto_cuota' => $montoCuota,
            'fecha_vencimiento' => $fechaVencimiento->format('Y-m-d')
        ]);
    }

    /**
     * Calcular monto total del préstamo
     */
    public static function calculateMontoTotal($montoPrestado, $tasaInteres, $periodoMeses) {
        // Interés simple: monto_total = monto_prestado * (1 + (tasa_interes / 100) * periodo_meses / 12)
        $interes = $montoPrestado * ($tasaInteres / 100) * ($periodoMeses / 12);
        return $montoPrestado + $interes;
    }
    
    /**
     * Calcular monto de cuota (mensual por periodoMeses)
     */
    public static function calculateMontoCuota($montoTotal, $periodoMeses) {
        return $montoTotal / $periodoMeses;
    }

    /**
     * Calcular monto de cuota por número de cuotas
     */
    public static function calculateMontoCuotaPorCuotas($montoTotal, $numeroCuotas) {
        return $montoTotal / max(1, intval($numeroCuotas));
    }
}
