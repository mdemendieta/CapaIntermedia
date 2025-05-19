<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
header('Content-Type: application/json');
include 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['contactId'])) {
    echo json_encode(['error' => 'Falta contactId']);
    exit;
}

$contactId = intval($data['contactId']);
$currentUserId = $_SESSION['id_usuario'] ?? null;

if (!$currentUserId) {
    echo json_encode(['error' => 'Usuario no logueado']);
    exit;
}

// Primero, buscar la conversacion entre los dos usuarios
$db = new Database();
$conexion = $db->getConexion();
$stmt = $conexion->prepare("
    SELECT id_conversacion FROM Conversacion
    WHERE (id_usuario1 = ? AND id_usuario2 = ?)
       OR (id_usuario1 = ? AND id_usuario2 = ?)
");
$stmt->bind_param("iiii", $currentUserId, $contactId, $contactId, $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $idConversacion = $row['id_conversacion'];
} else {
    echo json_encode(['error' => 'Aún no hay mensajes']);
    exit;
}

// Ahora cargar los mensajes y cotizaciones de esa conversación
$stmt = $conexion->prepare("CALL sp_ChatCotizaciones(?, ?)");
$stmt->bind_param("ii", $currentUserId, $contactId);
$stmt->execute();
$resultado = $stmt->get_result();

$mensajes = [];
while ($row = $resultado->fetch_assoc()) {
    $mensajes[] = [
        'sender' => $row['id_remitente'] == $currentUserId ? 'yo' : 'otro',
        'texto' => $row['mensaje'],
        'fecha' => $row['fecha'],
        'tipo' => $row['tipo'],
        'id' => $row['id'],
        'nombre' => $row['nombre_producto'] ?? null,
        'detalles' => $row['detalles'] ?? null,
        'unidades' => $row['unidades'] ?? null,
        'imagen' => $row['imagen_url'] ?? null,
        'precio' => $row['precio_total'] ?? null,
        'estado' => $row['estado'] ?? null
    ];
}

echo json_encode($mensajes);

?>