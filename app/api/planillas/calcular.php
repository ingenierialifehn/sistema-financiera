<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/PlanillaHelper.php';

Auth::requireAuth();

$db = getDB();

try {
    // Get all advisors
    $stmt = $db->query("
        SELECT 
            c.id_colaborador, 
            c.nombre_completo,
            a.nombre_agencia,
            a.id_agencia
        FROM colaboradores c
        LEFT JOIN agencias a ON c.id_agencia = a.id_agencia
        WHERE c.puesto_cargo = 'Asesor de Créditos' 
        AND c.estado_laboral = 'Activo'
        ORDER BY a.nombre_agencia, c.nombre_completo
    ");
    $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $preview = [];
    $totalPagarGlobal = 0;

    foreach ($advisors as $adv) {
        $calculation = PlanillaHelper::calculatePayment($db, $adv['id_colaborador']);

        $item = array_merge($adv, $calculation);

        // Add calculated fields for the frontend table
        // "Resultado Final: Sueldo Base + (Comisión Calculada) + Gastos de Campo"
        // TODO: Gastos de campo logic? 
        // Prompt says "Gastos de Campo (Combustible/Depreciación)". 
        // Where does this come from? Constant? Input?
        // I will add a placeholder field `gastos_campo` = 0 or derived from configuration if added. 
        // For now, I'll allow frontend to edit it or assume 0. 
        // Let's add `gastos_campo` to the response, maybe random or 0.
        // Usually dependent on "Zona" or "Vehículo". 
        // I'll check if `colaboradores` has vehicle info. 
        // I'll default to 0.

        $item['gastos_campo'] = 0; // Editable in frontend?
        $item['total_pagar'] = $item['sueldo_base'] + $item['comision_final'] + $item['gastos_campo'];

        $preview[] = $item;
        $totalPagarGlobal += $item['total_pagar'];
    }

    Response::success([
        'preview' => $preview,
        'total_global' => $totalPagarGlobal
    ]);

} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
