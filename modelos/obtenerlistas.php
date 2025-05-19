<?php
session_start();
header('Content-Type: application/json');
require_once 'conexion.php'; // Asegúrate que la ruta sea correcta

$response = [
    'success' => false,
    'listas' => [],
    'message' => '',
    'is_logged_in' => false
];

if (isset($_SESSION['id_usuario'])) {
    $response['is_logged_in'] = true; // Actualizar si está logueado
    $id_usuario = $_SESSION['id_usuario'];
    $db = new Database();
    $conexion = $db->getConexion();
    $listas = [];

    $query = "SELECT id_lista, NombreLista FROM ListaUsuario WHERE id_usuario = ?";
    $stmt = $conexion->prepare($query);

    if ($stmt) {
        $stmt->bind_param("i", $id_usuario);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $listas[] = [
                    'id_lista' => $row['id_lista'],
                    'nombre_lista' => htmlspecialchars($row['NombreLista'])
                ];
            }
            $response['success'] = true;
            $response['listas'] = $listas;
            if (empty($listas)) {
                $response['message'] = 'No tienes listas creadas. Puedes crear una desde tu perfil.';
            } else {
                $response['message'] = 'Listas cargadas.';
            }
        } else {
            // Mantener is_logged_in = true, pero message indica error de DB
            $response['message'] = 'Error al obtener listas: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la consulta de listas: ' . $conexion->error;
    }
    $conexion->close();
}
// Si no entró al if de sesión, el mensaje por defecto de "Usuario no autenticado" y is_logged_in: false se envían.

echo json_encode($response);
?>