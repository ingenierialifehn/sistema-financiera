CREATE TABLE IF NOT EXISTS sistema_financiera.colaboradores (
    -- Identificadores y Seguridad
    id_colaborador INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(20) NOT NULL UNIQUE, -- Clave para evitar duplicados en planilla
    
    -- Datos Personales y de Identidad
    nombre_completo VARCHAR(150) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    genero ENUM('Masculino', 'Femenino', 'Otro') NOT NULL,
    telefono VARCHAR(20),
    direccion_residencia TEXT,
    
    -- Datos Laborales y Contables
    puesto_cargo VARCHAR(100) NOT NULL, -- Ej: Asesor, Supervisor, Guardia, Limpieza
    id_agencia INT NOT NULL, -- Relación con la sede a la que pertenece
    fecha_ingreso DATE NOT NULL, -- Base para cálculo de antigüedad y vacaciones
    sueldo_base DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    
    -- Datos de Pago (Bancarios)
    numero_cuenta_bancaria VARCHAR(50),
    banco_receptor VARCHAR(100),
    tipo_cuenta ENUM('Ahorro', 'Cheques', 'Nomina', 'Otro') DEFAULT 'Ahorro',
    
    -- Seguridad Social y Legal
    numero_seguro_social VARCHAR(50), -- IHSS o equivalente
    rtn_personal VARCHAR(50), -- Registro tributario personal
    
    -- Control de Estado (Soft Delete)
    estado_laboral ENUM('Activo', 'Vacaciones', 'Incapacitado', 'Suspendido', 'Despido', 'Renuncia') DEFAULT 'Activo',
    
    -- Auditoría de Sistema
    creado_por INT, -- ID del usuario de gerencia que lo registró
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para optimización de reportes
    INDEX (dni),
    INDEX (estado_laboral),
    INDEX (id_agencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
