<?php
if(!isset($_SESSION['email'])) {
    session_start(); // Asegúrate de tener la sesión iniciada
}
include 'conexion.php'; // Tu conexión a la base de datos

$idUsuarioActual = $_SESSION['email'];

// Buscamos usuarios activos distintos al que está logueado
$db = new Database();
$conexion = $db->getConexion();
$stmt = $conexion->prepare("SELECT id_usuario, email, nombre_usuario, nombre, apellido_P, avatar 
                        FROM Usuario 
                        WHERE estado = 'Activo' AND email != ?");
$stmt->bind_param("s", $idUsuarioActual);
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
