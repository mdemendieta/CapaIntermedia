<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Asegúrate de tener la sesión iniciada
}

require_once 'conexion.php'; // Tu conexión a la base de datos

// Asegúrate de que el usuario esté logueado y tengamos su ID
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'Usuario no autenticado', 'contacts' => []]);
    exit;
}
$idUsuarioActual = (int)$_SESSION['id_usuario'];

$db = new Database();
$conexion = $db->getConexion();

$contacts = [];

/*
Consulta para obtener los IDs de los otros usuarios en las conversaciones del usuario actual.
Luego, se obtienen los detalles de esos usuarios.
*/

// Llamar al Stored Procedure
$stmt = $conexion->prepare("CALL sp_ObtenerChats(?)");

if ($stmt) {
    $stmt->bind_param("i", $idUsuarioActual);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Determinar la ruta del avatar
        $avatar_filename = (isset($row['avatar']) && !empty($row['avatar'])) ? $row['avatar'] : 'perfilvacio.jpg';
        $avatar_path = '../recursos/usuarios/' . $avatar_filename; // Ajusta si 'perfilvacio.jpg' está en otra ruta

        // Si el avatar es el por defecto y no existe en la carpeta usuarios, ajústamos para que apunte directamente a recursos
        if ($avatar_filename === 'perfilvacio.jpg' && !file_exists($_SERVER['DOCUMENT_ROOT'] . '/recursos/usuarios/' . $avatar_filename) ) {
 
             $project_root_relative_path = '/CapaIntermedia/'; 
             if (file_exists($_SERVER['DOCUMENT_ROOT'] . $project_root_relative_path . 'recursos/' . $avatar_filename)){
                $avatar_path = '../recursos/' . $avatar_filename;
             } else {
                // Fallback si incluso el perfilvacio.jpg general no se encuentra
                $avatar_path = '../recursos/perfilvacio.jpg'; // Ruta por defecto
             }
        }


        $contacts[] = [
            'id' => $row['id_usuario'],
            'username' => htmlspecialchars($row['nombre_usuario']),
            'name' => htmlspecialchars($row['nombre'] . " " . $row['apellido_P']),
            'img' => htmlspecialchars($avatar_path)
        ];
    }
    $stmt->close();
} else {
    // Error en la preparación de la consulta
    echo json_encode(['error' => 'Error al preparar la consulta: ' . $conexion->error, 'contacts' => []]);
    $conexion->close();
    exit;
}

$conexion->close();
echo json_encode($contacts); // Devuelve siempre un array, vacío si no hay contactos
?>