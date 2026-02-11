# 📋 GUÍA DE VARIABLES - MÓDULO DE DOCUMENTACIÓN

## Ejemplo de Uso Completo

**Texto que quieres escribir:**
> "El Deudor se compromete a devolver el importe total del crédito en un plazo de 3 meses, mediante 12 cuotas Semanales de L. 1,300.00."

**Cómo escribirlo con variables:**
> "El Deudor se compromete a devolver el importe total del crédito en un plazo de {{plazo_letras}}, mediante {{total_cuotas}} cuotas {{frecuencia_minuscula}} de {{valor_cuota}}."

---

## 📄 NÚMERO DE CONTRATO

| Variable | Ejemplo de Salida |
|----------|------------------|
| `{{numero_contrato}}` | 000013 |
| `{{numero_prestamo}}` | 13 |
| `{{id_prestamo}}` | 13 |

**Nota:** `{{numero_contrato}}` formatea el ID del préstamo con ceros a la izquierda (6 dígitos) para uso en documentos oficiales.

---

## 👤 CLIENTE

| Variable | Ejemplo de Salida |
|----------|------------------|
| `{{nombre_cliente}}` | JUAN PÉREZ LÓPEZ |
| `{{dni_cliente}}` | 0801-1990-12345 |
| `{{direccion_cliente}}` | Col. Kennedy, Bloque A, Casa 15 |
| `{{telefono_cliente}}` | 9876-5432 |

---

## 💰 MONTO DEL PRÉSTAMO

| Variable | Ejemplo de Salida |
|----------|------------------|
| `{{monto_prestamo}}` | 5,000.00 |
| `{{monto_letras}}` | CINCO MIL LEMPIRAS CON 00/100 CENTAVOS |

---

## 📅 PLAZO

| Variable | Ejemplo de Salida |
|----------|------------------|
| `{{plazo}}` | 3 |
| `{{plazo_meses}}` | 3 meses |
| `{{plazo_letras}}` | tres meses |

---

## 🔢 CUOTAS (Total de Pagos)

| Variable | Ejemplo de Salida |
|----------|------------------|
| `{{total_cuotas}}` | 12 |
| `{{total_cuotas_texto}}` | 12 cuotas |
| `{{total_cuotas_letras}}` | doce cuotas |

**Nota:** El sistema calcula automáticamente:
- Diario: plazo × 30
- Semanal: plazo × 4
- Quincenal: plazo × 2
- Mensual: plazo × 1

---

## ⏱️ MODALIDAD/FRECUENCIA

| Variable | Ejemplo de Salida |
|----------|------------------|
| `{{modalidad}}` | Semanal |
| `{{frecuencia}}` | Semanal |
| `{{frecuencia_minuscula}}` | semanal |

---

## 💵 VALOR DE CUOTA

| Variable | Ejemplo de Salida |
|----------|------------------|
| `{{cuota}}` | 1,300.00 |
| `{{valor_cuota}}` | L. 1,300.00 |
| `{{cuota_letras}}` | MIL TRESCIENTOS LEMPIRAS CON 00/100 CENTAVOS |

---

## 📊 TASA DE INTERÉS

| Variable | Ejemplo de Salida |
|----------|------------------|
| `{{tasa_interes}}` | 3.5 |
| `{{tasa_interes_porcentaje}}` | 3.5% |

---

## 📆 FECHAS

| Variable | Ejemplo de Salida |
|----------|------------------|
| `{{fecha_desembolso}}` | 10/02/2026 |
| **`{{fecha_primera_cuota}}`** | **15/02/2026** ⭐ |
| `{{dia_primera_cuota}}` | 15 |
| `{{mes_primera_cuota}}` | Febrero |
| `{{anio_primera_cuota}}` | 2026 |
| `{{fecha_actual}}` | 10/02/2026 |
| `{{dia_actual}}` | 10 |
| `{{dia_actual_letras}}` | diez |
| `{{mes_actual}}` | Febrero |
| `{{anio_actual}}` | 2026 |

**⭐ NUEVA:** La variable `{{fecha_primera_cuota}}` obtiene automáticamente la fecha de vencimiento de la primera cuota del préstamo desde la tabla de cuotas generadas.

---

## 🏢 AGENCIA

| Variable | Ejemplo de Salida |
|----------|------------------|
| `{{nombre_agencia}}` | Agencia Central |
| `{{ciudad_agencia}}` | Tegucigalpa |

---

## 📝 EJEMPLOS DE FRASES COMPLETAS

### Ejemplo 1: Compromiso de Pago
**Plantilla:**
```
El Deudor se compromete a devolver el importe total del crédito de {{monto_letras}} 
en un plazo de {{plazo_letras}}, mediante {{total_cuotas_texto}} {{frecuencia_minuscula}} 
de {{valor_cuota}} cada una.
```

**Resultado:**
```
El Deudor se compromete a devolver el importe total del crédito de CINCO MIL LEMPIRAS CON 00/100 CENTAVOS 
en un plazo de tres meses, mediante 12 cuotas semanal 
de L. 1,300.00 cada una.
```

### Ejemplo 2: Encabezado de Contrato
**Plantilla:**
```
En la ciudad de {{ciudad_agencia}}, a los {{dia_actual_letras}} días del mes de {{mes_actual}} 
del año {{anio_actual}}, comparecen {{nombre_cliente}}, con DNI {{dni_cliente}}.
```

**Resultado:**
```
En la ciudad de Tegucigalpa, a los diez días del mes de Febrero 
del año 2026, comparecen JUAN PÉREZ LÓPEZ, con DNI 0801-1990-12345.
```

### Ejemplo 3: Condiciones de Interés
**Plantilla:**
```
El préstamo por la cantidad de L. {{monto_prestamo}} devengará un interés del {{tasa_interes_porcentaje}} 
sobre saldos, pagadero en {{total_cuotas}} cuotas {{frecuencia_minuscula}}.
```

**Resultado:**
```
El préstamo por la cantidad de L. 5,000.00 devengará un interés del 3.5% 
sobre saldos, pagadero en 12 cuotas semanal.
```

---

## ✅ CONSEJOS

1. **Usa variables descriptivas**: Prefiere `{{plazo_letras}}` sobre `{{plazo}}` para textos legales
2. **Combina variables**: Puedes usar múltiples variables en una misma frase
3. **Revisa el formato**: Algunas variables ya incluyen símbolos (L., %)
4. **Prueba antes de imprimir**: Usa la función de vista previa

---

**Última actualización:** 10/02/2026
