<?php
session_start();
require_once '../modelos/conexion.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Acción no permitida o datos incorrectos.'];

if (!isset($_SESSION['id_usuario'])) {
    $response['message'] = 'Debes iniciar sesión para realizar esta acción.';
    echo json_encode($response);
    exit;
}

$visitor_id = (int)$_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_lista']) && isset($_POST['id_producto'])) {
        $id_lista = (int)$_POST['id_lista'];
        $id_producto = (int)$_POST['id_producto'];

        $db = new Database();
        $conexion = $db->getConexion();

        // Verificar que el usuario logueado es el propietario de la lista
        $stmtCheckOwner = $conexion->prepare("SELECT id_usuario FROM ListaUsuario WHERE id_lista = ?");
        if ($stmtCheckOwner) {
            $stmtCheckOwner->bind_param("i", $id_lista);
            $stmtCheckOwner->execute();
            $resultOwner = $stmtCheckOwner->get_result();

            if ($ownerRow = $resultOwner->fetch_assoc()) {
                if ((int)$ownerRow['id_usuario'] === $visitor_id) {
                    // Propietario confirmado, proceder a eliminar
                    $stmtDelete = $conexion->prepare("DELETE FROM ProductoEnLista WHERE id_lista = ? AND id_producto = ?");
                    if ($stmtDelete) {
                        $stmtDelete->bind_param("ii", $id_lista, $id_producto);
                        if ($stmtDelete->execute()) {
                            if ($stmtDelete->affected_rows > 0) {
                                $response['success'] = true;
                                $response['message'] = 'Producto eliminado de la lista correctamente.';
                            } else {
                                $response['message'] = 'El producto no se encontró en esta lista o ya fue eliminado.';
                                // Considerar success=true si el estado final es el deseado (producto no en lista)
                                // $response['success'] = true; 
                            }
                        } else {
                            $response['message'] = 'Error al ejecutar la eliminación: ' . $stmtDelete->error;
                        }
                        $stmtDelete->close();
                    } else {
                         $response['message'] = 'Error al preparar la consulta de eliminación: ' . $conexion->error;
                    }
                } else {
                    $response['message'] = 'No tienes permiso para modificar esta lista.';
                }
            } else {
                $response['message'] = 'La lista especificada no existe.';
            }
            $stmtCheckOwner->close();
        } else {
            $response['message'] = 'Error al preparar la verificación del propietario: ' . $conexion->error;
        }
        $conexion->close();
    } else {
        $response['message'] = 'Faltan parámetros necesarios (id_lista o id_producto).';
    }
}

echo json_encode($response);
?>