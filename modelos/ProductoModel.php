<?php
class ProductoModel {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    // Obtener todos los productos activos de un vendedor
    public function ProductosVendedor($idVendedor): array   {
    $sql = "SELECT id_producto, Nombre AS nombre, imagen_principal AS imagen FROM VistaProductoCotizacion WHERE id_vendedor = ?";

    $stmt = $this->conexion->prepare($sql);
    $stmt->bind_param("i", $idVendedor);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $productos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $productos[] = $fila;
    }

    $stmt->close();
    return $productos;
}


    // Obtener datos de un producto específico
    public function obtenerProductoID($idProducto) {
    $sql = "SELECT id_producto, id_vendedor, Nombre AS nombre, imagen_principal AS imagen, Inventario  AS inventario FROM VistaDetalleProducto WHERE id_producto = ?";

    $stmt = $this->conexion->prepare($sql);
    $stmt->bind_param("i", $idProducto);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $producto = $resultado->fetch_assoc();

    $stmt->close();
    return $producto;
}

}
