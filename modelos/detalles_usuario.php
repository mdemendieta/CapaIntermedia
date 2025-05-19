<?php
header('Content-Type: application/json');
require_once 'conexion.php'; // Tu script de conexión a la BD

$response = ['success' => false, 'error' => 'ID de usuario no proporcionado o inválido.'];

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $userId = (int)$_GET['id'];

    $db = new Database();
    $conexion = $db->getConexion();

    // Ajusta los campos según tu tabla Usuario
    $stmt = $conexion->prepare("SELECT id_usuario, nombre_usuario, nombre, apellido_P, avatar FROM Usuario WHERE id_usuario = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Construir la ruta del avatar
            $avatarPath = '../recursos/perfilvacio.jpg'; // Avatar por defecto
            if (!empty($user['avatar'])) {
                // Asumiendo que los avatares están en /recursos/usuarios/
                // Necesitas ajustar la ruta base si es diferente en tu servidor al construir la URL relativa al frontend
                $potentialPath = $_SERVER['DOCUMENT_ROOT'] . '/CapaIntermedia/recursos/usuarios/' . $user['avatar']; // Ajusta '/CapaIntermedia/' si tu estructura es diferente
                if (file_exists($potentialPath)) {
                    $avatarPath = '../recursos/usuarios/' . htmlspecialchars($user['avatar']);
                }
            }
            
            $response = [
                'success' => true,
                'contact' => [
                    'id' => $user['id_usuario'],
                    'username' => htmlspecialchars($user['nombre_usuario']),
                    'name' => htmlspecialchars($user['nombre'] . ' ' . $user['apellido_P']),
                    'img' => $avatarPath
                ]
            ];
        } else {
            $response['error'] = 'Usuario no encontrado o inactivo.';
        }
        $stmt->close();
    } else {
        $response['error'] = 'Error al preparar la consulta: ' . $conexion->error;
    }
    $conexion->close();
}

echo json_encode($response);
?>