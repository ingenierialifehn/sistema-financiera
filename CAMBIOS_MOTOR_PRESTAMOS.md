# Ajustes al Motor de Préstamos - Resumen de Cambios

## Fecha: 2026-01-07

### Objetivo
Mover la generación de cuotas del paso de Desembolso al paso de Análisis, y mejorar el flujo de Operaciones con asignación de personal y vista previa de calendario.

---

## Cambios Implementados

### 1. **Módulo de Análisis - Generación Automática de Cuotas**

**Archivo modificado:** `app/api/prestamos/update_status.php`

**Cambios:**
- Cuando el Analista cambia el estado a **'Pendiente de Operaciones'**, el sistema ahora:
  - Elimina cuotas existentes (si las hay, en caso de re-análisis)
  - Genera automáticamente el plan de pagos completo
  - Aplica las reglas de fechas según modalidad:
    - **Diario**: Lunes a Viernes (salta fines de semana)
    - **Semanal**: Cada 7 días
    - **Catorcenal**: Cada 14 días
    - **Mensual**: Mismo día cada mes

**Código agregado:**
```php
// If changing to 'Pendiente de Operaciones', generate payment schedule (cuotas)
if ($nuevoEstado === 'Pendiente de Operaciones') {
    // Delete existing cuotas if any (in case of re-analysis)
    $stmtDelete = $db->prepare("DELETE FROM cuotas WHERE prestamo_id = ?");
    $stmtDelete->execute([$prestamoId]);

    // Generate cuotas based on modality
    PrestamoHelper::generateCuotasModalidad(...);
}
```

---

### 2. **Base de Datos - Nuevos Campos de Asignación**

**Script ejecutado:** `app/api/update_prestamos_asignacion.php`

**Campos agregados a la tabla `prestamos`:**
- `asesor_creditos_id` (INT NULL) - Usuario asignado para cobro
- `oficial_desembolsos_id` (INT NULL) - Usuario asignado para entrega

**Relaciones:**
- Foreign keys hacia la tabla `usuarios`
- ON DELETE SET NULL (si se elimina el usuario, el campo queda NULL)

---

### 3. **Módulo de Operaciones - Vista Previa y Asignación**

**Archivo modificado:** `public/admin/operaciones.php`

**Mejoras implementadas:**

#### A. Vista Previa del Calendario de Cuotas
- Se muestra una tabla con todas las cuotas generadas por el Analista
- Incluye: número de cuota, fecha de vencimiento y monto
- Carga dinámica mediante AJAX desde `app/api/prestamos/cuotas.php`

#### B. Asignación de Personal
- Dos selectores obligatorios:
  1. **Asesor de Créditos (Cobro)**: Usuario que hará el seguimiento de cobro
  2. **Oficial de Desembolsos (Entrega)**: Usuario que entregará el dinero
- Los selectores se llenan con usuarios activos del sistema
- Si ya hay asignación previa, se pre-selecciona

#### C. Validación Mejorada
- El botón "Enviar a Desembolso" solo se habilita si:
  - ✓ Todos los checkboxes de documentación están marcados
  - ✓ Se ha seleccionado un Asesor de Créditos
  - ✓ Se ha seleccionado un Oficial de Desembolsos

**JavaScript actualizado:**
```javascript
function validateChecklist() {
    const allChecked = checks.every(id => document.getElementById(id).checked);
    const asesorSelected = document.getElementById('asesor_creditos_id').value !== '';
    const oficialSelected = document.getElementById('oficial_desembolsos_id').value !== '';
    
    if (allChecked && asesorSelected && oficialSelected) {
        // Habilitar botón
    }
}
```

---

### 4. **Nuevos Endpoints API**

#### A. `app/api/prestamos/asignar_personal.php`
- **Método:** POST
- **Parámetros:**
  - `prestamo_id` (requerido)
  - `asesor_creditos_id` (opcional)
  - `oficial_desembolsos_id` (opcional)
- **Función:** Asignar personal al préstamo

#### B. `app/api/usuarios/list.php`
- **Método:** GET
- **Función:** Listar usuarios activos del sistema
- **Retorna:** id_usuario, username, nombre_completo, rol_nombre

---

### 5. **Flujo de Estados Actualizado**

**Antes:**
```
Solicitado → En Análisis → Verificación de Campo → Pendiente de Operaciones → 
Aprobado → Listo para Entrega → [Desembolso genera cuotas] → Activo
```

**Ahora:**
```
Solicitado → En Análisis → Verificación de Campo → 
Pendiente de Operaciones [GENERA CUOTAS] → 
Aprobado → Listo para Entrega [ASIGNA PERSONAL] → 
Activo [SOLO CONFIRMA ENTREGA]
```

---

## Instrucciones de Uso

### Para el Analista:
1. Revisar la solicitud de préstamo
2. Ajustar términos si es necesario (monto, plazo, tasa, modalidad)
3. Cambiar estado a **"Pendiente de Operaciones"**
4. ✨ **El sistema genera automáticamente el calendario de cuotas**

### Para el Oficial de Operaciones:
1. Abrir el préstamo en el módulo de Operaciones
2. Revisar la **Vista Previa del Calendario** (ya generado)
3. Imprimir documentos (Contrato, Pagaré, Plan de Pagos)
4. Validar documentación física (checkboxes)
5. **Asignar Asesor de Créditos** (quien cobrará)
6. **Asignar Oficial de Desembolsos** (quien entregará)
7. Enviar a "Listo para Entrega"

### Para el Oficial de Desembolsos:
1. Ver préstamos asignados en su ruta
2. Confirmar entrega del dinero al cliente
3. El préstamo pasa a estado **"Activo"**
4. Las cuotas ya están generadas y listas para cobro

---

## Ventajas del Nuevo Flujo

✅ **Menos errores**: Las cuotas se generan una sola vez, en el momento correcto
✅ **Mejor trazabilidad**: Se sabe quién es responsable de cobro y entrega
✅ **Vista previa**: Operaciones puede revisar el calendario antes de imprimir
✅ **Simplicidad**: El desembolso solo confirma, no calcula
✅ **Consistencia**: Las fechas se calculan con las reglas correctas desde el inicio

---

## Archivos Modificados

1. `app/api/prestamos/update_status.php` - Generación de cuotas en análisis
2. `public/admin/operaciones.php` - Vista previa y asignación de personal
3. `app/api/update_prestamos_asignacion.php` - Script de migración (ejecutado)

## Archivos Creados

1. `app/api/prestamos/asignar_personal.php` - Endpoint de asignación
2. `app/api/usuarios/list.php` - Endpoint de listado de usuarios

---

## Notas Técnicas

- Las cuotas se eliminan y regeneran si el préstamo vuelve a "Pendiente de Operaciones"
- La asignación de personal es opcional en la base de datos (NULL permitido)
- Los usuarios inactivos no aparecen en los selectores
- El estado "Activo" ahora es válido en update_status.php

---

## Próximos Pasos Sugeridos

1. ✅ Probar el flujo completo con un préstamo de prueba
2. ⚠️ Verificar que los permisos de usuario permitan acceso a los nuevos endpoints
3. 📋 Capacitar al personal sobre el nuevo flujo
4. 🔍 Monitorear que las cuotas se generen correctamente según modalidad

---

**Implementado por:** Antigravity AI
**Fecha:** 2026-01-07
