-- Insertar tasas de interés sugeridas por modalidad
INSERT INTO configuraciones (clave, valor, tipo, descripcion) VALUES
('tasa_diario', '2.5', 'decimal', 'Tasa de interés sugerida para pagos diarios (Lunes a Viernes)'),
('tasa_semanal', '5.0', 'decimal', 'Tasa de interés sugerida para pagos semanales (4 cuotas al mes)'),
('tasa_catorcenal', '8.0', 'decimal', 'Tasa de interés sugerida para pagos catorcenales (2 cuotas al mes)'),
('tasa_mensual', '15.0', 'decimal', 'Tasa de interés sugerida para pagos mensuales'),
('mes_laboral_dias', '20', 'numero', 'Número de días laborales en un mes (Lunes a Viernes)')
ON DUPLICATE KEY UPDATE 
valor = VALUES(valor), 
descripcion = VALUES(descripcion),
updated_at = CURRENT_TIMESTAMP;
