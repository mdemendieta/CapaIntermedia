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

DELIMITER $$

CREATE PROCEDURE sp_ChatCotizaciones (
    IN idUsuario1 INT,
    IN idUsuario2 INT
)
BEGIN
    DECLARE convId INT;

    -- Obtener id_conversacion existente entre los dos usuarios
    SELECT id_conversacion INTO convId
    FROM conversacion
    WHERE (id_usuario1 = idUsuario1 AND id_usuario2 = idUsuario2)
       OR (id_usuario1 = idUsuario2 AND id_usuario2 = idUsuario1)
    LIMIT 1;

    -- Si no hay conversación, devolver resultado vacío
    IF convId IS NULL THEN
        SELECT NULL AS id, NULL AS id_remitente, NULL AS id_destinatario,
               NULL AS mensaje, NULL AS fecha, NULL AS tipo,
               NULL AS nombre_producto, NULL AS detalles, NULL AS unidades,
               NULL AS precio_total, NULL AS imagen_url, NULL AS estado;
    ELSE
        -- Mensajes normales
        SELECT 
			mc.id_mensaje AS id,
			mc.id_remitente,
			CASE 
				WHEN mc.id_remitente = idUsuario1 THEN idUsuario2
				ELSE idUsuario1
			END AS id_destinatario,
			mc.Mensaje AS mensaje,
			mc.FechaHora AS fecha,
			'mensaje' AS tipo,
			NULL AS nombre_producto,
			NULL AS detalles,
			NULL AS unidades,
			NULL AS precio_total,
			NULL AS imagen_url,
            NULL AS estado
		FROM mensajechat mc
		WHERE mc.id_conversacion = convId

        UNION ALL

        -- Cotizaciones entre estos dos usuarios
        SELECT 
            c.id_cotizacion AS id,
            c.id_vendedor AS id_remitente,
            c.id_comprador AS id_destinatario,
            '' AS mensaje,
            c.fecha_creacion AS fecha,
            'cotizacion' AS tipo,
            p.Nombre AS nombre_producto,
            c.Detalles AS detalles,
            c.unidades,
            c.PrecioTotal AS precio_total,
            mp.URL AS imagen_url,
            c.estado AS estado
        FROM cotizacion c
        JOIN producto p ON p.id_producto = c.id_producto
        LEFT JOIN (
            SELECT id_producto, MIN(URL) AS URL
            FROM multimediaproducto
            GROUP BY id_producto
        ) mp ON mp.id_producto = p.id_producto
        WHERE (c.id_vendedor = idUsuario1 AND c.id_comprador = idUsuario2)
           OR (c.id_vendedor = idUsuario2 AND c.id_comprador = idUsuario1)

        ORDER BY fecha ASC;
    END IF;
END$$

DELIMITER ;

-- CALL sp_ChatCotizaciones ('3','2');

DELIMITER $$

CREATE PROCEDURE sp_ObtenerChats(
    IN p_idUsuarioActual INT
)
BEGIN
    SELECT
        u.id_usuario,
        u.nombre_usuario,
        u.nombre,
        u.apellido_P,
        u.avatar
    FROM
        Usuario u
    JOIN (
        SELECT DISTINCT id_usuario2 AS other_user_id
        FROM Conversacion
        WHERE id_usuario1 = p_idUsuarioActual
        UNION DISTINCT
        SELECT DISTINCT id_usuario1 AS other_user_id
        FROM Conversacion
        WHERE id_usuario2 = p_idUsuarioActual
    ) AS OtrosUsuariosConversacion ON u.id_usuario = OtrosUsuariosConversacion.other_user_id
    WHERE
        u.id_usuario != p_idUsuarioActual; 

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


