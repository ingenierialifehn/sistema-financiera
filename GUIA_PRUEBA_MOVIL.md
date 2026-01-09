# Guía de Prueba - Acceso Móvil y Reportes de Agencia

## ✅ Cambios Implementados

### 1. Acceso Móvil en Red Local
- ✅ Configuración de sesiones para red local (`app/config/config.php`)
- ✅ Rutas relativas en redirecciones (`public/login.php`)
- ✅ Cookies compatibles con acceso por IP

### 2. Módulo de Reportes de Agencia
- ✅ Vista responsive con Bootstrap 5 (`public/admin/reportes-agencia.php`)
- ✅ Filtrado automático por agencia del usuario
- ✅ Selector de agencias para administradores

### 3. APIs de Reportes
- ✅ `app/api/reportes/recaudacion-dia.php` - Cobros del día
- ✅ `app/api/reportes/estado-cartera.php` - Estado de cartera por categorías
- ✅ `app/api/reportes/clientes-mora.php` - Clientes con +30 días de mora

### 4. Integración al Sistema
- ✅ Enlace agregado al sidebar del dashboard
- ✅ Documentación completa generada

## 🧪 Instrucciones de Prueba

### Paso 1: Obtener la IP del Servidor

En la computadora donde está instalado XAMPP, abre PowerShell o CMD y ejecuta:

```powershell
ipconfig
```

Busca la "Dirección IPv4" de tu adaptador de red WiFi. Ejemplo: `192.168.1.100`

### Paso 2: Configurar XAMPP (Si es necesario)

1. Abre el archivo de configuración de Apache:
   - `C:\xampp\apache\conf\httpd.conf`

2. Busca la línea que dice `Listen 80` y verifica que NO diga `Listen 127.0.0.1:80`
   - Debe ser solo: `Listen 80`

3. Reinicia Apache desde el panel de control de XAMPP

### Paso 3: Probar desde el Móvil

1. **Conecta tu móvil a la misma red WiFi** que la computadora con XAMPP

2. **Abre el navegador del móvil** (Chrome, Safari, etc.)

3. **Accede a la URL** (reemplaza con tu IP):
   ```
   http://192.168.1.100/sistema-financiera/public/login.php
   ```

4. **Inicia sesión** con tus credenciales normales

5. **Navega a Reportes de Agencia**:
   ```
   http://192.168.1.100/sistema-financiera/public/admin/reportes-agencia.php
   ```
   O usa el menú lateral → "Reportes de Agencia"

### Paso 4: Verificar Funcionalidad

#### Para Usuarios Normales:
- ✅ Debe ver solo los datos de su agencia
- ✅ NO debe ver el selector de agencias
- ✅ Los reportes deben cargar automáticamente

#### Para Administradores/Gerentes:
- ✅ Debe ver el selector de agencias en la parte superior
- ✅ Puede cambiar entre agencias
- ✅ Los datos se actualizan al cambiar de agencia

#### Reportes a Verificar:
1. **Recaudación del Día**
   - Total recaudado hoy
   - Desglose: Capital, Interés (4%), Gastos + Comisión (7%)
   - Tabla con detalle de cobros

2. **Estado de Cartera**
   - Gráfico de dona con distribución de clientes (A-E)
   - Gráfico de barras con montos en riesgo
   - Tarjetas con contadores por categoría

3. **Clientes en Mora**
   - Tabla con clientes que tienen más de 30 días de mora
   - Información detallada de cada cliente

## 🔧 Solución de Problemas

### Problema: No puedo acceder desde el móvil

**Solución 1: Verificar Firewall**
```powershell
# En PowerShell como Administrador:
netsh advfirewall firewall add rule name="Apache" dir=in action=allow protocol=TCP localport=80
```

**Solución 2: Verificar que ambos dispositivos estén en la misma red**
- Computadora y móvil deben estar conectados a la misma red WiFi
- No usar datos móviles en el celular

**Solución 3: Reiniciar Apache**
- Detener y volver a iniciar Apache en XAMPP

### Problema: Las sesiones no se mantienen

**Solución:**
- Verificar que el archivo `app/config/config.php` tenga la configuración de sesiones
- Limpiar cookies del navegador móvil
- Intentar en modo incógnito/privado

### Problema: Los reportes no cargan datos

**Solución 1: Verificar que existan datos**
```sql
-- Ejecutar en phpMyAdmin
SELECT COUNT(*) FROM pagos WHERE DATE(fecha_pago) = CURDATE();
SELECT COUNT(*) FROM prestamos WHERE estado = 'Activo';
```

**Solución 2: Verificar errores en consola**
- Abrir las herramientas de desarrollador del navegador (F12)
- Ver la pestaña "Console" para errores JavaScript
- Ver la pestaña "Network" para errores de API

**Solución 3: Verificar permisos**
- El usuario debe tener el permiso 'reportes' activo
- Verificar en la tabla `roles` que el permiso esté habilitado

### Problema: Error "id_agencia es requerido"

**Solución:**
- Verificar que el usuario tenga asignada una agencia en la tabla `colaboradores`
- Ejecutar:
```sql
SELECT c.id_agencia, a.nombre_agencia 
FROM colaboradores c 
LEFT JOIN agencias a ON c.id_agencia = a.id_agencia
WHERE c.id_colaborador = [ID_DEL_COLABORADOR];
```

## 📱 Características Responsive

El módulo está optimizado para diferentes tamaños de pantalla:

- **Móvil (< 768px)**: 
  - Tarjetas apiladas verticalmente
  - Tablas con scroll horizontal
  - Menú hamburguesa

- **Tablet (768px - 1024px)**:
  - Tarjetas en 2 columnas
  - Gráficos adaptados

- **Desktop (> 1024px)**:
  - Layout completo con sidebar
  - Tarjetas en 4 columnas
  - Gráficos en 2 columnas

## 🔄 Actualización Automática

Los datos se actualizan automáticamente cada 5 minutos. Para forzar una actualización manual:
- Recargar la página (pull to refresh en móvil)
- Los gráficos se regeneran con cada actualización

## 📊 Categorías de Riesgo

| Categoría | Días de Mora | Color | Descripción |
|-----------|--------------|-------|-------------|
| A | 0-7 días | Verde | Al día |
| B | 8-30 días | Azul | Mora temprana |
| C | 31-60 días | Amarillo | Mora media |
| D | 61-90 días | Rojo | Mora alta |
| E | >90 días | Morado | Mora crítica |

## 🎯 Próximos Pasos Recomendados

1. **Agregar más reportes**:
   - Proyección de cobranza semanal
   - Rendimiento de cobradores
   - Histórico de recaudación

2. **Exportación**:
   - Botón para exportar a Excel
   - Generar PDF de reportes
   - Compartir por WhatsApp

3. **Notificaciones**:
   - Alertas cuando un cliente pasa a categoría D o E
   - Resumen diario automático

4. **Dashboard gerencial**:
   - Comparativa entre agencias
   - KPIs (tasa de recuperación, mora promedio)
   - Metas vs. resultados

## 📞 Soporte

Si encuentras algún problema:
1. Revisar la consola del navegador (F12)
2. Verificar los logs de PHP en `C:\xampp\apache\logs\error.log`
3. Revisar la documentación en `ACTUALIZACIONES_MOVIL_REPORTES.md`

---

**Fecha de implementación**: <?php echo date('Y-m-d H:i:s'); ?>
**Versión**: 1.0.0
