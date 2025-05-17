<?php
require_once '../modelos/conexion.php';
require_once '../modelos/ProductoModel.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Este archivo se mantiene como POST
    session_start();
    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(['success' => false, 'mensaje' => 'Sesión expirada.']);
        exit;
    }

    $db = new Database();
    $conexion = $db->getConexion();
    $productoModel = new ProductoModel($conexion);

    $id_vendedor = $_SESSION['id_usuario'];
    $id_comprador = $_POST['id_comprador']; // <-- este debe venir oculto en el formulario
    $id_producto = $_POST['product-name'];
    $descripcion = $_POST['product-description'];
    $unidades = $_POST['product-quantity'];
    $precio_total = $_POST['product-price'];

    // Validar que el producto pertenece al vendedor
    $producto = $productoModel->obtenerProductoID($id_producto);
    if (!$producto || $producto['id_vendedor'] != $id_vendedor) {
        echo json_encode(['success' => false, 'mensaje' => 'Producto inválido.']);
        exit;
    }

    // Insertar cotización
    $stmt = $conexion->prepare("INSERT INTO Cotizacion (id_comprador, id_vendedor, id_producto, unidades, Detalles, PrecioTotal) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiisd", $id_comprador, $id_vendedor, $id_producto, $unidades, $descripcion, $precio_total);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'mensaje' => 'Cotización enviada.',
            'producto' => [
                'nombre' => $producto['nombre'],
                'imagen' => $producto['imagen'],
                'precio' => $precio_total,
                'unidades' => $unidades,
                'detalles' => $descripcion
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'mensaje' => 'Error al guardar cotización.']);
    }


    $stmt->close();
}
