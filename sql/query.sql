-- DROP DATABASE bd_capainter;
CREATE DATABASE bd_capaInter; -- cambiar nombre al de la página final
use bd_capaInter;
 
-- drop table usuario;
CREATE TABLE Usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificacion numerica del usuario',
    email VARCHAR(255) UNIQUE COMMENT 'Correo del usuario',
    nombre_usuario VARCHAR(50) UNIQUE COMMENT 'Username del usuario',
    contrasena VARCHAR(255) COMMENT 'Contraseña del usuario',
    tipo ENUM('Cliente','Vendedor', 'Administrador', 'Superadministrador') COMMENT 'Rol del usuario',
    avatar VARCHAR(255) COMMENT 'Imagen del usuario',
    nombre VARCHAR(50) COMMENT 'Nombre del usuario',
    apellido_P VARCHAR(50) COMMENT 'Apellido paterno del usuario',
    apellido_M VARCHAR(50) COMMENT 'Apellido materno del usuario',
    fecha_Nacimiento DATE COMMENT 'Fecha de Nacimiento',
    genero ENUM('Masculino', 'Femenino') COMMENT 'Genero del usuario',
    fecha_Registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro',
    estado ENUM('Activo', 'Inactivo') COMMENT 'Usuario Activo (SI/NO)'
);

-- drop table Categoria;
CREATE TABLE Categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Clave de identificacion de la categoria',
    NombreCategoria VARCHAR(30) COMMENT 'Nombre de la Categoria',
    Descripcion TEXT COMMENT 'Descripcion de la Categoria',
    id_usuario INT COMMENT 'Identificacion del Usuario',
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
);

-- drop table Producto;
CREATE TABLE Producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Clave de identificacion del Producto',
    Nombre VARCHAR(100) COMMENT 'Nombre del producto',
    Descripcion TEXT COMMENT 'Descripcion del producto',
    Precio DECIMAL(10, 2) COMMENT 'Precio del producto',
    Inventario INT COMMENT 'Unidades disponibles',
    Valoracion DECIMAL(2, 1) COMMENT 'Valoracion del Producto',
    id_categoria INT COMMENT 'Clave de identificacion de la categoria',
    id_vendedor INT COMMENT 'Clave de identificacion del Vendedor',
    Estado ENUM('Pendiente', 'Aprobado', 'Rechazado') COMMENT '¿El producto fue aprobado?',
    Tipo ENUM('Cotizar', 'Vender') COMMENT 'Tipo de Producto',
    FechaCreacion TIMESTAMP DEFAULT current_timestamp COMMENT 'Fecha de Creacion del Producto',
    FOREIGN KEY (id_categoria) REFERENCES Categoria(id_categoria),
    FOREIGN KEY (id_vendedor) REFERENCES Usuario(id_usuario)
);

-- drop table ListaUsuario;
CREATE TABLE ListaUsuario (
    id_lista INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Clave de identificacion de la lista',
    id_usuario INT COMMENT 'Identificacion del Usuario',
    id_producto INT COMMENT 'Identificacion del Producto',
    NombreLista VARCHAR(50) COMMENT 'Nombre de la lista',
    Descripcion TEXT COMMENT 'Descripcion de la lista',
    Publica BOOLEAN COMMENT '¿La lista es publica?',
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

-- drop table ProductoEnLista;
CREATE TABLE ProductoEnLista (
    id_producto INT COMMENT 'Clave de identificacion del Producto',
    id_lista INT COMMENT 'Clave de identificacion de la lista agregada',
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto),
    FOREIGN KEY (id_lista) REFERENCES ListaUsuario(id_lista),
    primary key(id_producto,id_lista)
);

-- drop table Comentario;
CREATE TABLE Comentario (
	id_comentario INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Clave de identificación del Comentario',
    id_producto INT COMMENT 'Producto al que va dirigdo el comentario',
    id_autor INT COMMENT 'Quien publicó el comentario',
    Texto VARCHAR(500) COMMENT 'Contenido del comentario',
    FechaHora TIMESTAMP DEFAULT current_timestamp COMMENT 'Fecha de publicación del comenario',
    FOREIGN KEY (id_autor) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
);


-- drop table MultimediaProducto;
CREATE TABLE MultimediaProducto (
    id_multimedia INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Clave de identificacion del Video del producto',
    id_producto INT COMMENT 'Clave de identificacion del Producto',
    URL VARCHAR(255) COMMENT 'URL del archivo multimedia del Producto',
    FOREIGN KEY (Id_producto) REFERENCES Producto(id_producto)
);

-- drop table CarritoCompras;
CREATE TABLE CarritoCompras (
    id_usuario INT COMMENT 'Clave de identificacion del Usuario',
    id_producto INT COMMENT 'Clave de identificacion del Producto',
    Cantidad INT COMMENT 'Cantidad de Productos',
    FechaAgregado TIMESTAMP COMMENT 'Fecha y Hora de Agregado del Producto',
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto),
    primary key(id_usuario,id_producto)
);

-- drop table Venta;
CREATE TABLE Venta (
    id_venta INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Clave de identificación de la Venta',
    id_vendedor INT COMMENT 'Clave de identificación del Vendedor',
    id_cliente INT COMMENT 'Clave de identificación del Comprador',
    id_producto INT COMMENT 'Clave del Producto',
    CantidadVendida INT COMMENT 'Cantidad Vendida',
    PrecioTotal DECIMAL(10, 2) COMMENT 'Precio del producto' COMMENT 'Precio total entre la cantidad del producto',
    FechaHoraVenta TIMESTAMP COMMENT 'Fecha y Hora de la Venta',
    FOREIGN KEY (id_vendedor) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_cliente) REFERENCES Usuario(id_usuario), 
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
);

-- drop table Conversacion;
CREATE TABLE Conversacion (
    id_conversacion INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador unico de la conversacion',
    id_usuario1 INT NOT NULL COMMENT 'Primer usuario de la conversacion',
    id_usuario2 INT NOT NULL COMMENT 'Segundo usuario de la conversacion',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion de la conversacion',
    UNIQUE (id_usuario1, id_usuario2),
    FOREIGN KEY (id_usuario1) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_usuario2) REFERENCES Usuario(id_usuario)
);

-- drop table MensajesChat;
CREATE TABLE MensajeChat (
    id_mensaje INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador unico del mensaje',
    id_conversacion INT COMMENT 'Conversacion a la que pertenece el mensaje',
    id_remitente INT COMMENT 'Usuario que envia el mensaje',
    Mensaje TEXT COMMENT 'Contenido del mensaje',
    FechaHora TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de envio del mensaje',
    FOREIGN KEY (id_conversacion) REFERENCES Conversacion(id_conversacion),
    FOREIGN KEY (id_remitente) REFERENCES Usuario(id_usuario)
);


-- drop table Cotizacion;
CREATE TABLE Cotizacion (
    id_cotizacion INT AUTO_INCREMENT PRIMARY KEY,
    id_comprador INT,
    id_vendedor INT,
    id_producto INT,
    unidades INT,
    Detalles TEXT COMMENT 'Comentarios acerca de la cotización',
    PrecioTotal DECIMAL(10, 2) COMMENT 'Cuota del cotización',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Pendiente', 'Aceptado','Rechazado','Terminado') DEFAULT "Pendiente" COMMENT 'Estado de la cotización',
    FOREIGN KEY (id_comprador) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_vendedor) REFERENCES Usuario(id_usuario),
	FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
);

CREATE TABLE ValoracionesUsuario (
        id_valoracion INT AUTO_INCREMENT PRIMARY KEY,
        id_producto INT,
        id_usuario INT,
        puntuacion INT NOT NULL CHECK (puntuacion >= 1 AND puntuacion <= 5),
        fecha_valoracion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_producto) REFERENCES Producto(id_producto) ON DELETE CASCADE,
        FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario) ON DELETE CASCADE,
        UNIQUE KEY idx_usuario_producto_valoracion (id_usuario, id_producto) -- Para que un usuario valore un producto una sola vez
);

/*
CREATE TABLE IntentosLogin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    intentos INT DEFAULT 1,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (email) REFERENCES Usuario(email)
);
*/
-- Consultas --
select * from Usuario; -- contraseña desencriptada: x8RfLTrm$1
-- Cliente
INSERT INTO Usuario (
    email, nombre_usuario, contrasena, tipo, avatar, nombre, apellido_P, apellido_M, 
    fecha_Nacimiento, genero, estado
) VALUES (
    'cliente@gmail.com', 'MaxWell', '$2y$10$6jVQWbYLUo1L.o6z8ry.puJXbLzwpI36kd2oMAPo8KQ1r2C2IuDT6', 'Cliente',
    Null, 'Maximiliano', 'de Mendieta', 'Cavazos',
    '1995-06-15', 'Masculino', 'Activo'
);

-- Vendedor
INSERT INTO Usuario (
    email, nombre_usuario, contrasena, tipo, avatar, nombre, apellido_P, apellido_M, 
    fecha_Nacimiento, genero, estado
) VALUES (
    'vendedor@gmail.com', 'Veck', '$2y$10$6jVQWbYLUo1L.o6z8ry.puJXbLzwpI36kd2oMAPo8KQ1r2C2IuDT6', 'Vendedor',
    Null, 'Victor Hugo', 'Molina', 'Ruiz',
    '2003-08-01', 'Masculino', 'Activo'
);

-- Administrador
INSERT INTO Usuario (
    email, nombre_usuario, contrasena, tipo, avatar, nombre, apellido_P, apellido_M, 
    fecha_Nacimiento, genero, estado
) VALUES (
    'admin@gmail.com', 'Padroneitor', '$2y$10$6jVQWbYLUo1L.o6z8ry.puJXbLzwpI36kd2oMAPo8KQ1r2C2IuDT6', 'Administrador',
    Null, 'Juan José', 'Rodríguez', 'Padrón',
    '1990-03-22', 'Masculino', 'Activo'
);

-- Superadministrador
INSERT INTO Usuario (
    email, nombre_usuario, contrasena, tipo, avatar, nombre, apellido_P, apellido_M, 
    fecha_Nacimiento, genero, estado
) VALUES (
    'superadmin@gmail.com', 'AdrianAdmin', '$2y$10$6jVQWbYLUo1L.o6z8ry.puJXbLzwpI36kd2oMAPo8KQ1r2C2IuDT6', 'Superadministrador',
    Null, 'Adriana Guadalupe', 'Garza', 'Álvarez',
    '1985-09-10', 'Femenino', 'Activo'
);
-- Chats

INSERT INTO Conversacion (id_usuario1, id_usuario2)
VALUES (1, 2);
-- Mensaje del Cliente (MaxWell)
INSERT INTO MensajeChat (id_conversacion, id_remitente, Mensaje)
VALUES (1, 1, 'Hola, ¿cuál es la cotización?');
-- Mensaje del Vendedor (Veck)
INSERT INTO MensajeChat (id_conversacion, id_remitente, Mensaje)
VALUES (1, 2, '$1500, negociable.');
-- select * from producto



