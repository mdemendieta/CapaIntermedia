<?php
// Archivo: PWCI/modelos/CarritoModel.php
require_once 'conexion.php'; // Asegúrate que la ruta a tu archivo de conexión sea correcta

class CarritoModel {
    private $conexion;

    public function __construct($db_connection) { // Espera un objeto de conexión mysqli
        $this->conexion = $db_connection;
    }

    // ... (tus métodos existentes como productoEnCarrito, agregarProducto, eliminarProducto) ...

    /**
     * Obtiene todos los productos en el carrito de un usuario con sus detalles.
     * @param int $id_usuario ID del usuario.
     * @return array Lista de productos en el carrito.
     */
    public function obtenerProductosDelCarrito(int $id_usuario): array {
        $productos = [];
        $query = "SELECT
                        p.id_producto,
                        p.Nombre,
                        p.Precio,
                        p.Inventario, /* Stock disponible del producto */
                        cc.Cantidad AS CantidadEnCarrito, /* Cantidad que el usuario tiene en el carrito */
                        (SELECT mp.URL FROM MultimediaProducto mp WHERE mp.id_producto = p.id_producto ORDER BY mp.id_multimedia ASC LIMIT 1) AS imagen_url
                    FROM CarritoCompras cc
                    JOIN Producto p ON cc.id_producto = p.id_producto
                    WHERE cc.id_usuario = ?
                    ORDER BY cc.FechaAgregado DESC";

        $stmt = $this->conexion->prepare($query);
        if (!$stmt) {
            // En un entorno real, registrarías este error.
            // error_log("Error al preparar consulta en obtenerProductosDelCarrito: " . $this->conexion->error);
            return []; 
        }

        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
        $stmt->close();
        return $productos;
    }

    /**
     * Actualiza la cantidad de un producto específico en el carrito de un usuario.
     * (Este método es útil si permites cambiar la cantidad directamente en la página del carrito y guardarla en BD)
     * @param int $id_usuario ID del usuario.
     * @param int $id_producto ID del producto.
     * @param int $nuevaCantidad Nueva cantidad del producto.
     * @return array ['success' => bool, 'message' => string]
     */
    public function actualizarCantidadProducto(int $id_usuario, int $id_producto, int $nuevaCantidad): array {
        if ($nuevaCantidad < 1) {
             return ['success' => false, 'message' => 'La cantidad debe ser al menos 1. Para quitar el producto, usa el botón Eliminar.'];
        }

        // Verificar stock disponible del producto
        $queryStock = "SELECT Inventario FROM Producto WHERE id_producto = ?";
        $stmtStock = $this->conexion->prepare($queryStock);
        if (!$stmtStock) {
             return ['success' => false, 'message' => 'Error al preparar la verificación de stock.'];
        }
        $stmtStock->bind_param("i", $id_producto);
        $stmtStock->execute();
        $resultStock = $stmtStock->get_result();
        $productoDB = $resultStock->fetch_assoc();
        $stmtStock->close();

        if (!$productoDB) {
            return ['success' => false, 'message' => 'Producto no encontrado.'];
        }

        if ($nuevaCantidad > $productoDB['Inventario']) {
            return ['success' => false, 'message' => 'La cantidad solicitada (' . $nuevaCantidad . ') excede el stock disponible (' . $productoDB['Inventario'] . ').'];
        }

        $query = "UPDATE CarritoCompras SET Cantidad = ? WHERE id_usuario = ? AND id_producto = ?";
        $stmt = $this->conexion->prepare($query);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar la actualización de cantidad: ' . $this->conexion->error];
        }
        $stmt->bind_param("iii", $nuevaCantidad, $id_usuario, $id_producto);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $stmt->close();
                return ['success' => true, 'message' => 'Cantidad actualizada en el carrito.'];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'No se actualizó la cantidad (producto no en carrito o misma cantidad).'];
            }
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al actualizar la cantidad: ' . $error];
        }
    }
}
?>