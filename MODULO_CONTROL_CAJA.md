# ✅ Módulo de Control de Caja - Apertura y Cierre

**Fecha de Implementación:** 2026-01-06  
**Estado:** ✅ COMPLETADO

---

## 📋 Características Implementadas

### 1. Calculadora de Billetaje
- ✅ **Denominaciones incluidas:** L.500, L.200, L.100, L.50, L.20, L.10, L.5, L.1
- ✅ **Cálculo en tiempo real:** El total se actualiza automáticamente al ingresar cantidades
- ✅ **Subtotales por denominación:** Muestra el subtotal de cada tipo de billete/moneda
- ✅ **Total general:** Suma automática de todos los billetes contados

### 2. Apertura de Caja
- ✅ Muestra saldo del sistema (de `agencias.saldo_efectivo`)
- ✅ Calculadora de billetaje para conteo físico
- ✅ Comparación automática entre saldo sistema vs físico
- ✅ Alerta amarilla si hay diferencia
- ✅ Campo de observaciones (requerido si hay diferencia)
- ✅ Validación: Solo una caja abierta por día

### 3. Cierre de Caja
- ✅ Muestra información de apertura
- ✅ Muestra saldo actual del sistema
- ✅ Calculadora de billetaje para conteo físico
- ✅ Comparación automática entre saldo sistema vs físico
- ✅ Alerta amarilla si hay diferencia (sobrante o faltante)
- ✅ Campo de observaciones (requerido si hay diferencia)
- ✅ Confirmación antes de cerrar
- ✅ Guarda diferencia de cierre

### 4. Validaciones
- ✅ **Diferencia detectada:** Muestra alerta amarilla con monto exacto
- ✅ **Observaciones requeridas:** Si hay diferencia, debe justificar
- ✅ **Mensajes específicos:**
  - "Existe un sobrante de L. X.XX. Por favor, justifique en observaciones."
  - "Existe un faltante de L. X.XX. Por favor, justifique en observaciones."

---

## 📁 Archivos Creados

### Frontend
- `public/admin/control_caja.php` - Vista principal del módulo
- `public/admin/assets/js/control_caja.js` - Lógica JavaScript completa

### Backend (API)
- `app/api/caja/get_estado.php` - Obtener estado de caja actual
- `app/api/caja/get_saldo_sistema.php` - Obtener saldo del sistema
- `app/api/caja/apertura.php` - Registrar apertura de caja
- `app/api/caja/cierre.php` - Registrar cierre de caja

### Navegación
- Entrada agregada en `sidebar.php` (Control de Caja)

---

## 💾 Base de Datos

### Tabla Utilizada
**`control_caja_diaria`** (ya existente)

**Campos utilizados:**
- `id_control` - ID único del registro
- `id_agencia` - Agencia a la que pertenece
- `id_usuario_apertura` - Usuario que abrió la caja
- `fecha_dia` - Fecha del día
- `hora_apertura` - Timestamp de apertura
- `saldo_apertura_sistema` - Saldo que reporta el sistema
- `saldo_apertura_fisico` - **Saldo contado con calculadora** ✅
- `id_usuario_cierre` - Usuario que cerró la caja
- `hora_cierre` - Timestamp de cierre
- `saldo_cierre_sistema` - Saldo que reporta el sistema al cierre
- `saldo_cierre_fisico` - **Saldo contado con calculadora** ✅
- `diferencia_cierre` - Diferencia entre físico y sistema
- `estado` - 'Abierto' o 'Cerrado'
- `observaciones` - Justificación de diferencias

**Nota:** Solo se guarda el **monto total** en los campos `saldo_apertura_fisico` y `saldo_cierre_fisico`. No se crean tablas adicionales para los billetes.

---

## 🎯 Flujo de Uso

### Apertura de Caja

1. Usuario accede a **Control de Caja**
2. Si no hay caja abierta, ve botón **"Abrir Caja"**
3. Click en "Abrir Caja" abre modal con:
   - Saldo del sistema (automático)
   - Calculadora de billetaje
4. Usuario ingresa cantidades de cada denominación
5. Sistema calcula total en tiempo real
6. Si hay diferencia:
   - Muestra alerta amarilla
   - Requiere observaciones
7. Click en "Abrir Caja" → Guarda registro

### Durante el Día

- Muestra tarjeta verde con estado "Caja Abierta"
- Muestra:
  - Saldo de apertura
  - Saldo actual
  - Movimiento del día
- Botón "Cerrar Caja" disponible

### Cierre de Caja

1. Click en "Cerrar Caja"
2. Modal muestra:
   - Información de apertura
   - Saldo actual del sistema
   - Calculadora de billetaje
3. Usuario cuenta efectivo físico
4. Sistema calcula total en tiempo real
5. Si hay diferencia:
   - Muestra alerta amarilla (sobrante o faltante)
   - Requiere observaciones
6. Confirmación: "¿Cerrar Caja?"
7. Click en "Sí, cerrar" → Guarda cierre

---

## 🧮 Calculadora de Billetaje

### Denominaciones
```
L. 500  [cantidad] → Subtotal
L. 200  [cantidad] → Subtotal
L. 100  [cantidad] → Subtotal
L.  50  [cantidad] → Subtotal
L.  20  [cantidad] → Subtotal
L.  10  [cantidad] → Subtotal
L.   5  [cantidad] → Subtotal
L.   1  [cantidad] → Subtotal
                    ─────────
            Total Contado: L. X,XXX.XX
```

### Ejemplo de Uso
```
L. 500  × 10 = L. 5,000.00
L. 200  ×  5 = L. 1,000.00
L. 100  × 20 = L. 2,000.00
L.  50  × 10 = L.   500.00
L.  20  × 25 = L.   500.00
L.  10  × 30 = L.   300.00
L.   5  × 20 = L.   100.00
L.   1  × 50 = L.    50.00
                    ─────────
Total Contado:      L. 9,450.00
```

---

## ⚠️ Validaciones y Alertas

### Alerta de Diferencia (Amarilla)

**Caso 1: Sobrante**
```
⚠️ Existe un sobrante de L. 150.00. Por favor, justifique en observaciones.
```

**Caso 2: Faltante**
```
⚠️ Existe un faltante de L. 75.50. Por favor, justifique en observaciones.
```

### Validación de Observaciones
- Si diferencia > L. 0.01 → Observaciones **REQUERIDAS**
- Si diferencia = L. 0.00 → Observaciones opcionales

### Otras Validaciones
- ✅ Solo una caja abierta por día por agencia
- ✅ Usuario debe tener agencia asignada
- ✅ Solo usuarios con permisos pueden abrir/cerrar
- ✅ Confirmación antes de cerrar caja

---

## 🔐 Permisos

### Roles Configurados
- ✅ **Administrador:** Todos los permisos de caja
- ✅ **Sup. Regional:** Todos los permisos de caja

### Estructura de Permisos
```json
{
  "caja": {
    "view": true,
    "create": true,
    "edit": true,
    "delete": true
  }
}
```

### Verificación en APIs
- `caja.crear` o `caja.editar` o `caja` → Permite abrir/cerrar

---

## 📊 Ejemplo de Datos Guardados

### Registro de Apertura
```sql
INSERT INTO control_caja_diaria (
    id_agencia,
    id_usuario_apertura,
    fecha_dia,
    saldo_apertura_sistema,
    saldo_apertura_fisico,
    observaciones,
    estado
) VALUES (
    1,                    -- Sede Central
    2,                    -- Usuario lediscuac
    '2026-01-06',         -- Hoy
    10000.00,             -- Saldo sistema
    9950.00,              -- Saldo contado
    'Faltante de L.50 por cambio dado a cliente sin registrar',
    'Abierto'
);
```

### Registro de Cierre
```sql
UPDATE control_caja_diaria SET
    id_usuario_cierre = 2,
    hora_cierre = NOW(),
    saldo_cierre_sistema = 15000.00,
    saldo_cierre_fisico = 15050.00,
    diferencia_cierre = 50.00,
    observaciones = CONCAT(observaciones, '\n--- CIERRE ---\nSobrante de L.50 encontrado en gaveta'),
    estado = 'Cerrado'
WHERE id_control = 1;
```

---

## 🎨 Interfaz de Usuario

### Colores y Estilos
- **Verde:** Caja abierta, apertura exitosa
- **Rojo:** Cierre de caja, botones de cerrar
- **Amarillo:** Alertas de diferencia
- **Azul:** Información del sistema
- **Gris:** Calculadora de billetaje

### Iconos
- 🏦 `fa-cash-register` - Menú principal
- ✅ `fa-check-circle` - Caja abierta
- 🔓 `fa-unlock` - Abrir caja
- 🔒 `fa-lock` - Cerrar caja
- 🧮 `fa-calculator` - Calculadora
- ⚠️ `fa-exclamation-triangle` - Alerta de diferencia

---

## 🧪 Pruebas Recomendadas

### 1. Apertura Normal (Sin Diferencia)
- Saldo sistema: L. 10,000.00
- Conteo físico: L. 10,000.00
- Resultado: ✅ Sin alerta, apertura exitosa

### 2. Apertura con Faltante
- Saldo sistema: L. 10,000.00
- Conteo físico: L. 9,950.00
- Resultado: ⚠️ Alerta amarilla, requiere observaciones

### 3. Cierre con Sobrante
- Saldo sistema: L. 15,000.00
- Conteo físico: L. 15,100.00
- Resultado: ⚠️ Alerta amarilla, requiere observaciones

### 4. Validación de Caja Duplicada
- Intentar abrir caja cuando ya hay una abierta
- Resultado: ❌ Error "Ya existe una caja abierta para hoy"

---

## 📝 Notas Técnicas

### JavaScript
- Usa jQuery para manipulación del DOM
- Cálculos en tiempo real con evento `input`
- Formato de moneda: `toLocaleString('es-HN')`
- SweetAlert2 para confirmaciones

### PHP
- Validación de permisos en cada endpoint
- Uso de prepared statements
- Manejo de errores con try-catch
- Logs de errores con `error_log()`

### SQL
- Índices en `id_agencia` y `fecha_dia`
- Estado ENUM: 'Abierto', 'Cerrado'
- Timestamps automáticos
- Campos DECIMAL(15,2) para montos

---

## ✅ Checklist de Implementación

- [x] Vista principal creada (`control_caja.php`)
- [x] JavaScript con calculadora implementado
- [x] API de estado de caja
- [x] API de saldo del sistema
- [x] API de apertura
- [x] API de cierre
- [x] Entrada en menú sidebar
- [x] Permisos configurados
- [x] Validaciones de diferencia
- [x] Alertas amarillas funcionando
- [x] Observaciones requeridas si hay diferencia
- [x] Cálculo en tiempo real
- [x] Solo guarda monto total (no billetes individuales)

---

## 🎉 Conclusión

El módulo de **Control de Caja** está completamente implementado con todas las características solicitadas:

✅ Calculadora de billetaje (L.500 a L.1)  
✅ Cálculo en tiempo real con JavaScript  
✅ Solo guarda monto total en BD  
✅ Alerta amarilla si hay diferencia  
✅ Validación de observaciones requeridas  

**Estado:** ✅ LISTO PARA PRODUCCIÓN
