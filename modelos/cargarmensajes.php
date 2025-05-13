<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    echo json_encode(['error' => 'No hay conversación existente']);
    exit;
}

// Ahora cargar los mensajes de esa conversación
$stmt = $conexion->prepare("
    SELECT id_remitente, Mensaje, FechaHora
    FROM MensajeChat
    WHERE id_conversacion = ?
    ORDER BY FechaHora ASC
");
$stmt->bind_param("i", $idConversacion);
$stmt->execute();
$result = $stmt->get_result();

$mensajes = [];

while ($row = $result->fetch_assoc()) {
    $mensajes[] = [
        'sender' => $row['id_remitente'] == $currentUserId ? 'yo' : 'otro',
        'texto' => $row['Mensaje'],
        'fecha' => $row['FechaHora']
    ];
}

echo json_encode($mensajes);
?>
