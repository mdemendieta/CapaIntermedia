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

-- drop table Ventas;
CREATE TABLE Venta (
    id_venta INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Clave de identificación de la Venta',
    id_vendedor INT COMMENT 'Clave de identificación del Vendedor',
    id_cliente INT COMMENT 'Clave de identificación del Comprador',
    id_producto INT COMMENT 'Clave del Producto',
    CantidadVendida INT COMMENT 'Cantidad Vendida',
    PrecioTotal INT COMMENT 'Precio total entre la cantidad del producto',
    FechaHoraVenta TIMESTAMP COMMENT 'Fecha y Hora de la Venta',
    FOREIGN KEY (id_vendedor) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_cliente) REFERENCES Usuario(id_usuario), 
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
);

-- drop table MensajesChat;
CREATE TABLE MensajesChat (
    id_chat INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Clave de identificacion del Mensaje Vendedor y/o comprador',
    id_remitente INT COMMENT 'Clave del remitente',
    id_destinatario INT COMMENT 'Clave del destinatario',
    Mensaje TEXT COMMENT 'Cuerpo del Mensaje',
    FechaHora TIMESTAMP COMMENT 'Fecha y Hora del Mensaje',
    FOREIGN KEY (id_remitente) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_destinatario) REFERENCES Usuario(id_usuario)
);

-- drop table Cotizacion;
CREATE TABLE Cotizacion (
    id_cotizacion INT AUTO_INCREMENT PRIMARY KEY,
    id_comprador INT,
    id_vendedor INT,
    id_producto INT,
    unidades INT,
    cuota INT,
    estado ENUM('Pendiente', 'Aceptado','Rechazado','Terminado') DEFAULT "Pendiente" COMMENT 'Estado de la cotización',
    FOREIGN KEY (id_comprador) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_vendedor) REFERENCES Usuario(id_usuario),
	FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
);