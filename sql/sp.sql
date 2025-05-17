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

DELIMITER $$

CREATE PROCEDURE sp_IniciarSesion(
IN p_usuario VARCHAR(255)
)
BEGIN
    SELECT 
        id_usuario,
        nombre,
        apellido_P,
        apellido_M,
        nombre_usuario,
        email,
        contrasena, 
        tipo,
        avatar,
        genero,
        fecha_Nacimiento
    FROM Usuario
    WHERE nombre_usuario = p_usuario OR email = p_usuario
    LIMIT 1;
END$$

DELIMITER ;

-- Vista Producto (con una 1 sóla imagen para Previews)--
CREATE VIEW VistaDetalleProducto AS
SELECT 
    p.id_producto,
    p.Nombre,
    p.Descripcion,
    p.Precio,
    p.Inventario,
    p.Estado,
    p.Tipo,
    p.id_vendedor,
    p.id_categoria,
    (SELECT URL 
     FROM MultimediaProducto m 
     WHERE m.id_producto = p.id_producto 
     ORDER BY m.id_multimedia ASC 
     LIMIT 1) AS imagen_principal
FROM Producto p;


-- Vista Productos para Cotizar --
CREATE VIEW VistaProductoCotizacion AS
SELECT 
    p.id_producto,
    p.Nombre,
    p.Descripcion,
    p.Precio,
    p.Inventario,
    p.Estado,
    p.Tipo,
    p.id_vendedor,
    (SELECT URL FROM MultimediaProducto m 
     WHERE m.id_producto = p.id_producto 
     ORDER BY m.id_multimedia ASC LIMIT 1) AS imagen_principal
FROM Producto p
WHERE p.Estado = 'Aprobado' AND p.Tipo = 'Cotizar';
