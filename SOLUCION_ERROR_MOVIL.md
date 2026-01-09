# 🔧 Solución de Problemas - Error de Conexión en Móvil

## Problema Reportado
El sistema carga el login en el teléfono, pero muestra "error de conexión" al intentar iniciar sesión.

## ✅ Solución Implementada

### 1. Corrección de URLs Dinámicas
**Archivo modificado:** `public/login.php`

**Cambio realizado:**
- ❌ **Antes:** Usaba `baseUrl` de PHP que podía generar URLs con `localhost`
- ✅ **Ahora:** Construye la URL dinámicamente en JavaScript usando `window.location`

**Beneficio:** La URL se adapta automáticamente a la dirección desde donde se accede (localhost o IP de red local)

### 2. Herramientas de Diagnóstico Creadas

#### A. Página de Test de Conexión
**Archivo:** `public/test-conexion.html`

**Cómo usarla:**
1. **Desde el móvil**, accede a:
   ```
   http://[IP-DEL-PC]/sistema-financiera/public/test-conexion.html
   ```
   Ejemplo: `http://192.168.1.100/sistema-financiera/public/test-conexion.html`

2. **Ejecuta las pruebas en orden:**
   - **Test 1:** Diagnóstico del servidor
   - **Test 2:** Verificar API de login
   - **Test 3:** Probar login completo

3. **Revisa los resultados:**
   - ✅ Verde = Todo bien
   - ❌ Rojo = Hay un problema
   - ℹ️ Azul = Información

#### B. API de Diagnóstico
**Archivo:** `public/diagnostico.php`

**Cómo usarla:**
```
http://[IP-DEL-PC]/sistema-financiera/public/diagnostico.php
```

Muestra información detallada del servidor, cliente y configuración.

## 🔍 Pasos para Diagnosticar el Problema

### Paso 1: Verificar Conectividad Básica

1. **Desde el móvil**, abre el navegador y accede a:
   ```
   http://[TU-IP]/sistema-financiera/public/test-conexion.html
   ```

2. **Presiona el botón "1. Test de Diagnóstico"**
   - Si funciona ✅: El servidor está accesible
   - Si falla ❌: Hay un problema de red o firewall

### Paso 2: Verificar API de Login

1. **Presiona el botón "2. Test API de Login"**
   - Si funciona ✅: La API responde correctamente
   - Si falla ❌: Hay un problema con la API o CORS

### Paso 3: Probar Login Real

1. **Presiona el botón "3. Test Login Completo"**
2. **Ingresa tus credenciales reales**
   - Si funciona ✅: El login está funcionando
   - Si falla ❌: Revisa las credenciales o la base de datos

## 🛠️ Soluciones Comunes

### Problema 1: "Failed to fetch" o "Network error"

**Causa:** Firewall de Windows bloqueando conexiones entrantes

**Solución:**
```powershell
# Ejecutar en PowerShell como Administrador:
netsh advfirewall firewall add rule name="Apache HTTP" dir=in action=allow protocol=TCP localport=80
netsh advfirewall firewall add rule name="Apache HTTPS" dir=in action=allow protocol=TCP localport=443
```

### Problema 2: "CORS policy" error

**Causa:** Configuración de CORS en el servidor

**Solución:** Ya está configurado en `app/config/config.php`, pero verifica que Apache esté reiniciado.

### Problema 3: "404 Not Found" en la API

**Causa:** URL incorrecta o mod_rewrite no habilitado

**Solución:**
1. Verifica que la URL sea correcta usando la página de test
2. En XAMPP, verifica que `mod_rewrite` esté habilitado en `httpd.conf`

### Problema 4: Timeout o respuesta muy lenta

**Causa:** Configuración de red o PHP

**Solución:**
1. Verifica que ambos dispositivos estén en la misma red WiFi
2. Aumenta el timeout en `php.ini`:
   ```ini
   max_execution_time = 300
   default_socket_timeout = 300
   ```

### Problema 5: "Base URL está vacía"

**Causa:** Ya solucionado con la actualización

**Verificación:**
- Abre la consola del navegador (F12 en PC, inspeccionar en móvil)
- Deberías ver logs como:
  ```
  Base URL construida: http://192.168.1.100/sistema-financiera
  Protocol: http:
  Host: 192.168.1.100
  Base Path: /sistema-financiera
  ```

## 📱 Verificación Rápida desde el Móvil

### Opción 1: Consola del Navegador (Chrome Android)

1. Abre Chrome en el móvil
2. Ve a `chrome://inspect` en Chrome de PC
3. Conecta el móvil por USB
4. Inspecciona la página del móvil
5. Ve a la pestaña "Console" para ver errores

### Opción 2: Usar la Página de Test

1. Accede a `test-conexion.html` desde el móvil
2. Ejecuta las 3 pruebas
3. Toma captura de pantalla de los resultados
4. Revisa los mensajes de error

## 🔐 Verificar Configuración de Sesiones

Si el login funciona pero no mantiene la sesión:

1. **Verifica cookies en el navegador móvil:**
   - Asegúrate de que las cookies estén habilitadas
   - No uses modo incógnito/privado

2. **Verifica la configuración de sesión:**
   - Ya está configurada en `app/config/config.php`
   - `session.cookie_domain` está vacío (permite IP)
   - `session.cookie_samesite` es 'Lax'

## 📊 Información para Soporte

Si el problema persiste, recopila esta información:

1. **Desde `test-conexion.html`:**
   - Resultados de las 3 pruebas
   - Capturas de pantalla de errores

2. **Desde `diagnostico.php`:**
   - Copia el JSON completo

3. **Información del móvil:**
   - Navegador y versión
   - Sistema operativo
   - IP del móvil

4. **Información del servidor:**
   - IP del servidor
   - Versión de XAMPP
   - Versión de PHP

## 🎯 Prueba Rápida de 30 Segundos

1. Abre el móvil
2. Ve a: `http://[TU-IP]/sistema-financiera/public/test-conexion.html`
3. Presiona los 3 botones en orden
4. Si todos son ✅ verdes, el sistema funciona
5. Si alguno es ❌ rojo, lee el mensaje de error

## 📞 URLs de Prueba

Reemplaza `[TU-IP]` con la IP de tu PC (ejemplo: 192.168.1.100):

- **Test de Conexión:** `http://[TU-IP]/sistema-financiera/public/test-conexion.html`
- **Diagnóstico:** `http://[TU-IP]/sistema-financiera/public/diagnostico.php`
- **Login:** `http://[TU-IP]/sistema-financiera/public/login.php`

---

**Última actualización:** 2026-01-09
**Versión:** 1.1.0
