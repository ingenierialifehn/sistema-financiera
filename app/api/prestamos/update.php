<?php
/**
 * API: Actualizar préstamo
 * PUT/POST /app/api/prestamos/update.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/PrestamoHelper.php'; // Helper for calculations

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    // Check authentication
    $user = AuthMiddleware::requireAuth();

    // Permissions Check
    // Allow if Admin OR has specific permission to edit data (e.g. Verifier)
    $hasPermission = Auth::hasPermission('seguridad') || // Admin usually has security
        Auth::hasPermission('prestamos_gestion.edit_terms') ||
        Auth::hasPermission('verificacion_campo.edit_data');

    if (!$hasPermission) {
        Response::forbidden('No tiene permisos para actualizar préstamos.');
    }

    $input = getJsonInput();

    if (!isset($input['id']) || empty($input['id'])) {
        Response::error('ID de préstamo es requerido', 400);
    }

    $id = intval($input['id']);
    $db = getDB();

    // Verificar que existe
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $prestamoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prestamoExistente) {
        Response::notFound('Préstamo no encontrado');
    }

    // Validar estado para permitir edición
    // Usually only pending/verification status allows full edits
    $editableStates = ['Solicitado', 'En Análisis', 'Verificación de Campo', 'Pendiente de Operaciones', 'Rechazado en Ruta'];
    $isFinancialEdit = isset($input['monto_capital']) || isset($input['plazo_meses']) || isset($input['tasa_total']);

    if ($isFinancialEdit && !in_array($prestamoExistente['estado'], $editableStates)) {
        // If active, ensure no payments before editing (or block entirely)
        // For now, blocking financial edits on active loans here (use specialized REST restructure API for that)
        Response::error('No se pueden editar términos financieros en este estado. Use reestructuración.', 409);
    }

    // Build Update Query
    $updateFields = [];
    $params = ['id' => $id];
    $recalculateFinancials = false;

    // --- FINANCIAL FIELDS ---
    if (isset($input['monto_capital'])) {
        $monto = floatval($input['monto_capital']);
        if ($monto <= 0)
            Response::error('El monto debe ser mayor a 0', 400);
        $updateFields[] = "monto_capital = :monto";
        $updateFields[] = "neto_entregar = :monto_entregar"; // Reset net to full amount initially
        $params['monto'] = $monto;
        $params['monto_entregar'] = $monto;
        $recalculateFinancials = true;
    }

    if (isset($input['plazo_meses'])) {
        $plazo = intval($input['plazo_meses']);
        if ($plazo <= 0)
            Response::error('El plazo debe ser mayor a 0', 400);
        $updateFields[] = "plazo_meses = :plazo";
        $params['plazo'] = $plazo;
        $recalculateFinancials = true;
    }

    // Capture Tasa (Using tasa_total as the main driver based on create.php logic)
    // Front end sends 'tasa_interes', but logic uses 'tasa_total' (11%) in create.php.
    // Let's assume input 'tasa_interes' actually means 'tasa_total' for simple calculation matching create.php, 
    // OR we respect the split. create.php hardcoded 11%. 
    // If user edits "Tasa", we update tasa_total.
    if (isset($input['tasa_interes'])) {
        $tasa = floatval($input['tasa_interes']);
        if ($tasa < 0)
            Response::error('La tasa no puede ser negativa', 400);
        $updateFields[] = "tasa_total = :tasa"; // Mapping UI rate to total rate for calculation
        $updateFields[] = "tasa_interes = :tasa_simple"; // Update breakdown too visually
        $params['tasa'] = $tasa;
        $params['tasa_simple'] = $tasa; // Simplified mapping
        $recalculateFinancials = true;
    }

    if (isset($input['modalidad'])) {
        $validModalities = ['Mensual', 'Quincenal', 'Catorcenal', 'Semanal', 'Diario']; // Adjust case sensitivity
        // Normalize case? DB seems to use Capitalized.
        $modalidad = ucfirst(strtolower($input['modalidad']));
        $updateFields[] = "modalidad = :modalidad";
        $params['modalidad'] = $modalidad;
        $recalculateFinancials = true;
    }

    if (isset($input['tipo_prestamo'])) {
        $updateFields[] = "tipo_prestamo = :tipo";
        $params['tipo'] = $input['tipo_prestamo'];
    }

    if (isset($input['fecha_desembolso'])) {
        $updateFields[] = "fecha_desembolso = :fecha";
        $params['fecha'] = $input['fecha_desembolso'];
    }

    // --- GENERIC FIELDS ---
    if (isset($input['observaciones'])) {
        $updateFields[] = "observaciones = :observaciones";
        $params['observaciones'] = strip_tags($input['observaciones']);
    }

    if (isset($input['estado'])) {
        // Basic validation for state transitions could go here
        $updateFields[] = "estado = :estado";
        $params['estado'] = $input['estado'];
    }

    // --- RECALCULATION LOGIC ---
    if ($recalculateFinancials) {
        // Construct composite current state
        $monto = isset($params['monto']) ? $params['monto'] : floatval($prestamoExistente['monto_capital']);
        $plazo = isset($params['plazo']) ? $params['plazo'] : intval($prestamoExistente['plazo_meses']);
        $tasa = isset($params['tasa']) ? $params['tasa'] : floatval($prestamoExistente['tasa_total']);
        $modal = isset($params['modalidad']) ? $params['modalidad'] : $prestamoExistente['modalidad'];

        // Logic from create.php
        // Total Interes = Monto * (Tasa/100) * Plazo
        $totalInteresMonto = $monto * ($tasa / 100) * $plazo;
        $totalAPagar = $monto + $totalInteresMonto;

        // Recalculate 'neto_entregar' if amount changed or type changed
        if (isset($params['monto']) || isset($params['tipo'])) {
            $currentType = isset($params['tipo']) ? $params['tipo'] : $prestamoExistente['tipo_prestamo'];

            if ($currentType === 'Refinanciamiento' || $currentType === 'Readecuacion') {
                // Buscar préstamos anteriores activos para deducir saldo (consolidado)
                // Exclude current ID just in case
                $stmtPrevCalc = $db->prepare("SELECT 
                                                SUM(p.monto_capital - (
                                                    SELECT IFNULL(SUM(monto_pagado * (capital_cuota/monto_cuota)), 0) 
                                                    FROM cuotas WHERE prestamo_id = p.id AND estado IN ('pagada', 'parcial') AND monto_cuota > 0
                                                )) as saldo_pendiente_total
                                              FROM prestamos p
                                              WHERE id_cliente = ? 
                                              AND id != ?
                                              AND estado IN ('Activo', 'Vencido')");
                $stmtPrevCalc->execute([$prestamoExistente['id_cliente'], $id]);
                $prevCalc = $stmtPrevCalc->fetch(PDO::FETCH_ASSOC);

                if ($prevCalc && $prevCalc['saldo_pendiente_total'] !== null) {
                    $saldoAnterior = max(0, floatval($prevCalc['saldo_pendiente_total']));
                    $netoEntregar = max(0, $monto - $saldoAnterior);

                    // Update the parameter
                    $params['monto_entregar'] = $netoEntregar;
                }
            }
        }

        $numCuotas = 1;
        switch (strtolower($modal)) {
            case 'diario':
                $numCuotas = $plazo * 20;
                break;
            case 'semanal':
                $numCuotas = $plazo * 4;
                break;
            case 'catorcenal':
                $numCuotas = $plazo * 2;
                break;
            case 'mensual':
                $numCuotas = $plazo * 1;
                break;
            default:
                $numCuotas = $plazo * 1;
        }

        $valorCuota = ($numCuotas > 0) ? ($totalAPagar / $numCuotas) : 0;

        // Add to update
        $updateFields[] = "total_a_pagar = :calc_total";
        $updateFields[] = "valor_cuota = :calc_cuota";
        $params['calc_total'] = $totalAPagar;
        $params['calc_cuota'] = $valorCuota;
    }

    if (empty($updateFields)) {
        Response::success(null, 'No hubo cambios para guardar.');
    }

    $updateFields[] = "updated_at = NOW()";

    $sql = "UPDATE prestamos SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Audit Log
    Auth::logActivity($user['id_usuario'], 'update', 'prestamos', "Actualizó préstamo #{$id}", null, $input);

    // Return full updated object
    $stmtNew = $db->prepare("SELECT * FROM prestamos WHERE id = ?");
    $stmtNew->execute([$id]);
    $updatedLoan = $stmtNew->fetch(PDO::FETCH_ASSOC);

    Response::success($updatedLoan, 'Préstamo actualizado correctamente.');

} catch (Exception $e) {
    error_log("Error updating loan: " . $e->getMessage());
    Response::serverError('Error al actualizar: ' . $e->getMessage());
}


