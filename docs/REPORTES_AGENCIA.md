# Módulo de Reportes de Agencia

## Descripción General

El módulo **Reportes de Agencia** es un sistema de reportes con seguridad automática por sesión que permite a cada agencia visualizar únicamente sus propios datos financieros y operacionales.

## Características de Seguridad

### Filtrado Automático por Sesión
- **Todos** los reportes filtran automáticamente por `id_agencia` del usuario en sesión
- No es posible ver datos de otras agencias
- La seguridad se aplica a nivel de API (backend), no solo en el frontend

### Implementación de Seguridad
```php
// En cada API de reportes
$idAgencia = $_SESSION['id_agencia'] ?? null;

if (!$idAgencia) {
    throw new Exception('No se pudo determinar la agencia del usuario');
}

// Todas las queries incluyen:
WHERE p.id_agencia = ?
```

## Reportes Disponibles

### 1. Resumen de Recaudación Diaria

**Ubicación API:** `/app/api/reportes/recaudacion_diaria.php`

**Datos mostrados:**
- Total cobrado en la fecha actual
- Desglose contable:
  - Capital (monto principal)
  - Interés 4%
  - Gastos 4%
  - Comisión 3%
- Lista de transacciones del día con:
  - Nombre del cliente
  - Número de cuota
  - Monto pagado
  - Desglose por componente
  - Hora del pago

**Cálculo del desglose:**
El sistema obtiene los valores de las columnas `capital_cuota`, `interes_cuota`, `gastos_cuota` y `comision_cuota` de la tabla `cuotas` para cada pago realizado en el día.

### 2. Estado de Cartera y Mora

**Ubicación API:** `/app/api/reportes/estado_cartera.php`

**Datos mostrados:**
- **Capital Total en la Calle:** Suma de todos los saldos pendientes de préstamos activos
- **Resumen por Categorías de Riesgo (A, B, C, D, E):**
  - Cantidad de clientes en cada categoría
  - Monto total en riesgo
  - Promedio de días en mora
- **Clientes con más de 30 días de atraso:**
  - Solo muestra categorías C, D y E
  - Información completa del cliente
  - Días de mora
  - Saldo pendiente
  - Próxima cuota vencida

**Categorías de Riesgo:**
- **A:** 0 días de mora (Al día)
- **B:** 1-30 días de mora (Mora leve)
- **C:** 31-60 días de mora (Mora moderada)
- **D:** 61-90 días de mora (Mora alta)
- **E:** Más de 90 días de mora (Mora crítica)

**Cálculo dinámico:**
El sistema calcula la categoría de riesgo en tiempo real basándose en la cuota más antigua vencida de cada préstamo activo.

### 3. Desembolsos del Periodo

**Ubicación API:** `/app/api/reportes/desembolsos_periodo.php`

**Parámetros:**
- `fecha_desde`: Fecha inicial del periodo (default: primer día del mes actual)
- `fecha_hasta`: Fecha final del periodo (default: fecha actual)

**Datos mostrados:**
- **Resumen:**
  - Cantidad total de préstamos desembolsados
  - Monto total colocado
  - Promedio por préstamo
- **Resumen por Modalidad:**
  - Cantidad y monto por cada modalidad (Diario, Semanal, Catorcenal, Mensual)
- **Detalle de Desembolsos:**
  - Fecha de desembolso
  - Cliente y DNI
  - Monto capital
  - Modalidad y plazo
  - Total a pagar
  - Oficial que realizó el desembolso

## Interfaz de Usuario

### Diseño
- **Tabs de navegación** para cambiar entre reportes
- **Cards estadísticas** con colores diferenciados
- **Tablas responsivas** con scroll horizontal
- **Diseño profesional** con gradientes y sombras

### Funcionalidad de Impresión

Cada reporte incluye un botón **"Imprimir Reporte"** que:
- Genera una versión optimizada para PDF/impresión
- Incluye encabezado con:
  - Nombre del reporte
  - Fecha del reporte
  - Nombre de la agencia
- Oculta elementos de navegación y botones
- Optimiza el layout para papel

**Uso:**
```javascript
function imprimirReporte(tipo) {
    window.print();
}
```

El CSS `@media print` se encarga de:
- Mostrar solo el área de impresión
- Ocultar tabs, filtros y botones
- Agregar encabezado de impresión
- Evitar saltos de página en medio de secciones

## Archivos del Módulo

### Backend (APIs)
```
app/api/reportes/
├── recaudacion_diaria.php    # Reporte de cobros del día
├── estado_cartera.php         # Reporte de cartera y mora
└── desembolsos_periodo.php    # Reporte de desembolsos
```

### Frontend
```
public/admin/
└── reportes_agencia.php       # Interfaz principal del módulo
```

### Navegación
```
public/admin/includes/
└── sidebar.php                # Enlace agregado en el menú
```

## Permisos Requeridos

El módulo requiere el permiso `reportes` para ser visible en el menú:
```php
<?php if (Auth::hasPermission('reportes')): ?>
    <!-- Enlace al módulo -->
<?php endif; ?>
```

## Flujo de Datos

1. **Usuario accede al módulo** → `reportes_agencia.php`
2. **JavaScript carga datos** → Llama a las APIs correspondientes
3. **API valida sesión** → Obtiene `id_agencia` de `$_SESSION`
4. **API ejecuta queries** → Filtra por `id_agencia`
5. **API retorna JSON** → Datos solo de la agencia del usuario
6. **JavaScript renderiza** → Muestra datos en la interfaz

## Ejemplo de Uso

### Acceso al módulo
1. Iniciar sesión con un usuario asignado a una agencia
2. Navegar a **Reportes de Agencia** en el menú lateral
3. El sistema automáticamente muestra solo los datos de tu agencia

### Ver Recaudación Diaria
1. El tab "Recaudación Diaria" se carga automáticamente
2. Muestra el total cobrado hoy
3. Desglose contable automático
4. Lista de todas las transacciones del día

### Ver Estado de Cartera
1. Click en tab "Estado de Cartera"
2. Visualiza el capital total en la calle
3. Revisa la distribución por categorías de riesgo
4. Identifica clientes con más de 30 días de mora

### Ver Desembolsos
1. Click en tab "Desembolsos"
2. Ajusta el rango de fechas si es necesario
3. Click en "Buscar"
4. Visualiza resumen y detalle de desembolsos

### Imprimir Reporte
1. Navega al reporte deseado
2. Click en "Imprimir Reporte"
3. Se abre el diálogo de impresión del navegador
4. Selecciona impresora o "Guardar como PDF"

## Notas Técnicas

### Cálculo de Categorías de Riesgo
Las categorías se calculan dinámicamente en PHP usando la lógica de `ClienteHelper::calcularCategoriaRiesgo()`:

```php
// Obtener cuota más antigua vencida
$cuotaMasAntigua = // Query a la base de datos

// Calcular días de mora
$diasMora = 0;
if ($cuotaMasAntigua) {
    $venc = new DateTime($cuotaMasAntigua);
    $hoy = new DateTime();
    if ($venc < $hoy) {
        $diff = $hoy->diff($venc);
        $diasMora = $diff->days;
    }
}

// Determinar categoría
if ($diasMora == 0) $categoria = 'A';
elseif ($diasMora <= 30) $categoria = 'B';
elseif ($diasMora <= 60) $categoria = 'C';
elseif ($diasMora <= 90) $categoria = 'D';
else $categoria = 'E';
```

### Optimización de Queries
- Se utilizan subconsultas para calcular saldos pendientes
- Los datos se agrupan en memoria (PHP) para evitar queries complejas
- Se usa `IFNULL` para manejar casos sin pagos

### Compatibilidad
- Compatible con todos los navegadores modernos
- Responsive design para tablets y móviles
- Impresión optimizada para Chrome, Firefox, Edge

## Mantenimiento

### Agregar un nuevo reporte
1. Crear API en `app/api/reportes/nuevo_reporte.php`
2. Incluir filtro de seguridad por `id_agencia`
3. Agregar tab en `reportes_agencia.php`
4. Crear función JavaScript para cargar datos
5. Agregar estilos de impresión si es necesario

### Modificar cálculos
- Los cálculos de categorías están en `estado_cartera.php`
- Los desgloses contables usan las columnas de la tabla `cuotas`
- Cualquier cambio debe mantener el filtro de seguridad

## Troubleshooting

### "No se pudo determinar la agencia del usuario"
- Verificar que el usuario tenga `id_agencia` en la sesión
- Revisar que `Auth::requireLogin()` se ejecute correctamente
- Confirmar que el usuario esté asignado a una agencia en la BD

### Los reportes no muestran datos
- Verificar que existan datos para la agencia en la fecha/periodo
- Revisar que los préstamos tengan `id_agencia` asignado
- Confirmar que las cuotas estén marcadas como 'pagada' para recaudación

### La impresión no funciona correctamente
- Verificar que el CSS `@media print` esté cargado
- Probar en diferentes navegadores
- Revisar la configuración de impresión del navegador

## Seguridad

### Validaciones implementadas
- ✅ Filtro obligatorio por `id_agencia` en todas las APIs
- ✅ Validación de sesión activa
- ✅ Uso de prepared statements (prevención de SQL injection)
- ✅ Sanitización de salida JSON
- ✅ No hay parámetros de agencia manipulables por el usuario

### Recomendaciones
- Mantener siempre el filtro `WHERE id_agencia = ?` en todas las queries
- No exponer el `id_agencia` como parámetro GET/POST
- Validar permisos con `Auth::hasPermission('reportes')`
- Registrar accesos a reportes en logs (futuro)
