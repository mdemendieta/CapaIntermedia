<?php
session_start();
require_once '../modelos/conexion.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Acción no permitida o datos incorrectos.'];

// Verificar rol de Administrador y que sea una solicitud POST
if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Administrador' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_producto']) && isset($_POST['nuevo_estado'])) {
        $id_producto = (int)$_POST['id_producto'];
        $nuevo_estado = $_POST['nuevo_estado'];

        // Validar que el nuevo estado sea uno de los permitidos
        if ($nuevo_estado === 'Aprobado' || $nuevo_estado === 'Rechazado') {
            $db = new Database();
            $conexion = $db->getConexion();

            $stmt = $conexion->prepare("UPDATE Producto SET Estado = ? WHERE id_producto = ? AND Estado = 'Pendiente'");
            if ($stmt) {
                $stmt->bind_param("si", $nuevo_estado, $id_producto);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $response['success'] = true;
                        $response['message'] = 'Estado del producto actualizado a ' . strtolower($nuevo_estado) . '.';
                    } else {
                        $response['message'] = 'No se pudo actualizar el estado. El producto podría no estar pendiente o no existir.';
                    }
                } else {
                    $response['message'] = 'Error al ejecutar la actualización: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $response['message'] = 'Error al preparar la consulta: ' . $conexion->error;
            }
            $conexion->close();
        } else {
            $response['message'] = 'Estado no válido proporcionado.';
        }
    } else {
        $response['message'] = 'Faltan parámetros necesarios (id_producto o nuevo_estado).';
    }
} else {
    if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'Administrador') {
        $response['message'] = 'No tienes permiso para realizar esta acción.';
    }
}

echo json_encode($response);
?>