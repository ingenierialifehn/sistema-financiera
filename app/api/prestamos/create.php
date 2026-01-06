<?php
/**
 * API: Crear préstamo
 * POST /app/api/prestamos/create.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../core/PrestamoHelper.php';
require_once __DIR__ . '/../../core/Auth.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    // Solo admin puede crear préstamos
    $user = AuthMiddleware::requireAdmin();
    
    $input = getJsonInput();
    
    // Validar datos
    $validation = Validator::validate($input, [
        'cliente_id' => [
            'type' => 'integer',
            'required' => true,
            'message' => 'ID de cliente es requerido'
        ],
        'monto_prestado' => [
            'type' => 'number',
            'required' => true,
            'min' => 1,
            'message' => 'Monto prestado es requerido (mínimo 1)'
        ],
        'tasa_interes' => [
            'type' => 'number',
            'required' => true,
            'min' => 0,
            'max' => 100,
            'message' => 'Tasa de interés es requerida (0-100%)'
        ],
        'periodo_meses' => [
            'type' => 'integer',
            'required' => true,
            'min' => 1,
            'max' => 120,
            'message' => 'Periodo en meses es requerido (1-120)'
        ],
        'fecha_desembolso' => [
            'type' => 'date',
            'required' => true,
            'message' => 'Fecha de desembolso es requerida'
        ],
        'dia_pago' => [
            'type' => 'integer',
            'required' => true,
            'min' => 1,
            'max' => 28,
            'message' => 'Día de pago es requerido (1-28)'
        ],
        'modalidad' => [
            'type' => 'string',
            'required' => false,
            'message' => 'Modalidad inválida'
        ],
        'observaciones' => [
            'type' => 'string',
            'required' => false,
            'max' => 500,
            'message' => 'Observaciones inválidas'
        ]
    ]);
    
    if (!$validation['valid']) {
        Response::validationError($validation['errors']);
    }
    
    $data = $validation['data'];
    $db = getDB();
    
    // Verificar que el cliente existe y está activo
    $stmt = $db->prepare("SELECT id, nombre_completo FROM clientes WHERE id = :id AND estado = 'activo'");
    $stmt->execute(['id' => $data['cliente_id']]);
    $cliente = $stmt->fetch();
    
    if (!$cliente) {
        Response::error('Cliente no encontrado o inactivo', 404);
    }
    
    // Calcular montos
    $montoPrestado = floatval($data['monto_prestado']);
    $tasaInteres = floatval($data['tasa_interes']);
    $periodoMeses = intval($data['periodo_meses']);
    $modalidad = isset($data['modalidad']) ? $data['modalidad'] : 'mensual';
    if (!in_array($modalidad, ['diario','semanal','catorcenal','mensual'])) {
        $modalidad = 'mensual';
    }

    $montoTotal = PrestamoHelper::calculateMontoTotal($montoPrestado, $tasaInteres, $periodoMeses);
    $numeroCuotas = PrestamoHelper::calculateNumeroCuotas($periodoMeses, $modalidad);
    $montoCuota = ($modalidad === 'mensual')
        ? PrestamoHelper::calculateMontoCuota($montoTotal, $periodoMeses)
        : PrestamoHelper::calculateMontoCuotaPorCuotas($montoTotal, $numeroCuotas);
    
    // Calcular fecha de vencimiento
    $fechaDesembolso = new DateTime($data['fecha_desembolso']);
    $fechaVencimiento = PrestamoHelper::calcularUltimaFechaVencimiento(
        $periodoMeses,
        $data['fecha_desembolso'],
        intval($data['dia_pago']),
        $modalidad
    );

    // Generar número de préstamo único
    $numeroPrestamo = generatePrestamoNumber();
    $stmt = $db->prepare("SELECT id FROM prestamos WHERE numero_prestamo = :numero");
    $stmt->execute(['numero' => $numeroPrestamo]);
    $intentos = 0;
    while ($stmt->fetch() && $intentos < 10) {
        $numeroPrestamo = generatePrestamoNumber();
        $stmt->execute(['numero' => $numeroPrestamo]);
        $intentos++;
    }
    
    $db->beginTransaction();
    
    try {
        // Insertar préstamo con columnas opcionales modalidad y tasa_interes_sugerida si existen
        $colModalidad = false;
        $colTasaSug = false;
        $check = $db->query("SHOW COLUMNS FROM prestamos LIKE 'modalidad'");
        if ($check && $check->fetch()) { $colModalidad = true; }
        $check = $db->query("SHOW COLUMNS FROM prestamos LIKE 'tasa_interes_sugerida'");
        if ($check && $check->fetch()) { $colTasaSug = true; }

        $tasaSugerida = null;
        $map = [
            'diario' => 'tasa_diario',
            'semanal' => 'tasa_semanal',
            'catorcenal' => 'tasa_catorcenal',
            'mensual' => 'tasa_mensual'
        ];
        $cfgKey = isset($map[$modalidad]) ? $map[$modalidad] : null;
        if ($cfgKey) {
            $tasaSugerida = getConfig($cfgKey, getConfig('tasa_interes_default', $tasaInteres));
        }

        $cols = [
            'cliente_id', 'numero_prestamo', 'monto_prestado', 'tasa_interes', 'periodo_meses',
            'monto_total', 'monto_cuota', 'fecha_desembolso', 'fecha_vencimiento',
            'dia_pago', 'estado', 'observaciones', 'created_by'
        ];
        $vals = [
            ':cliente_id', ':numero_prestamo', ':monto_prestado', ':tasa_interes', ':periodo_meses',
            ':monto_total', ':monto_cuota', ':fecha_desembolso', ':fecha_vencimiento',
            ':dia_pago', "'pendiente'", ':observaciones', ':created_by'
        ];
        if ($colModalidad) { $cols[] = 'modalidad'; $vals[] = ':modalidad'; }
        if ($colTasaSug) { $cols[] = 'tasa_interes_sugerida'; $vals[] = ':tasa_interes_sugerida'; }

        $sql = "INSERT INTO prestamos (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
        $stmt = $db->prepare($sql);
        $params = [
            'cliente_id' => $data['cliente_id'],
            'numero_prestamo' => $numeroPrestamo,
            'monto_prestado' => $montoPrestado,
            'tasa_interes' => $tasaInteres,
            'periodo_meses' => $periodoMeses,
            'monto_total' => round($montoTotal, 2),
            'monto_cuota' => round($montoCuota, 2),
            'fecha_desembolso' => $data['fecha_desembolso'],
            'fecha_vencimiento' => $fechaVencimiento,
            'dia_pago' => intval($data['dia_pago']),
            'observaciones' => !empty($data['observaciones']) ? Validator::sanitize($data['observaciones']) : null,
            'created_by' => $user['id']
        ];
        if ($colModalidad) { $params['modalidad'] = $modalidad; }
        if ($colTasaSug) { $params['tasa_interes_sugerida'] = $tasaSugerida; }
        $stmt->execute($params);
        
        $prestamoId = $db->lastInsertId();
        
        // Generar cuotas automáticamente según modalidad
        PrestamoHelper::generateCuotasModalidad(
            $db,
            $prestamoId,
            round($montoCuota, 2),
            $periodoMeses,
            $data['fecha_desembolso'],
            intval($data['dia_pago']),
            $modalidad
        );
        
        // Actualizar estado a activo
        $db->prepare("UPDATE prestamos SET estado = 'activo' WHERE id = :id")
           ->execute(['id' => $prestamoId]);
        
        $db->commit();
        
        // Obtener préstamo creado
        $stmt = $db->prepare("
            SELECT p.*, c.nombre_completo as cliente_nombre, c.codigo_cliente
            FROM prestamos p
            INNER JOIN clientes c ON p.cliente_id = c.id
            WHERE p.id = :id
        ");
        $stmt->execute(['id' => $prestamoId]);
        $prestamo = $stmt->fetch();
        
        // Obtener cuotas
        $stmt = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id = :id ORDER BY numero_cuota ASC");
        $stmt->execute(['id' => $prestamoId]);
        $prestamo['cuotas'] = $stmt->fetchAll();
        
        // Registrar log
        Auth::logActivity($user['id'], 'create', 'prestamos', "Préstamo creado: {$numeroPrestamo}", null, $prestamo);
        
        Response::success($prestamo, 'Préstamo creado exitosamente', 201);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en prestamos/create.php: " . $e->getMessage());
    Response::serverError('Error al crear préstamo: ' . $e->getMessage());
}

