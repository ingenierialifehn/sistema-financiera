# Test de URLs - Guía de Verificación

## 🔍 Verificar que las rutas funcionen correctamente

### 1. Verificar configuración

Abre `app/config/config.php` y verifica:
```php
define('BASE_URL', 'http://localhost/AplicacionesJFCC/sistema-financiera');
```

### 2. URLs que deberían funcionar

Con tu configuración actual, estas URLs deberían funcionar:

#### Páginas Web:
- **Login**: `http://localhost/AplicacionesJFCC/sistema-financiera/public/login.php`
- **Logout**: `http://localhost/AplicacionesJFCC/sistema-financiera/public/logout.php`
- **Ejemplo protegido**: `http://localhost/AplicacionesJFCC/sistema-financiera/public/ejemplo-pagina-protegida.php`

#### API:
- **Login API**: `http://localhost/AplicacionesJFCC/sistema-financiera/app/api/auth/login.php`
- **Logout API**: `http://localhost/AplicacionesJFCC/sistema-financiera/app/api/auth/logout.php`

### 3. Cómo probar

1. **Abre el navegador** y ve a:
   ```
   http://localhost/AplicacionesJFCC/sistema-financiera/public/login.php
   ```

2. **Intenta hacer login** con:
   - Usuario: `admin`
   - Password: `admin123`

3. **Verifica la consola del navegador** (F12) para ver si hay errores:
   - Si hay errores 404, las rutas no están correctas
   - Si hay errores 500, hay un problema en el servidor

4. **Verifica la pestaña Network** para ver las peticiones:
   - La petición a `/app/api/auth/login.php` debería ser exitosa (200)

### 4. Solución de problemas

#### Si la página no carga:
- Verifica que XAMPP esté corriendo
- Verifica que el puerto sea 80 (o el que configuraste)
- Verifica la ruta en el navegador

#### Si el login no funciona:
- Abre la consola del navegador (F12)
- Ve a la pestaña Network
- Busca la petición a `login.php`
- Revisa la respuesta del servidor

#### Si hay errores de ruta:
- Verifica que `BASE_URL` en `config.php` esté correcto
- Verifica que no haya barras duplicadas (`//`)
- Las rutas ahora usan `base_url()` que debería funcionar automáticamente

### 5. Debug rápido

Agrega esto temporalmente en `public/login.php` después de la línea 29:

```php
// Debug (eliminar en producción)
echo "<!-- BASE_URL: " . getBaseUrl() . " -->";
echo "<!-- API URL: " . base_url('app/api/auth/login.php') . " -->";
```

Esto te mostrará en el HTML las URLs que se están generando.

### 6. Verificar que la función base_url() funciona

Crea un archivo temporal `test-urls.php` en la raíz:

```php
<?php
require_once __DIR__ . '/app/config/config.php';

echo "BASE_URL definido: " . BASE_URL . "<br>";
echo "getBaseUrl(): " . getBaseUrl() . "<br>";
echo "base_url('public/login.php'): " . base_url('public/login.php') . "<br>";
echo "base_url('app/api/auth/login.php'): " . base_url('app/api/auth/login.php') . "<br>";
```

Visita: `http://localhost/AplicacionesJFCC/sistema-financiera/test-urls.php`

Deberías ver las URLs correctas generadas.

