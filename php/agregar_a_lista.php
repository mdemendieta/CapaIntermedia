<?php
session_start();
include('conexion.php'); // Conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_producto = $_POST['id_producto'];
    $id_lista = $_POST['id_lista'];

    // Consulta para insertar el producto en la lista
    $query = "INSERT INTO productoenlista (id_lista, id_producto) VALUES ($id_lista, $id_producto)";
    if (mysqli_query($conexion, $query)) {
        echo "Producto añadido exitosamente a la lista.";
    } else {
        echo "Error al añadir el producto: " . mysqli_error($conexion);
    }
}
?>
