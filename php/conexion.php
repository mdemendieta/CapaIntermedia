<?php
// Parámetros de conexión
$host = 'localhost'; // El host de la base de datos (puede ser localhost si está en el mismo servidor)
$usuario = 'root';   // Nombre de usuario
$contraseña = ''; // Contraseña
$base_de_datos = 'bd_capaInter'; // Nombre de la base de datos
$puerto = '33065'; // Puerto de conexión (opcional, por defecto es 3306 para MySQL)

// Crear la conexión
$conn = new mysqli($host, $usuario, $contraseña, $base_de_datos, $puerto);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// IMPORTANTE CERRAR CONEXIÓN AL FINAL DE CADA CONSULTA A LA BASE DE DATOS
// $conn->close();
?>