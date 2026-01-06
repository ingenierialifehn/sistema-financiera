# Ejemplos de Uso del Sistema de Autenticación

## 📋 Índice
1. [Uso en API REST (Endpoints PHP)](#uso-en-api-rest)
2. [Uso en Páginas Web (PHP)](#uso-en-páginas-web)
3. [Uso desde JavaScript/jQuery](#uso-desde-javascript)
4. [Testing con Postman](#testing-con-postman)

---

## 🔌 Uso en API REST (Endpoints PHP)

### Proteger un endpoint con autenticación

```php
<?php
// /app/api/clientes/list.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';

// Requerir autenticación
$user = AuthMiddleware::requireAuth();

// El usuario está autenticado, continuar con la lógica
// $user contiene: id, usuario, nombre_completo, email, rol, estado

Response::success([
    'clientes' => [],
    'usuario_actual' => $user['nombre_completo']
]);
```

### Proteger un endpoint solo para admin

```php
<?php
// /app/api/admin/dashboard.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';

// Requerir que sea admin
$user = AuthMiddleware::requireAdmin();

// Solo admins pueden acceder aquí
Response::success(['dashboard_data' => []]);
```

### Proteger un endpoint para cobrador o admin

```php
<?php
// /app/api/pagos/registrar.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';

// Permitir cobrador o admin
$user = AuthMiddleware::requireCobradorOrAdmin();

// Lógica del endpoint
Response::success(['mensaje' => 'Pago registrado']);
```

### Proteger con rol específico

```php
<?php
// Requerir rol específico
$user = AuthMiddleware::requireRole('cliente');

// O múltiples roles
$user = AuthMiddleware::requireRole(['admin', 'cobrador']);
```

---

## 🌐 Uso en Páginas Web (PHP)

### Proteger una página web

```php
<?php
// /public/admin/dashboard.php

// Incluir el helper de autenticación
require_once __DIR__ . '/../auth_check.php';

// El usuario ya está autenticado y disponible en $user
// También disponible en $GLOBALS['current_user']

echo "Bienvenido, " . $user['nombre_completo'];
echo "Tu rol es: " . $user['rol'];
```

### Proteger página solo para admin

```php
<?php
// /public/admin/configuracion.php

require_once __DIR__ . '/../auth_check.php';
requireAdmin(); // Solo admins pueden acceder

// Continuar con el código de la página
```

### Proteger página para cobrador o admin

```php
<?php
// /public/cobrador/registrar-pago.php

require_once __DIR__ . '/../auth_check.php';
requireCobradorOrAdmin();

// Continuar con el código
```

### Acceder a datos del usuario en la página

```php
<?php
require_once __DIR__ . '/../auth_check.php';

// Opción 1: Variable global $user
$nombre = $user['nombre_completo'];
$rol = $user['rol'];

// Opción 2: Variable global alternativa
$currentUser = $GLOBALS['current_user'];
$email = $currentUser['email'];
```

---

## 💻 Uso desde JavaScript/jQuery

### Login con AJAX

```javascript
// Login desde formulario
$('#loginForm').on('submit', function(e) {
    e.preventDefault();
    
    const usuario = $('#usuario').val();
    const password = $('#password').val();
    
    $.ajax({
        url: '/app/api/auth/login.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            usuario: usuario,
            password: password
        }),
        success: function(response) {
            if (response.success) {
                // Guardar token
                localStorage.setItem('auth_token', response.data.token);
                
                // Redirigir según rol
                const role = response.data.user.rol;
                if (role === 'admin') {
                    window.location.href = '/public/admin/dashboard.php';
                }
            }
        },
        error: function(xhr) {
            console.error('Error:', xhr.responseJSON);
        }
    });
});
```

### Usar token en peticiones API

```javascript
// Obtener token del localStorage
const token = localStorage.getItem('auth_token');

// Realizar petición autenticada
$.ajax({
    url: '/app/api/clientes/list.php',
    method: 'GET',
    headers: {
        'Authorization': 'Bearer ' + token
    },
    success: function(response) {
        console.log('Datos:', response.data);
    },
    error: function(xhr) {
        if (xhr.status === 401) {
            // Token expirado, redirigir a login
            window.location.href = '/public/login.php';
        }
    }
});
```

### Logout desde JavaScript

```javascript
// Logout
$.ajax({
    url: '/app/api/auth/logout.php',
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
    },
    success: function() {
        // Limpiar localStorage
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        
        // Redirigir a login
        window.location.href = '/public/login.php';
    }
});
```

### Usar Fetch API (alternativa moderna)

```javascript
// Login con Fetch
async function login(usuario, password) {
    try {
        const response = await fetch('/app/api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                usuario: usuario,
                password: password
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            localStorage.setItem('auth_token', data.data.token);
            return data.data;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error en login:', error);
        throw error;
    }
}

// Petición autenticada con Fetch
async function getClientes() {
    const token = localStorage.getItem('auth_token');
    
    const response = await fetch('/app/api/clientes/list.php', {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    
    if (response.status === 401) {
        window.location.href = '/public/login.php';
        return;
    }
    
    const data = await response.json();
    return data.data;
}
```

---

## 🧪 Testing con Postman

### Importar colección

1. Abre Postman
2. Click en "Import"
3. Selecciona el archivo `postman_collection.json`
4. La colección incluye:
   - Login (usuario y password)
   - Login con email
   - Logout
   - Ejemplos de errores

### Configurar variables

1. Abre la colección "Sistema Financiero - API Auth"
2. Ve a la pestaña "Variables"
3. Ajusta `base_url` según tu entorno:
   - Local: `http://localhost/sistema-financiera`
   - Producción: `https://tudominio.com`

### Usar autenticación automática

La colección está configurada para:
- Guardar automáticamente el token después del login
- Usar el token en todas las peticiones (Bearer Token)

### Ejemplo de petición manual

**Login:**
```
POST http://localhost/sistema-financiera/app/api/auth/login.php
Content-Type: application/json

{
    "usuario": "admin",
    "password": "admin123"
}
```

**Petición autenticada:**
```
GET http://localhost/sistema-financiera/app/api/clientes/list.php
Authorization: Bearer {token_obtenido_del_login}
```

---

## 🔒 Seguridad

### Buenas prácticas implementadas

✅ **Prevención SQL Injection**: Uso de PDO prepared statements  
✅ **Prevención XSS**: Sanitización con `htmlspecialchars`  
✅ **Session Fixation**: Regeneración de ID de sesión  
✅ **Token expiración**: Tokens con fecha de expiración  
✅ **Logs de actividad**: Registro de intentos de login  
✅ **Validación de datos**: Validación y sanitización de entradas  

### Recomendaciones adicionales

- Cambiar contraseña por defecto del admin
- Usar HTTPS en producción
- Configurar `TOKEN_SECRET` único en producción
- Revisar logs de actividad regularmente
- Implementar rate limiting para login

---

## 📝 Notas

- El sistema soporta tanto tokens (API) como sesiones PHP (web)
- Los tokens se guardan en localStorage para uso en JavaScript
- Las sesiones PHP se usan automáticamente en páginas web
- El middleware `AuthMiddleware` es solo para API REST
- El helper `auth_check.php` es solo para páginas web PHP

