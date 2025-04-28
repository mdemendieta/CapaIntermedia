USE bd_capaInter;

-- STORED PROCEDURES --
DELIMITER $$
-- Registro de Usuario
CREATE PROCEDURE sp_RegistrarUsuario (
    IN p_nombre VARCHAR(50),
    IN p_apellido_P VARCHAR(50),
    IN p_apellido_M VARCHAR(50),
    IN p_nombre_usuario VARCHAR(50),
    IN p_email VARCHAR(255),
    IN p_contrasena VARCHAR(255),
    IN p_genero ENUM('Masculino', 'Femenino'),
    IN p_fecha_Nacimiento DATE,
    IN p_tipo ENUM('Cliente','Vendedor', 'Administrador', 'Superadministrador')
)
BEGIN
    INSERT INTO Usuario (
        nombre, apellido_P, apellido_M, nombre_usuario, email, contrasena, genero, fecha_Nacimiento, tipo, estado
    ) VALUES (
        p_nombre, p_apellido_P, p_apellido_M, p_nombre_usuario, p_email, p_contrasena, p_genero, p_fecha_Nacimiento, p_tipo, 'Activo'
    );
END $$
DELIMITER ;

DELIMITER //
-- Validar que no se repitan Usuarios
CREATE PROCEDURE sp_ValidarUsuarioCorreo(
    IN p_nombre_usuario VARCHAR(50),
    IN p_email VARCHAR(255)
)
BEGIN
    SELECT id_usuario 
    FROM Usuario
    WHERE nombre_usuario = p_nombre_usuario OR email = p_email;
END //
DELIMITER ;
/*
DELIMITER //
CREATE PROCEDURE sp_IniciarSesion(
    IN p_usuario VARCHAR(255)
)
BEGIN
    DECLARE v_id_usuario INT;
    DECLARE v_contrasena_bd VARCHAR(255);
    DECLARE v_intentos INT;

    -- Buscamos el usuario
    SELECT id_usuario, contrasena, intentos_fallidos
    INTO v_id_usuario, v_contrasena_bd, v_intentos
    FROM Usuario
    WHERE email = p_usuario OR nombre_usuario = p_usuario
    LIMIT 1;

    -- Si no existe el usuario, lanzamos error
    IF v_id_usuario IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no encontrado';
    END IF;

    -- Devolvemos los datos que necesitamos (contraseña hash + intentos fallidos)
    SELECT v_id_usuario AS id_usuario, v_contrasena_bd AS contrasena, v_intentos AS intentos_fallidos;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE sp_ManejarIntentoFallido(IN p_usuario VARCHAR(255))
BEGIN
    DECLARE v_intentos INT DEFAULT 0;

    -- Buscar si ya existe registro
    SELECT intentos INTO v_intentos FROM IntentosLogin WHERE usuario = p_usuario LIMIT 1;

    IF v_intentos IS NULL THEN
        -- Primer intento fallido
        INSERT INTO IntentosLogin (usuario, intentos) VALUES (p_usuario, 1);
    ELSE
        -- Aumentar el contador
        UPDATE IntentosLogin SET intentos = v_intentos + 1 WHERE usuario = p_usuario;

        -- Si llega a 3, suspender al usuario
        IF v_intentos + 1 >= 3 THEN
            UPDATE Usuario SET estado = 'Suspendido' WHERE email = p_usuario OR nombre_usuario = p_usuario;
            DELETE FROM IntentosLogin WHERE usuario = p_usuario;
        END IF;
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE sp_LimpiarIntentos(IN p_usuario VARCHAR(255))
BEGIN
    DELETE FROM IntentosLogin WHERE usuario = p_usuario;
END //
DELIMITER ;
/*
-- drop procedure sp_IniciarSesion

