<?php
/**
 * Script para configurar tasas de interés por modalidad
 */
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    
    // Configuraciones a insertar
    $configuraciones = [
        [
            'clave' => 'tasa_diario',
            'valor' => '2.5',
            'tipo' => 'decimal',
            'descripcion' => 'Tasa de interés sugerida para pagos diarios (Lunes a Viernes)'
        ],
        [
            'clave' => 'tasa_semanal',
            'valor' => '5.0',
            'tipo' => 'decimal',
            'descripcion' => 'Tasa de interés sugerida para pagos semanales (4 cuotas al mes)'
        ],
        [
            'clave' => 'tasa_catorcenal',
            'valor' => '8.0',
            'tipo' => 'decimal',
            'descripcion' => 'Tasa de interés sugerida para pagos catorcenales (2 cuotas al mes)'
        ],
        [
            'clave' => 'tasa_mensual',
            'valor' => '15.0',
            'tipo' => 'decimal',
            'descripcion' => 'Tasa de interés sugerida para pagos mensuales'
        ],
        [
            'clave' => 'mes_laboral_dias',
            'valor' => '20',
            'tipo' => 'numero',
            'descripcion' => 'Número de días laborales en un mes (Lunes a Viernes)'
        ]
    ];
    
    // Insertar o actualizar cada configuración
    foreach ($configuraciones as $config) {
        $stmt = $db->prepare("
            INSERT INTO configuraciones (clave, valor, tipo, descripcion) 
            VALUES (:clave, :valor, :tipo, :descripcion)
            ON DUPLICATE KEY UPDATE 
            valor = VALUES(valor), 
            descripcion = VALUES(descripcion),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            ':clave' => $config['clave'],
            ':valor' => $config['valor'],
            ':tipo' => $config['tipo'],
            ':descripcion' => $config['descripcion']
        ]);
        
        echo "Configuración '{$config['clave']}' actualizada correctamente<br>";
    }
    
    echo "<br><strong>¡Todas las configuraciones han sido establecidas!</strong>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
