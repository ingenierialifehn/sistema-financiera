-- Crear la tabla maestra de agencias
CREATE TABLE IF NOT EXISTS sistema_financiera.agencias (
    id_agencia INT AUTO_INCREMENT PRIMARY KEY,
    nombre_agencia VARCHAR(100) NOT NULL UNIQUE,
    direccion VARCHAR(255),
    ciudad VARCHAR(100),
    telefono_agencia VARCHAR(20),
    estado ENUM('Activa', 'Inactiva') DEFAULT 'Activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar dato inicial para evitar inconsistencias
INSERT IGNORE INTO sistema_financiera.agencias (id_agencia, nombre_agencia, direccion, estado) 
VALUES (1, 'Sede Central', 'Dirección General', 'Activa');

-- Establecer la relación formal con la tabla colaboradores
ALTER TABLE sistema_financiera.colaboradores
ADD CONSTRAINT fk_colaborador_agencia FOREIGN KEY (id_agencia) 
REFERENCES sistema_financiera.agencias(id_agencia) ON DELETE RESTRICT;
