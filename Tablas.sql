CREATE DATABASE IF NOT EXISTS HospitalTalca;
USE HospitalTalca;

-- 1. Tablas (Estructura)
CREATE TABLE Pacientes (
    id_paciente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    rut VARCHAR(12) NOT NULL UNIQUE, 
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Biopsias (
    id_biopsia INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    organo VARCHAR(100) NOT NULL,
    fecha_ingreso DATE NOT NULL,
    fecha_expiracion DATE,
    observaciones TEXT,
    CONSTRAINT fk_paciente
    FOREIGN KEY (id_paciente) REFERENCES Pacientes(id_paciente)
    ON DELETE CASCADE
);

-- 2. Trigger (Automatización de Fechas)
DELIMITER //
CREATE TRIGGER calcular_expiracion_biopsia
BEFORE INSERT ON Biopsias
FOR EACH ROW
BEGIN
    SET NEW.fecha_expiracion = DATE_ADD(NEW.fecha_ingreso, INTERVAL 30 DAY);
END;
//
DELIMITER ;

-- 3. Generador de Datos Masivos (1500 Registros con doble apellido)
DELIMITER //
CREATE PROCEDURE GenerarDatosMasivos()
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE rand_nombre VARCHAR(100);
    DECLARE ape1 VARCHAR(50);
    DECLARE ape2 VARCHAR(50);
    DECLARE rand_apellido_completo VARCHAR(100);
    DECLARE rand_rut VARCHAR(12);
    DECLARE rand_organo VARCHAR(100);
    DECLARE rand_fecha DATE;
    DECLARE last_id INT;

    -- Bucle para crear 1500 registros
    WHILE i < 1500 DO
        -- Seleccionar nombre aleatorio (Lista ampliada a 50 nombres comunes)
        SET rand_nombre = ELT(FLOOR(1 + (RAND() * 50)), 
            'Juan', 'María', 'Pedro', 'Ana', 'Luis', 'Carmen', 'José', 'Francisca', 
            'Diego', 'Camila', 'Jorge', 'Valentina', 'Carlos', 'Daniela', 'Manuel',
            'Sofía', 'Andrés', 'Javiera', 'Miguel', 'Carolina', 'David', 'Paula', 
            'Roberto', 'Isidora', 'Fernando', 'Antonia', 'Felipe', 'Gabriela', 'Ricardo', 
            'Constanza', 'Pablo', 'Catalina', 'Francisco', 'Fernanda', 'Gabriel', 'Natalia', 
            'Tomás', 'Victoria', 'Alejandro', 'Beatriz', 'Héctor', 'Teresa', 'Sergio', 
            'Patricia', 'Eduardo', 'Monserrat', 'Matías', 'Estefanía', 'Nicolás', 'Alejandra');

        -- Seleccionar PRIMER apellido (Lista de 35 apellidos comunes)
        SET ape1 = ELT(FLOOR(1 + (RAND() * 35)), 
            'González', 'Muñoz', 'Rojas', 'Díaz', 'Pérez', 'Soto', 'Contreras', 'Silva', 
            'Martínez', 'Sepúlveda', 'Morales', 'Rodríguez', 'López', 'Fuentes', 'Hernández', 
            'Torres', 'Araya', 'Flores', 'Espinoza', 'Valenzuela', 'Castillo', 'Tapia', 
            'Reyes', 'Gutiérrez', 'Castro', 'Pizarro', 'Álvarez', 'Vásquez', 'Sánchez', 
            'Fernández', 'Ramírez', 'Carrasco', 'Gómez', 'Cortés', 'Herrera');

        -- Seleccionar SEGUNDO apellido de la misma lista
        SET ape2 = ELT(FLOOR(1 + (RAND() * 35)), 
            'González', 'Muñoz', 'Rojas', 'Díaz', 'Pérez', 'Soto', 'Contreras', 'Silva', 
            'Martínez', 'Sepúlveda', 'Morales', 'Rodríguez', 'López', 'Fuentes', 'Hernández', 
            'Torres', 'Araya', 'Flores', 'Espinoza', 'Valenzuela', 'Castillo', 'Tapia', 
            'Reyes', 'Gutiérrez', 'Castro', 'Pizarro', 'Álvarez', 'Vásquez', 'Sánchez', 
            'Fernández', 'Ramírez', 'Carrasco', 'Gómez', 'Cortés', 'Herrera');

        -- Concatenar ambos apellidos
        SET rand_apellido_completo = CONCAT(ape1, ' ', ape2);

        -- Generar RUT aleatorio
        SET rand_rut = CONCAT(FLOOR(5000000 + (RAND() * 20000000)), '-', FLOOR(0 + (RAND() * 9)));
        
        -- Insertar paciente
        INSERT IGNORE INTO Pacientes (nombre, apellido, rut) 
        VALUES (rand_nombre, rand_apellido_completo, rand_rut);
        
        -- Insertar biopsia si el paciente se creó correctamente
        IF ROW_COUNT() > 0 THEN
            SET last_id = LAST_INSERT_ID();
            SET rand_organo = ELT(FLOOR(1 + (RAND() * 8)), 'Hígado', 'Riñón', 'Estómago', 'Piel', 'Pulmón', 'Colon', 'Próstata', 'Tiroides');
            SET rand_fecha = DATE_ADD('2023-01-01', INTERVAL FLOOR(RAND() * 365) DAY);
            
            INSERT INTO Biopsias (id_paciente, organo, fecha_ingreso, observaciones) 
            VALUES (last_id, rand_organo, rand_fecha, 'Biopsia generada automáticamente por sistema');
            
            SET i = i + 1;
        END IF;
    END WHILE;
END //
DELIMITER ;

-- 4. Ejecutar la generación
CALL GenerarDatosMasivos();

-- 5. Verificar resultados
SELECT COUNT(*) as Total_Pacientes FROM Pacientes;
SELECT * FROM Pacientes LIMIT 10;