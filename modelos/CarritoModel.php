<?php
// Archivo: CapaIntermedia/modelos/CarritoModel.php
require_once 'conexion.php'; // Asegúrate que la ruta a tu archivo de conexión sea correcta

class CarritoModel {
    private $conexion;

    public function __construct() {
        $db = new Database(); // Instancia tu clase de conexión
        $this->conexion = $db->getConexion();
    }

    /**
     * Verifica si un producto específico ya está en el carrito de un usuario.
     * @param int $id_usuario ID del usuario.
     * @param int $id_producto ID del producto.
     * @return bool True si el producto está en el carrito, False en caso contrario.
     */
    public function productoEnCarrito(int $id_usuario, int $id_producto): bool {
        $query = "SELECT id_producto FROM CarritoCompras WHERE id_usuario = ? AND id_producto = ?";
        $stmt = $this->conexion->prepare($query);
        if (!$stmt) {
            // En un entorno de producción, loguearías este error en lugar de (o además de) solo retornar false.
            // error_log("Error al preparar consulta en productoEnCarrito: " . $this->conexion->error);
            return false; 
        }
        $stmt->bind_param("ii", $id_usuario, $id_producto);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->num_rows > 0;
    }

    /**
     * Agrega un producto al carrito de un usuario.
     * Si el producto ya existe, no hace nada (según la especificación para product.php).
     * La cantidad por defecto es 1.
     * @param int $id_usuario ID del usuario.
     * @param int $id_producto ID del producto.
     * @param int $cantidad Cantidad del producto a añadir (default 1).
     * @return array ['success' => bool, 'message' => string]
     */
    public function agregarProducto(int $id_usuario, int $id_producto, int $cantidad = 1): array {
        if ($cantidad < 1) {
            return ['success' => false, 'message' => 'La cantidad debe ser al menos 1.'];
        }

        // Si ya está en el carrito, no hacemos nada más desde product.php, según la nueva lógica
        if ($this->productoEnCarrito($id_usuario, $id_producto)) {
            return ['success' => true, 'message' => 'El producto ya se encuentra en el carrito.'];
        }

        // Opcional: Verificar si el producto existe en la tabla 'Producto' y si hay inventario
        // $queryCheckProducto = "SELECT Inventario FROM Producto WHERE id_producto = ?";
        // ... (código para verificar inventario si se desea implementar aquí) ...

        $query = "INSERT INTO CarritoCompras (id_usuario, id_producto, Cantidad, FechaAgregado) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
        $stmt = $this->conexion->prepare($query);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar la consulta para agregar al carrito: ' . $this->conexion->error];
        }
        $stmt->bind_param("iii", $id_usuario, $id_producto, $cantidad);

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Producto añadido al carrito exitosamente.'];
        } else {
            $error = $stmt->error;
            $stmt->close();
            // Podría ser un error de clave duplicada si la verificación previa falló, aunque no debería con la lógica actual.
            return ['success' => false, 'message' => 'Error al añadir producto al carrito: ' . $error];
        }
    }

    /**
     * Elimina un producto específico del carrito de un usuario.
     * @param int $id_usuario ID del usuario.
     * @param int $id_producto ID del producto.
     * @return array ['success' => bool, 'message' => string]
     */
    public function eliminarProducto(int $id_usuario, int $id_producto): array {
        $query = "DELETE FROM CarritoCompras WHERE id_usuario = ? AND id_producto = ?";
        $stmt = $this->conexion->prepare($query);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar la consulta para eliminar del carrito: ' . $this->conexion->error];
        }
        $stmt->bind_param("ii", $id_usuario, $id_producto);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $stmt->close();
                return ['success' => true, 'message' => 'Producto eliminado del carrito.'];
            } else {
                $stmt->close();
                // Esto podría significar que el producto no estaba en el carrito para empezar
                return ['success' => false, 'message' => 'El producto no se encontraba en el carrito o ya fue eliminado.'];
            }
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Error al eliminar producto del carrito: ' . $error];
        }
    }

    /**
     * Obtiene todos los productos del carrito de un usuario con sus detalles.
     * @param int $id_usuario ID del usuario.
     * @return array Lista de productos en el carrito.
     */
    public function obtenerProductosDelCarrito(int $id_usuario): array {
        $productos = [];
        $query = "SELECT
                    p.id_producto,
                    p.Nombre,
                    p.Precio,
                    p.Inventario,
                    cc.Cantidad AS CantidadEnCarrito,
                    (SELECT mp.URL FROM MultimediaProducto mp WHERE mp.id_producto = p.id_producto ORDER BY mp.id_multimedia ASC LIMIT 1) AS imagen_url
                  FROM
                    CarritoCompras cc
                  JOIN
                    Producto p ON cc.id_producto = p.id_producto
                  WHERE
                    cc.id_usuario = ?";
        
        $stmt = $this->conexion->prepare($query);
        if (!$stmt) {
            // En un entorno de producción, sería bueno loguear este error.
            // error_log("Error al preparar consulta en obtenerProductosDelCarrito: " . $this->conexion->error);
            return $productos; // Devuelve array vacío en caso de error de preparación
        }
        
        $stmt->bind_param("i", $id_usuario);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $productos[] = $row;
            }
        } else {
            // error_log("Error al ejecutar consulta en obtenerProductosDelCarrito: " . $stmt->error);
        }
        
        $stmt->close();
        return $productos;
    }

    /**
     * Obtiene todos los productos del carrito de un usuario con sus detalles.
     * @param int $id_usuario ID del usuario.
     * @return array Lista de productos en el carrito.
     */
    public function borrarcarrito(int $id_usuario){
        $query = "DELETE FROM carritocompras WHERE id_usuario = ? ";
        $stmt = $this->conexion->prepare($query);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar la consulta para eliminar del carrito: ' . $this->conexion->error];
        }
        $stmt->bind_param("i", $id_usuario);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $stmt->close();
                return ['success' => true, 'message' => 'Producto(s) eliminado(s) del carrito.'];
            } else {
                $stmt->close();
                // Esto podría significar que el producto no estaba en el carrito para empezar
                return ['success' => false, 'message' => 'no hay productos en el carrito'];
            }
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'error al intentar borrar el carrito.' . $error];
        }
    }
    // Futuros métodos para carrito.php:
    // public function actualizarCantidadProducto(int $id_usuario, int $id_producto, int $nuevaCantidad): array { ... }
    // public function vaciarCarrito(int $id_usuario): array { ... }
}
?>