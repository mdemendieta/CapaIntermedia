<?php
// Archivo: PWCI/controladores/CarritoController.php
session_start();
header('Content-Type: application/json');

require_once '../modelos/conexion.php';
require_once '../modelos/CarritoModel.php';

$response = ['success' => false, 'message' => 'Acción no válida o datos incompletos.', 'in_cart' => false];

if (!isset($_SESSION['id_usuario'])) {
    $response['message'] = 'Usuario no autenticado. Por favor, inicia sesión.';
    echo json_encode($response);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

$db = new Database(); // Crear instancia de Database
$conexion = $db->getConexion(); // Obtener el objeto de conexión mysqli
$carritoModel = new CarritoModel($conexion); // Pasar la conexión al modelo

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$id_producto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT) ?? filter_input(INPUT_GET, 'id_producto', FILTER_VALIDATE_INT);

switch ($action) {
    case 'check_status':
        // ... (código existente) ...
        break;

    case 'add_to_cart':
        // ... (código existente) ...
        // Este se usa desde product.php
        break;

    case 'remove_from_cart':
        if ($id_producto) {
            $result = $carritoModel->eliminarProducto($id_usuario, $id_producto);
            $response = $result;
            if ($response['success']) {
                $response['in_cart'] = false; // Después de eliminar, ya no está en el carrito
            }
        } else {
            $response['message'] = 'ID de producto no proporcionado para eliminar.';
        }
        break;

    case 'update_quantity_in_cart': // Nueva acción para carrito.php
        $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);
        if ($id_producto && $cantidad !== null) {
            if ($cantidad < 1) { // Si la cantidad es 0 o menos, eliminarlo.
                 $result = $carritoModel->eliminarProducto($id_usuario, $id_producto);
                 $response = $result;
                 if ($response['success']) $response['in_cart'] = false;
            } else {
                $result = $carritoModel->actualizarCantidadProducto($id_usuario, $id_producto, $cantidad);
                $response = $result;
                if ($response['success']) $response['in_cart'] = true; // Asumimos que sigue en el carrito
            }
        } else {
            $response['message'] = 'Datos incompletos para actualizar cantidad (producto o cantidad).';
        }
        break;
    
    default:
        $response['message'] = 'Acción desconocida: ' . htmlspecialchars((string)$action);
        break;
}

$conexion->close(); // Cerrar la conexión
echo json_encode($response);
?>