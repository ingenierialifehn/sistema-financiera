# Solución: No Jala Efectivo de Cuentas de Banco

## ✅ Problema Identificado

El problema es que los **permisos no están cargados en la sesión actual**. Aunque actualicé los permisos en la base de datos, necesitas cerrar sesión y volver a iniciar sesión para que se carguen.

## 🔧 Solución Paso a Paso

### Paso 1: Cerrar Sesión y Volver a Iniciar Sesión

1. **Cierra sesión** en el sistema
2. **Vuelve a iniciar sesión** con tu usuario
3. Esto cargará los nuevos permisos en la sesión

### Paso 2: Verificar que Tienes Agencia Asignada

Ejecuta este comando SQL para verificar:

```sql
USE sistema_financiera;

-- Ver tu usuario y agencia asignada
SELECT 
    u.id_usuario,
    u.username,
    c.nombre_completo,
    c.id_agencia,
    a.nombre_agencia
FROM usuarios u
LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
LEFT JOIN agencias a ON c.id_agencia = a.id_agencia
WHERE u.username = 'TU_USUARIO';  -- Reemplaza con tu nombre de usuario
```

Si `id_agencia` es NULL, asígnala con:

```sql
-- Asignar agencia al colaborador
UPDATE colaboradores 
SET id_agencia = 1  -- 1 = Sede Central
WHERE id_colaborador = (
    SELECT id_colaborador 
    FROM usuarios 
    WHERE username = 'TU_USUARIO'
);
```

### Paso 3: Verificar Permisos Actualizados

Los siguientes roles ya tienen permisos configurados:
- ✅ **Administrador**: Todos los permisos de bóveda y operaciones
- ✅ **Sup. Regional**: Todos los permisos de bóveda y operaciones

Si usas otro rol, actualízalo con:

```sql
-- Ejemplo: Agregar permisos a un rol específico
UPDATE roles 
SET permisos = JSON_SET(
    permisos, 
    '$.boveda', JSON_OBJECT('view', true, 'create', true, 'edit', true),
    '$.operaciones', JSON_OBJECT('view', true, 'create', true, 'edit', true)
)
WHERE nombre_rol = 'NOMBRE_DEL_ROL';
```

### Paso 4: Probar el Módulo

1. Ve a **Operaciones** o **Bóveda de Agencia**
2. Click en **"Jalar Fondos desde Banco"** o **"Registrar Ingreso de Efectivo"**
3. Selecciona un banco
4. Ingresa el monto
5. Click en **"Jalar Fondos"** o **"Registrar Ingreso"**

## 🐛 Si Aún No Funciona

### Verificar en la Consola del Navegador

1. Abre las **Herramientas de Desarrollador** (F12)
2. Ve a la pestaña **"Console"**
3. Intenta registrar un ingreso
4. Busca errores en rojo

### Verificar Respuesta de la API

1. En las Herramientas de Desarrollador, ve a **"Network"** (Red)
2. Intenta registrar un ingreso
3. Busca la petición a `registrar_ingreso.php`
4. Click en ella y ve a la pestaña **"Response"**
5. Verás el mensaje de error exacto

### Errores Comunes y Soluciones

| Error | Causa | Solución |
|-------|-------|----------|
| "No tiene permisos..." | Permisos no actualizados en sesión | Cerrar sesión y volver a iniciar |
| "Usuario no tiene agencia asignada" | Colaborador sin agencia | Ejecutar UPDATE en Paso 2 |
| "Banco no encontrado" | ID de banco incorrecto | Verificar que el banco existe |
| "Saldo insuficiente" | Banco sin fondos | Actualizar saldo del banco |
| "Agencia no encontrada" | Agencia no existe | Verificar que la agencia existe |

## 📊 Verificación de Datos

### Ver Bancos Disponibles

```sql
SELECT id, nombre_banco, numero_cuenta, saldo_actual, estado 
FROM bancos 
WHERE estado = 'activo';
```

### Ver Agencias

```sql
SELECT id_agencia, nombre_agencia, saldo_efectivo, estado 
FROM agencias 
WHERE estado = 'Activa';
```

### Actualizar Saldo de un Banco (si está en 0)

```sql
UPDATE bancos 
SET saldo_actual = 100000.00 
WHERE id = 1;  -- Reemplaza con el ID del banco
```

## 🧪 Prueba Manual con SQL

Si quieres probar la transacción manualmente:

```sql
USE sistema_financiera;

START TRANSACTION;

-- 1. Restar del banco
UPDATE bancos 
SET saldo_actual = saldo_actual - 5000.00 
WHERE id = 1;

-- 2. Sumar a la agencia
UPDATE agencias 
SET saldo_efectivo = saldo_efectivo + 5000.00 
WHERE id_agencia = 1;

-- 3. Registrar el movimiento
INSERT INTO ingresos_bancos_agencia (
    banco_id, 
    agencia_id, 
    monto, 
    referencia,
    saldo_anterior_banco,
    saldo_nuevo_banco,
    saldo_anterior_agencia,
    saldo_nuevo_agencia,
    realizado_por,
    observaciones
) VALUES (
    1,                          -- banco_id
    1,                          -- agencia_id
    5000.00,                    -- monto
    'Prueba manual',            -- referencia
    (SELECT saldo_actual + 5000.00 FROM bancos WHERE id = 1),  -- saldo_anterior_banco
    (SELECT saldo_actual FROM bancos WHERE id = 1),            -- saldo_nuevo_banco
    (SELECT saldo_efectivo - 5000.00 FROM agencias WHERE id_agencia = 1),  -- saldo_anterior_agencia
    (SELECT saldo_efectivo FROM agencias WHERE id_agencia = 1),            -- saldo_nuevo_agencia
    1,                          -- realizado_por (ID del usuario)
    'Prueba manual desde SQL'   -- observaciones
);

-- Si todo está bien, confirmar:
COMMIT;

-- Si algo salió mal, revertir:
-- ROLLBACK;
```

## 📝 Resumen de Cambios Realizados

### ✅ Base de Datos
- Columna `saldo_efectivo` en `agencias` ✓
- Tabla `ingresos_bancos_agencia` ✓
- Columnas `id_agencia` en `prestamos` y `clientes` ✓
- Estado 'aprobado' en `prestamos` ✓

### ✅ Permisos
- Rol **Administrador**: boveda y operaciones con todos los permisos ✓
- Rol **Sup. Regional**: boveda y operaciones con todos los permisos ✓

### ⚠️ Pendiente
- **Cerrar sesión y volver a iniciar** para cargar nuevos permisos
- **Verificar que tu usuario tiene agencia asignada**

## 🎯 Siguiente Paso Inmediato

**1. CIERRA SESIÓN**
**2. VUELVE A INICIAR SESIÓN**
**3. INTENTA JALAR FONDOS NUEVAMENTE**

Si después de esto sigue sin funcionar, revisa la consola del navegador (F12) y comparte el mensaje de error exacto.
