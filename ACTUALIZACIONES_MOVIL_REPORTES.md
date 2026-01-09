# Actualizaciones para Acceso Móvil y Reportes de Agencia

## Resumen de Cambios Implementados

### 1. Configuración para Acceso Móvil en Red Local

#### Archivo: `app/config/config.php`
- ✅ Agregada configuración de sesión para acceso móvil
- ✅ Configuración de cookies compatible con acceso por IP de red local
- ✅ `session.cookie_domain` vacío para permitir acceso por IP
- ✅ `session.cookie_samesite` configurado como 'Lax' para red local

#### Archivo: `public/login.php`
- ✅ Redirecciones cambiadas a rutas relativas (no más localhost hardcoded)
- ✅ Construcción dinámica de rutas basada en `$_SERVER['SCRIPT_NAME']`
- ✅ Compatible con acceso desde cualquier dispositivo en la red local

### 2. Módulo de Reportes de Agencia (Vista Móvil)

#### Archivo: `public/admin/reportes-agencia.php`
**Características:**
- ✅ Diseño 100% responsive con Bootstrap 5
- ✅ Optimizado para visualización en dispositivos móviles
- ✅ Filtrado automático por agencia del usuario en sesión
- ✅ Selector de agencias para administradores/gerentes

**Reportes Incluidos:**

1. **Recaudación del Día**
   - Total recaudado hoy
   - Desglose: Capital, Interés (4%), Gastos + Comisión (7%)
   - Tabla detallada de cobros con cliente, montos y hora

2. **Estado de Cartera**
   - Gráfico de dona: Distribución de clientes por categoría (A-E)
   - Gráfico de barras: Monto en riesgo por categoría
   - Tarjetas con contadores de clientes y montos por categoría

3. **Clientes en Mora (+30 días)**
   - Tabla con clientes que tienen más de 30 días de mora
   - Información: Nombre, ID préstamo, días mora, categoría, monto en riesgo
   - Badges de colores según categoría de riesgo

**Categorías de Riesgo:**
- **A** (Verde): 0-7 días - Al día
- **B** (Azul): 8-30 días - Mora temprana
- **C** (Amarillo): 31-60 días - Mora media
- **D** (Rojo): 61-90 días - Mora alta
- **E** (Morado): >90 días - Mora crítica

### 3. APIs de Reportes Creadas

#### `app/api/reportes/recaudacion-dia.php`
- Obtiene cobros del día actual
- Calcula desglose automático del 11% (4% + 4% + 3%)
- Filtra por agencia del usuario (o agencia seleccionada si es admin)
- Retorna resumen y detalle de cobros

#### `app/api/reportes/estado-cartera.php`
- Calcula categorías de riesgo (A-E) basado en días de mora
- Cuenta clientes por categoría
- Suma montos en riesgo por categoría
- Calcula totales y porcentaje de mora

#### `app/api/reportes/clientes-mora.php`
- Lista clientes con más de 30 días de mora
- Ordena por días de mora y monto en riesgo
- Incluye categoría de riesgo calculada
- Filtra automáticamente por agencia

### 4. Seguridad y Permisos

- ✅ Todos los endpoints verifican autenticación con `Auth::requireAuth()`
- ✅ Usuarios no-admin solo ven datos de su agencia
- ✅ Administradores/Gerentes pueden seleccionar cualquier agencia
- ✅ Filtrado automático basado en `$user['id_agencia']`

## Instrucciones de Uso

### Acceso desde Dispositivos Móviles

1. **Obtener la IP del servidor:**
   ```bash
   ipconfig
   # Buscar "Dirección IPv4" de tu adaptador de red
   # Ejemplo: 192.168.1.100
   ```

2. **Acceder desde el móvil:**
   - Asegurarse de que el móvil esté en la misma red WiFi
   - Abrir navegador y acceder a: `http://192.168.1.100/sistema-financiera/public/login.php`
   - Iniciar sesión normalmente

3. **Acceder a Reportes de Agencia:**
   - URL: `http://192.168.1.100/sistema-financiera/public/admin/reportes-agencia.php`
   - O agregar enlace en el menú del dashboard

### Configuración de XAMPP

Para permitir acceso desde otros dispositivos:

1. Editar `httpd.conf` de Apache
2. Buscar `Listen 80` y verificar que no esté restringido a localhost
3. Reiniciar Apache

### Agregar al Menú del Dashboard

Agregar en el sidebar de `dashboard.php`:

```html
<li class="nav-item">
    <a class="nav-link" href="reportes-agencia.php">
        <i class="fas fa-chart-line"></i>
        <span>Reportes de Agencia</span>
    </a>
</li>
```

## Características Técnicas

### Responsive Design
- Breakpoints optimizados para móviles, tablets y desktop
- Tarjetas que se apilan en móviles (col-12)
- Tablas con scroll horizontal en pantallas pequeñas
- Gráficos que se adaptan al tamaño de pantalla

### Actualización Automática
- Los datos se actualizan automáticamente cada 5 minutos
- Spinners de carga mientras se obtienen datos
- Manejo de errores con mensajes en consola

### Gráficos Interactivos
- Chart.js para visualizaciones
- Gráfico de dona para distribución de clientes
- Gráfico de barras para montos en riesgo
- Colores consistentes con las categorías de riesgo

## Próximos Pasos Sugeridos

1. **Agregar más reportes:**
   - Proyección de cobranza semanal/mensual
   - Rendimiento de cobradores
   - Histórico de recaudación

2. **Exportación de datos:**
   - Botón para exportar a Excel/PDF
   - Compartir reportes por WhatsApp

3. **Notificaciones:**
   - Alertas cuando un cliente pasa a categoría D o E
   - Resumen diario por email/SMS

4. **Dashboard gerencial:**
   - Comparativa entre agencias
   - Indicadores KPI (tasa de recuperación, mora promedio, etc.)
   - Metas vs. resultados

## Notas Importantes

- Las sesiones ahora funcionan correctamente en red local
- No es necesario cambiar URLs manualmente
- El sistema detecta automáticamente la URL base
- Compatible con localhost y acceso por IP simultáneamente
