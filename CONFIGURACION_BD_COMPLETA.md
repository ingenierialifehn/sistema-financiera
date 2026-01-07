# Resumen de Configuración de Base de Datos
## Módulos: Bóveda y Operaciones

### ✅ Configuración Completada Automáticamente

#### 1. Tabla `agencias`
- ✅ Columna `saldo_efectivo` creada (renombrada desde `saldo_caja`)
- **Tipo:** DECIMAL(15,2) NOT NULL DEFAULT 0.00
- **Propósito:** Almacenar el saldo de efectivo en la bóveda de cada agencia

#### 2. Tabla `ingresos_bancos_agencia`
- ✅ Tabla creada completamente
- **Propósito:** Registrar movimientos de fondos desde bancos hacia bóvedas de agencias
- **Campos principales:**
  - `banco_id`: ID del banco origen
  - `agencia_id`: ID de la agencia destino
  - `monto`: Monto transferido
  - `saldo_anterior_banco` y `saldo_nuevo_banco`: Saldos del banco
  - `saldo_anterior_agencia` y `saldo_nuevo_agencia`: Saldos de la agencia
  - `realizado_por`: Usuario que realizó la operación
  - `fecha_hora`: Timestamp de la operación

#### 3. Tabla `prestamos`
- ✅ Columna `estado` modificada para incluir 'aprobado'
- ✅ Columna `id_agencia` agregada
- **Valores de estado:** 'pendiente', 'aprobado', 'activo', 'completado', 'cancelado', 'en_mora'
- **Foreign Key:** `id_agencia` → `agencias(id_agencia)` ON DELETE SET NULL

#### 4. Tabla `clientes`
- ✅ Columna `id_agencia` agregada
- **Foreign Key:** `id_agencia` → `agencias(id_agencia)` ON DELETE SET NULL

---

### 📋 Tareas Manuales Pendientes

#### 1. Asignar Agencias a Clientes Existentes
Los clientes existentes tienen `id_agencia = NULL`. Debes asignarles una agencia manualmente:

```sql
-- Ejemplo: Asignar todos los clientes a la Sede Central (id_agencia = 1)
UPDATE clientes 
SET id_agencia = 1 
WHERE id_agencia IS NULL;

-- O asignar por cobrador (si los cobradores tienen agencia asignada)
UPDATE clientes c
INNER JOIN colaboradores col ON c.cobrador_id = col.id_colaborador
SET c.id_agencia = col.id_agencia
WHERE c.id_agencia IS NULL AND col.id_agencia IS NOT NULL;
```

#### 2. Asignar Agencias a Préstamos Existentes
Los préstamos existentes tienen `id_agencia = NULL`. Debes asignarles una agencia:

```sql
-- Opción 1: Asignar basado en la agencia del cliente
UPDATE prestamos p
INNER JOIN clientes c ON p.cliente_id = c.id
SET p.id_agencia = c.id_agencia
WHERE p.id_agencia IS NULL AND c.id_agencia IS NOT NULL;

-- Opción 2: Asignar todos a Sede Central
UPDATE prestamos 
SET id_agencia = 1 
WHERE id_agencia IS NULL;
```

#### 3. Configurar Permisos en el Sistema

##### Para el módulo "Bóveda de Agencia":
1. Ve a **Roles y Permisos** en el panel de administración
2. Edita el rol que necesita acceso (ej: "Oficial de Operaciones", "Cajero")
3. En la matriz de permisos, marca las casillas para **"Bóveda de Agencia"**:
   - ✅ **Ver**: Para visualizar el módulo
   - ✅ **Crear**: Para registrar ingresos de efectivo
   - ✅ **Editar**: Para registrar ingresos (alternativa a Crear)

##### Para el módulo "Operaciones":
1. Ve a **Roles y Permisos**
2. Edita el rol correspondiente
3. Marca las casillas para **"Operaciones"**:
   - ✅ **Ver**: Para visualizar el dashboard
   - ✅ **Crear**: Para jalar fondos desde banco
   - ✅ **Editar**: Para preparar entregas

#### 4. Verificar Usuarios con Agencia Asignada
Asegúrate de que los usuarios que usarán estos módulos tengan una agencia asignada en su perfil de colaborador:

```sql
-- Ver usuarios sin agencia asignada
SELECT u.id_usuario, u.nombre_usuario, c.nombre_completo, c.id_agencia
FROM usuarios u
LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
WHERE c.id_agencia IS NULL;

-- Asignar agencia a un colaborador específico
UPDATE colaboradores 
SET id_agencia = 1  -- ID de la agencia
WHERE id_colaborador = X;  -- ID del colaborador
```

#### 5. Crear Cuentas Bancarias (si no existen)
Para que el módulo de bóveda funcione, necesitas tener cuentas bancarias registradas:

```sql
-- Verificar si hay bancos
SELECT * FROM bancos;

-- Si no hay, insertar un banco de ejemplo
INSERT INTO bancos (nombre_banco, numero_cuenta, tipo_cuenta, saldo_actual, estado)
VALUES ('Banco Atlántida', '1234567890', 'Ahorro', 100000.00, 'activo');
```

---

### 🔍 Verificación Final

Ejecuta estos comandos para verificar que todo está configurado:

```sql
-- 1. Verificar estructura de agencias
DESCRIBE agencias;

-- 2. Verificar que saldo_efectivo existe
SELECT id_agencia, nombre_agencia, saldo_efectivo FROM agencias;

-- 3. Verificar estructura de prestamos
DESCRIBE prestamos;

-- 4. Verificar estructura de clientes
DESCRIBE clientes;

-- 5. Verificar tabla ingresos_bancos_agencia
DESCRIBE ingresos_bancos_agencia;

-- 6. Contar clientes sin agencia
SELECT COUNT(*) as clientes_sin_agencia FROM clientes WHERE id_agencia IS NULL;

-- 7. Contar préstamos sin agencia
SELECT COUNT(*) as prestamos_sin_agencia FROM prestamos WHERE id_agencia IS NULL;

-- 8. Verificar bancos disponibles
SELECT COUNT(*) as total_bancos FROM bancos WHERE estado = 'activo';
```

---

### 📝 Notas Importantes

1. **Transacciones Atómicas**: Los módulos de bóveda y operaciones usan transacciones SQL para garantizar la integridad de los datos.

2. **Seguridad**: Solo los usuarios con permisos específicos y agencia asignada pueden acceder a estos módulos.

3. **Filtrado por Agencia**: Todos los datos mostrados están filtrados automáticamente por la agencia del usuario autenticado.

4. **Saldo Inicial**: El saldo de efectivo de todas las agencias inicia en 0.00. Usa el módulo de bóveda para transferir fondos desde las cuentas bancarias.

5. **Estados de Préstamos**: 
   - `pendiente`: Solicitud inicial
   - `aprobado`: Aprobado pero no desembolsado (aparece en "Próximos Desembolsos")
   - `activo`: Desembolsado y en cobro
   - `completado`: Totalmente pagado
   - `cancelado`: Cancelado
   - `en_mora`: Con pagos atrasados

---

### 🚀 Próximos Pasos

1. ✅ Ejecutar los scripts SQL de asignación de agencias (sección "Tareas Manuales Pendientes")
2. ✅ Configurar permisos en el panel de administración
3. ✅ Verificar que los usuarios tengan agencias asignadas
4. ✅ Crear al menos una cuenta bancaria si no existe
5. ✅ Probar el módulo de bóveda registrando un ingreso de prueba
6. ✅ Probar el módulo de operaciones jalando fondos desde banco

---

**Fecha de configuración:** 2026-01-06
**Módulos configurados:** Bóveda de Agencia, Operaciones de Agencia
