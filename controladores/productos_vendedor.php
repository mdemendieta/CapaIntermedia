<?php
session_start();
require_once '../modelos/ProductoModel.php';
require_once '../modelos/conexion.php';

$db = new Database();
$conexion = $db->getConexion();

$id_vendedor = $_SESSION['id_usuario'] ?? null;

if (!$id_vendedor) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

if (isset($_GET['id'])) {
    $db = new Database();
    $conexion = $db->getConexion();
    $model = new ProductoModel($conexion);

    $producto = $model->obtenerProductoID((int)$_GET['id']);
    echo json_encode($producto);
    exit;
}

$modelo = new ProductoModel($conexion);
$productos = $modelo->ProductosVendedor($id_vendedor);
if (empty($productos)) {
    echo json_encode(['error' => 'No hay productos disponibles']);
    exit;
}else {
    echo json_encode($productos);
}




