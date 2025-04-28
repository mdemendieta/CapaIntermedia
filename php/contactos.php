<?php
session_start();
include('conexion.php'); // Tu conexión a la base de datos

$idUsuarioActual = $_SESSION['id_usuario'];

// Buscamos usuarios activos distintos al que está logueado
$stmt = $conexion->prepare("SELECT id_usuario, nombre_usuario, nombre, apellido_P, avatar 
                        FROM Usuario 
                        WHERE estado = 'Activo' AND id_usuario != ?");
$stmt->bind_param("i", $idUsuarioActual);
$stmt->execute();
$result = $stmt->get_result();

$contacts = [];

while ($row = $result->fetch_assoc()) {
    $contacts[] = [
        'id' => $row['id_usuario'],
        'name' => $row['nombre'] . " " . $row['apellido_P'],
        'img' => '../recursos/' . ('perfilvacio.jpg') // Ajusta ruta
    ];
}

echo json_encode($contacts);
?>
