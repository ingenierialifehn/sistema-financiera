<?php
/**
 * Middleware de Autenticación
 * Protege endpoints de la API
 * 
 * Uso:
 * require_once __DIR__ . "/../../middleware/AuthMiddleware.php";
 * AuthMiddleware::requireAuth();
 * 
 * O con rol específico:
 * AuthMiddleware::requireRole('admin');
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Response.php';

class AuthMiddleware {
    
    /**
     * Requerir autenticación
     * Retorna el usuario autenticado o detiene la ejecución con 401
     * 
     * @return array Datos del usuario autenticado
     */
    public static function requireAuth() {
        $user = Auth::getCurrentUser();
        
        if (!$user) {
            Response::unauthorized('Token de sesión inválido o expirado. Por favor, inicie sesión nuevamente.');
        }
        
        return $user;
    }
    
    /**
     * Requerir rol específico
     * 
     * @param string|array $roles Rol o roles permitidos ('admin', 'cobrador', 'cliente')
     * @return array Datos del usuario autenticado
     */
    public static function requireRole($roles) {
        $user = self::requireAuth();
        
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        if (!in_array($user['rol'], $roles)) {
            Response::forbidden('No tiene permisos para acceder a este recurso. Rol requerido: ' . implode(', ', $roles));
        }
        
        return $user;
    }
    
    /**
     * Requerir que sea admin
     * 
     * @return array Datos del usuario admin
     */
    public static function requireAdmin() {
        return self::requireRole('admin');
    }
    
    /**
     * Requerir que sea cobrador o admin
     * 
     * @return array Datos del usuario
     */
    public static function requireCobradorOrAdmin() {
        return self::requireRole(['admin', 'cobrador']);
    }
    
    /**
     * Obtener usuario actual (opcional, no lanza error si no está autenticado)
     * 
     * @return array|null Datos del usuario o null
     */
    public static function getCurrentUser() {
        return Auth::getCurrentUser();
    }
}

