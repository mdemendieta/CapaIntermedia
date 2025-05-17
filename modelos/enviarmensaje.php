<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json'); // Muy importante
include 'conexion.php';

// Leer el cuerpo JSON
$data = json_decode(file_get_contents("php://input"), true);

// Validar que lleguen los datos necesarios
if (!$data || !isset($data['contactId']) || !isset($data['mensaje'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$idRemitente = $_SESSION['id_usuario'] ?? null;
$idDestinatario = intval($data['contactId']);
$mensaje = $data['mensaje'];

if (!$idRemitente) {
    echo json_encode(['success' => false, 'error' => 'Usuario no logueado']);
    exit;
}

// Primero, ver si ya existe la conversación
$db = new Database();
$conexion = $db->getConexion();
$stmt = $conexion->prepare("SELECT id_conversacion FROM Conversacion 
                        WHERE (id_usuario1 = ? AND id_usuario2 = ?) 
                        OR (id_usuario1 = ? AND id_usuario2 = ?)");
$stmt->bind_param("iiii", $idRemitente, $idDestinatario, $idDestinatario, $idRemitente);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $idConversacion = $row['id_conversacion'];
} else {
    // Si no existe, la creamos
    $stmt = $conexion->prepare("INSERT INTO Conversacion (id_usuario1, id_usuario2) VALUES (?, ?)");
    $stmt->bind_param("ii", $idRemitente, $idDestinatario);
    $stmt->execute();
    $idConversacion = $stmt->insert_id;
}

// Insertamos el mensaje
$stmt = $conexion->prepare("INSERT INTO MensajeChat (id_conversacion, id_remitente, Mensaje) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $idConversacion, $idRemitente, $mensaje);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al enviar mensaje']);
}
?>

