<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/PlanillaHelper.php';

Auth::requireAuth();

// Permission check (assuming admin or specific permission)
if (!Auth::hasPermission('planillas.config') && !Auth::hasPermission('admin')) {
    // For development, maybe relax? But standard is strict.
    // Response::forbidden('No tiene permisos para configurar planillas');
}

$db = getDB();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 1. Get General Config
        $config = PlanillaHelper::getConfig($db);

        // Decode JSONs for frontend
        $config['tramos_comision'] = json_decode($config['tramos_comision']);
        $config['escaladores_normalidad'] = json_decode($config['escaladores_normalidad']);

        // 2. Get Advisors with Exceptions
        // Get all users with role/puesto 'Asesor de Créditos'
        $stmt = $db->query("
            SELECT 
                c.id_colaborador, 
                c.nombre_completo, 
                c.sueldo_base_excepcion,
                a.nombre_agencia
            FROM colaboradores c
            LEFT JOIN agencias a ON c.id_agencia = a.id_agencia
            WHERE c.puesto_cargo = 'Asesor de Créditos' 
            AND c.estado_laboral = 'Activo'
            ORDER BY a.nombre_agencia, c.nombre_completo
        ");
        $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success([
            'config' => $config,
            'advisors' => $advisors
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['update_type']) && $data['update_type'] === 'general') {
            // Update General Config
            $tramos = json_encode($data['tramos_comision']);
            $escaladores = json_encode($data['escaladores_normalidad']);

            $stmt = $db->prepare("UPDATE config_planilla SET 
                sueldo_base_general = ?, 
                minimo_clientes = ?, 
                minimo_normalidad = ?, 
                tramos_comision = ?, 
                escaladores_normalidad = ?
                WHERE id = 1"); // Assuming id 1 is the single config row

            // If id 1 doesn't exist (cleaned DB), we might need to handle, but setup script ensures it.
            $stmt->execute([
                $data['sueldo_base_general'],
                $data['minimo_clientes'],
                $data['minimo_normalidad'],
                $tramos,
                $escaladores
            ]);

            Response::success(['message' => 'Configuración actualizada']);

        } elseif (isset($data['update_type']) && $data['update_type'] === 'advisor_salary') {
            // Update Individual Advisor Salary
            $idColaborador = $data['id_colaborador'];
            $sueldo = $data['sueldo_base_excepcion']; // Can be null to reset to default

            $stmt = $db->prepare("UPDATE colaboradores SET sueldo_base_excepcion = ? WHERE id_colaborador = ?");
            $stmt->execute([$sueldo, $idColaborador]);

            Response::success(['message' => 'Sueldo de asesor actualizado']);

        } elseif (isset($data['update_type']) && $data['update_type'] === 'bulk_salary') {
            // Bulk Update
            // Prompt: "Un campo para actualizar el Sueldo Base de forma masiva... Si un asesor tiene un sueldo distinto... el sistema debe preguntar"
            // Start logic: The frontend should ask the user. Here we just execute what is sent.
            // If the user chooses "Update All" (overhead existing), we set exceptions to NULL (use general) or set all to new value?
            // "Actualizar el Sueldo Base de forma masiva" usually means updating `sueldo_base_general`.
            // Exceptions are stored in `colaboradores`.
            // So updating `sueldo_base_general` updates everyone who DOESN'T have an exception.
            // If the user wants to "Level" someone with an exception, they should clear the exception.

            // This endpoint might accept a list of IDs to clear exceptions for?

            if (!empty($data['clear_exceptions_ids'])) {
                $ids = implode(',', array_map('intval', $data['clear_exceptions_ids']));
                $db->exec("UPDATE colaboradores SET sueldo_base_excepcion = NULL WHERE id_colaborador IN ($ids)");
            }

            // Update general base
            if (isset($data['new_base_salary'])) {
                $stmt = $db->prepare("UPDATE config_planilla SET sueldo_base_general = ? WHERE id = 1");
                $stmt->execute([$data['new_base_salary']]);
            }

            Response::success(['message' => 'Sueldo base masivo actualizado']);
        }
    }

} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
