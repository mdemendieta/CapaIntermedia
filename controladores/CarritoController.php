<?php
// Archivo: CapaIntermedia/controladores/carrito_controller.php
session_start();
header('Content-Type: application/json');

// Ajusta las rutas según tu estructura de carpetas
require_once '../modelos/conexion.php'; 
require_once '../modelos/CarritoModel.php';

$response = ['success' => false, 'message' => 'Acción no válida o datos incompletos.', 'in_cart' => false];

if (!isset($_SESSION['id_usuario'])) {
    $response['message'] = 'Usuario no autenticado. Por favor, inicia sesión.';
    // $response['redirect_login'] = true; // Opcional, para el frontend
    echo json_encode($response);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// La conexión a la BD se crea dentro del modelo ahora.
$carritoModel = new CarritoModel(); // CarritoModel ahora maneja su propia conexión

$action = $_POST['action'] ?? $_GET['action'] ?? null; // Permitir action por GET para 'check_status'

$id_producto = null;
if (isset($_POST['id_producto'])) {
    $id_producto = filter_var($_POST['id_producto'], FILTER_VALIDATE_INT);
} elseif (isset($_GET['id_producto'])) { // Para 'check_status'
    $id_producto = filter_var($_GET['id_producto'], FILTER_VALIDATE_INT);
}

switch ($action) {
    case 'check_status':
        if ($id_producto) {
            $enCarrito = $carritoModel->productoEnCarrito($id_usuario, $id_producto);
            $response = ['success' => true, 'in_cart' => $enCarrito];
        } else {
            $response['message'] = 'ID de producto no proporcionado para la verificación.';
        }
        break;

    case 'add_to_cart':
        if ($id_producto) {
            // La cantidad siempre será 1 desde product.php para esta lógica de "añadir/quitar"
            $cantidad = 1; 
            $result = $carritoModel->agregarProducto($id_usuario, $id_producto, $cantidad);
            $response = $result; // $result ya tiene 'success' y 'message'
            // Confirmar el estado actual para el botón
            if ($response['success']) { // Solo si la operación de BD fue exitosa
                 $response['in_cart'] = $carritoModel->productoEnCarrito($id_usuario, $id_producto);
            }
        } else {
            $response['message'] = 'ID de producto no proporcionado para agregar.';
        }
        break;

    case 'remove_from_cart':
        if ($id_producto) {
            $result = $carritoModel->eliminarProducto($id_usuario, $id_producto);
            $response = $result;
            // Confirmar el estado actual para el botón
             if ($response['success']) { // Solo si la operación de BD fue exitosa
                $response['in_cart'] = $carritoModel->productoEnCarrito($id_usuario, $id_producto); // Debería ser false
            }
        } else {
            $response['message'] = 'ID de producto no proporcionado para eliminar.';
        }
        break;
    
    default:
        $response['message'] = 'Acción desconocida: ' . htmlspecialchars($action);
        break;
}

echo json_encode($response);
?>