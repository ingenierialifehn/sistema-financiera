<?php
/**
 * Script de Verificación de Lógica de Cuotas
 * Ejecutar desde navegador o consola
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/PrestamoHelper.php';

header('Content-Type: text/plain');

echo "=== VERIFICACIÓN DE LÓGICA DE CUOTAS ===\n";
echo "Zona Horaria: " . date_default_timezone_get() . "\n";
echo "Fecha Actual: " . date('Y-m-d H:i:s') . "\n\n";

function simular($titulo, $modalidad, $fechaInicioStr, $esperadoStr)
{
    echo "PRUEBA: $titulo\n";
    echo "  Modalidad: $modalidad\n";
    echo "  Fecha Inicio (Desembolso): $fechaInicioStr (" . date('l', strtotime($fechaInicioStr)) . ")\n";

    // Simular lógica
    $fecha = new DateTime($fechaInicioStr);
    $numeroCuotas = 1;
    $fechaResultado = '';

    if ($modalidad === 'diario') {
        $fecha->modify('+1 day');
        while (in_array($fecha->format('N'), [6, 7])) {
            $fecha->modify('+1 day');
        }
        $fechaResultado = $fecha->format('Y-m-d');
    } elseif ($modalidad === 'semanal') {
        $fecha->modify('+7 day');
        if ($fecha->format('N') == 6) {
            $fecha->modify('-1 day');
        } elseif ($fecha->format('N') == 7) {
            $fecha->modify('+1 day');
        }
        $fechaResultado = $fecha->format('Y-m-d');
    } elseif ($modalidad === 'catorcenal') {
        $fecha->modify('+14 day');
        if ($fecha->format('N') == 6) {
            $fecha->modify('-1 day');
        } elseif ($fecha->format('N') == 7) {
            $fecha->modify('+1 day');
        }
        $fechaResultado = $fecha->format('Y-m-d');
    }

    $diaRes = date('l', strtotime($fechaResultado));
    echo "  Resultado 1ra Cuota: $fechaResultado ($diaRes)\n";
    echo "  Esperado:            $esperadoStr\n";

    if ($fechaResultado === $esperadoStr) {
        echo "  ✅ CORRECTO\n";
    } else {
        echo "  ❌ INCORRECTO\n";
    }
    echo "--------------------------------------------------\n";
}

// 1. DIARIO - Inicio Sábado
// Sábado 3 Ene 2026. (El lunes 5 debería cargar la primera)
simular("Diario inicia Sábado", "diario", "2026-01-03", "2026-01-05");

// 2. SEMANAL - Inicio Miércoles
// Miércoles 7 Ene 2026. (Siguiente semana mismo día -> Miércoles 14)
simular("Semanal inicia Miércoles", "semanal", "2026-01-07", "2026-01-14");

// 3. SEMANAL - Inicio Sábado
// Sábado 3 Ene 2026. (Siguiente semana Sábado 10 -> Ajuste Viernes 9)
// Usuario: "si se desembolas el sabado seria la siguiente semana un dia antes del mismo"
simular("Semanal inicia Sábado", "semanal", "2026-01-03", "2026-01-09");

// 4. CATORCENAL - Inicio Sábado
// Sábado 3 Ene 2026. (14 días -> Sábado 17 -> Ajuste Viernes 16)
simular("Catorcenal inicia Sábado", "catorcenal", "2026-01-03", "2026-01-16");

?>