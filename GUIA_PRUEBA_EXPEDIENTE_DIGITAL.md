# GUÍA RÁPIDA DE PRUEBA - Expediente Digital de Clientes

## Cómo Probar el Sistema

### 1. Acceder al Módulo de Clientes

1. Abre tu navegador
2. Ve a: `http://localhost/sistema-financiera/public/admin/clientes.php`
3. Inicia sesión con tu usuario administrador

---

### 2. Crear un Nuevo Cliente

#### Paso 1: Abrir el Formulario
- Haz clic en el botón **"Nuevo Cliente"** (azul, esquina superior derecha)
- Se abrirá un modal con 3 pestañas

#### Paso 2: Pestaña "Datos Personales"
Completa los siguientes campos:
- ✅ **Nombre Completo**: Ej: "Juan Carlos Pérez García"
- ✅ **Tipo de Documento**: DNI (por defecto)
- ✅ **Número de Documento**: Ej: "0801199012345"
- ✅ **Fecha de Nacimiento**: Selecciona una fecha
- ✅ **Género**: Selecciona "Masculino" o "Femenino"
- ✅ **Teléfono**: Ej: "98765432"
- ✅ **Email**: Ej: "juan.perez@ejemplo.com"
- ✅ **Ocupación**: Ej: "Comerciante"

#### Paso 3: Pestaña "Ubicación"
Haz clic en la pestaña **"Ubicación"** y completa:
- ✅ **Dirección Completa**: Ej: "Col. Kennedy, Bloque A, Casa #15"
- ✅ **Departamento**: Ej: "Francisco Morazán"
- ✅ **Municipio**: Ej: "Tegucigalpa"
- ✅ **Barrio/Colonia**: Ej: "Col. Kennedy"
- ✅ **Punto de Referencia**: Ej: "Frente al parque central, al lado de la farmacia"
- ✅ **Tipo de Vivienda**: Selecciona una opción (Propia, Alquilada, Familiar, Pagándola)
- ✅ **Coordenadas GPS**: Haz clic en **"Obtener Ubicación"**
  - El navegador pedirá permiso para acceder a tu ubicación
  - Haz clic en "Permitir"
  - Las coordenadas se llenarán automáticamente

#### Paso 4: Pestaña "Documentación"
Haz clic en la pestaña **"Documentación"** y sube las fotos:

##### Opción A: Drag & Drop (Arrastrar y Soltar)
1. Arrastra cada imagen desde tu explorador de archivos
2. Suéltala en el área correspondiente
3. Verás una vista previa de la imagen

##### Opción B: Click para Seleccionar
1. Haz clic en el área de carga
2. Selecciona la imagen desde el explorador de archivos
3. Verás una vista previa de la imagen

**Fotos Requeridas:**
- ✅ **DNI - Frontal**: Foto del frente del DNI
- ✅ **DNI - Reverso**: Foto del reverso del DNI
- ✅ **Foto de Perfil**: Foto del rostro del cliente
- ✅ **Foto de Casa**: Foto de la fachada de la casa
- ✅ **Recibo de Servicio**: Foto de un recibo de luz/agua/teléfono

**Nota:** Cada foto debe ser:
- Formato: JPG o PNG
- Tamaño máximo: 5MB

#### Paso 5: Guardar
- Haz clic en el botón **"Guardar Cliente"** (azul, abajo a la derecha)
- Espera la confirmación
- Se mostrará un mensaje de éxito con dos opciones:
  - **"Ver Ficha"**: Para ver el expediente completo
  - **"Cerrar"**: Para volver a la lista

---

### 3. Verificar que se Guardó Correctamente

#### Opción 1: En la Lista de Clientes
- El nuevo cliente aparecerá en la tabla
- Verás su foto de perfil (si la subiste)
- Verás su código de cliente generado automáticamente

#### Opción 2: En la Base de Datos
Ejecuta esta consulta SQL:
```sql
SELECT 
    codigo_cliente,
    nombre_completo,
    tipo_vivienda,
    gps_coordenadas,
    foto_dni_frontal,
    foto_dni_posterior,
    foto_perfil,
    foto_fachada_casa,
    foto_recibo_servicio
FROM clientes
ORDER BY id DESC
LIMIT 1;
```

#### Opción 3: En el Servidor
Verifica que las fotos se guardaron en:
```
c:\xampp\htdocs\sistema-financiera\uploads\documentos\
```

Deberías ver 5 archivos con nombres como:
```
0801199012345_Juan_Carlos_Perez_Garcia_foto_dni_frontal_1704657890.jpg
0801199012345_Juan_Carlos_Perez_Garcia_foto_dni_posterior_1704657891.jpg
0801199012345_Juan_Carlos_Perez_Garcia_foto_perfil_1704657892.jpg
0801199012345_Juan_Carlos_Perez_Garcia_foto_fachada_casa_1704657893.jpg
0801199012345_Juan_Carlos_Perez_Garcia_foto_recibo_servicio_1704657894.jpg
```

---

### 4. Editar un Cliente Existente

1. En la lista de clientes, haz clic en el ícono de **editar** (lápiz azul)
2. El modal se abrirá con todos los datos cargados
3. Modifica los campos que desees
4. Haz clic en **"Guardar Cliente"**
5. Los cambios se guardarán

---

### 5. Probar la Funcionalidad GPS

#### Requisitos:
- Navegador moderno (Chrome, Firefox, Edge, Safari)
- Permisos de ubicación habilitados
- **Importante**: En producción necesitas HTTPS

#### Pasos:
1. Abre el formulario de nuevo cliente
2. Ve a la pestaña "Ubicación"
3. Haz clic en **"Obtener Ubicación"**
4. El navegador mostrará un mensaje: "Este sitio quiere conocer tu ubicación"
5. Haz clic en **"Permitir"**
6. El botón cambiará a "Obteniendo..." con un spinner
7. Después de 1-3 segundos, las coordenadas aparecerán
8. Verás un mensaje: "✓ Ubicación capturada correctamente"
9. Las coordenadas se mostrarán en formato: "14.123456, -87.654321"

#### Si no funciona:
- Verifica que diste permisos de ubicación
- Intenta en otro navegador
- Verifica que tu dispositivo tenga GPS/ubicación activada

---

### 6. Probar Drag & Drop de Imágenes

#### Pasos:
1. Abre el formulario de nuevo cliente
2. Ve a la pestaña "Documentación"
3. Abre tu explorador de archivos
4. Selecciona una imagen (JPG o PNG)
5. Arrástrala sobre el área que dice "Arrastra la imagen aquí..."
6. El área se resaltará en azul cuando estés sobre ella
7. Suelta la imagen
8. Verás una vista previa de la imagen
9. Aparecerá un botón ❌ en la esquina para remover la imagen si quieres cambiarla

#### Validaciones que se Ejecutan:
- ✅ Solo acepta JPG y PNG
- ✅ Máximo 5MB por imagen
- ✅ Muestra error si el archivo no cumple

---

### 7. Casos de Prueba Recomendados

#### Test 1: Cliente Completo
- ✅ Completa TODOS los campos
- ✅ Sube las 5 fotos
- ✅ Captura GPS
- ✅ Guarda
- **Resultado Esperado**: Cliente guardado exitosamente con expediente completo

#### Test 2: Cliente Mínimo
- ✅ Solo campos obligatorios: Nombre, DNI, Teléfono
- ✅ Sin fotos
- ✅ Sin GPS
- **Resultado Esperado**: Cliente guardado exitosamente (campos opcionales en NULL)

#### Test 3: DNI Duplicado
- ✅ Intenta crear un cliente con un DNI que ya existe
- **Resultado Esperado**: Error "Este DNI ya está registrado"

#### Test 4: Archivo Muy Grande
- ✅ Intenta subir una imagen de más de 5MB
- **Resultado Esperado**: Error "La imagen no debe superar 5MB"

#### Test 5: Archivo Inválido
- ✅ Intenta subir un PDF o documento Word
- **Resultado Esperado**: Error "Solo se permiten imágenes"

#### Test 6: GPS Sin Permisos
- ✅ Deniega los permisos de ubicación
- **Resultado Esperado**: Error "Permiso denegado. Permite el acceso a tu ubicación."

---

### 8. Verificación Final

Ejecuta este script SQL para ver estadísticas:
```sql
SELECT 
    COUNT(*) AS total_clientes,
    SUM(CASE WHEN gps_coordenadas IS NOT NULL THEN 1 ELSE 0 END) AS con_gps,
    SUM(CASE WHEN foto_dni_frontal IS NOT NULL THEN 1 ELSE 0 END) AS con_dni_frontal,
    SUM(CASE WHEN foto_perfil IS NOT NULL THEN 1 ELSE 0 END) AS con_foto_perfil,
    SUM(CASE WHEN tipo_vivienda IS NOT NULL THEN 1 ELSE 0 END) AS con_tipo_vivienda
FROM clientes;
```

---

### 9. Troubleshooting (Solución de Problemas)

#### Problema: No se suben las fotos
**Solución:**
1. Verifica que la carpeta existe: `c:\xampp\htdocs\sistema-financiera\uploads\documentos\`
2. Verifica permisos de escritura
3. Verifica `upload_max_filesize` en `php.ini`

#### Problema: GPS no funciona
**Solución:**
1. Verifica permisos del navegador
2. Intenta en Chrome/Firefox
3. Verifica que tu dispositivo tenga ubicación activada

#### Problema: Error al guardar
**Solución:**
1. Abre la consola del navegador (F12)
2. Ve a la pestaña "Console"
3. Busca errores en rojo
4. Verifica la pestaña "Network" para ver la respuesta del servidor

#### Problema: Campos no se muestran
**Solución:**
1. Limpia caché del navegador (Ctrl + Shift + Delete)
2. Recarga la página (Ctrl + F5)
3. Verifica que el script SQL se ejecutó correctamente

---

### 10. Checklist de Funcionalidades

Marca cada funcionalidad después de probarla:

**Base de Datos:**
- [ ] Campos de ubicación (departamento, municipio, barrio, punto_referencia)
- [ ] Campo tipo_vivienda con opciones correctas
- [ ] Campo gps_coordenadas
- [ ] Campo genero
- [ ] 5 campos de fotos

**Interfaz:**
- [ ] Formulario muestra todos los campos nuevos
- [ ] Pestañas funcionan correctamente
- [ ] Botón "Obtener Ubicación" visible
- [ ] Áreas de drag & drop para las 5 fotos

**Funcionalidad:**
- [ ] GPS captura coordenadas
- [ ] Drag & drop funciona
- [ ] Click para seleccionar funciona
- [ ] Vista previa de imágenes
- [ ] Validación de tipo de archivo
- [ ] Validación de tamaño
- [ ] Guardar cliente completo
- [ ] Editar cliente existente

**Archivos:**
- [ ] Fotos se guardan en /uploads/documentos/
- [ ] Nombres de archivo correctos
- [ ] Fotos se pueden ver en el servidor

---

## ¡Listo!

Si todas las funcionalidades funcionan correctamente, el módulo de Expediente Digital está completamente operativo.

**Próximo paso recomendado:** Crear la página de "Ficha del Cliente" para visualizar toda la información de forma organizada.
