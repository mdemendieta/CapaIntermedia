<?php
// modelos/ProductoModel.php
// No necesita require_once 'conexion.php' si el controlador que lo instancia ya la cargó y le pasa la conexión.

class ProductoModel {
    private $conexion;

    public function __construct($dbConexion) {
        $this->conexion = $dbConexion;
    }

    // Método que ya tenías para cotizaciones
    public function ProductosVendedor($idVendedor): array   {
        $sql = "SELECT id_producto, Nombre AS nombre, imagen_principal AS imagen FROM VistaProductoCotizacion WHERE id_vendedor = ?";
        $stmt = $this->conexion->prepare($sql);
        if(!$stmt) return [];
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

    // Método que ya tenías para cotizaciones
    public function obtenerProductoID($idProducto) {
        $sql = "SELECT id_producto, id_vendedor, Nombre AS nombre, imagen_principal AS imagen, Inventario  AS inventario FROM VistaDetalleProducto WHERE id_producto = ?";
        $stmt = $this->conexion->prepare($sql);
        if(!$stmt) return null;
        $stmt->bind_param("i", $idProducto);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $producto = $resultado->fetch_assoc();
        $stmt->close();
        return $producto;
    }

    // Nuevos métodos para el perfil
    public function getProductosPublicadosPorVendedor($id_vendedor, $filtros = []) {
        $sql = "SELECT p.id_producto, p.Nombre, p.Descripcion, p.Precio, p.Inventario, p.Estado, p.Tipo,
                       (SELECT mp.URL FROM MultimediaProducto mp WHERE mp.id_producto = p.id_producto ORDER BY mp.id_multimedia ASC LIMIT 1) as imagen_principal,
                       c.NombreCategoria
                FROM Producto p
                LEFT JOIN Categoria c ON p.id_categoria = c.id_categoria
                WHERE p.id_vendedor = ? AND p.Estado = 'Aprobado'";

        $params = [$id_vendedor];
        $types = "i";

        if (!empty($filtros['termino_busqueda'])) {
            $sql .= " AND (p.Nombre LIKE ? OR p.Descripcion LIKE ?)";
            $searchTerm = '%' . $filtros['termino_busqueda'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }
        if (!empty($filtros['categoria']) && $filtros['categoria'] !== 'all') {
            $sql .= " AND p.id_categoria = ?";
            $params[] = $filtros['categoria'];
            $types .= "i";
        }
        if (!empty($filtros['rango_precio'])) {
            // Implementar lógica de rango de precio aquí, ej: p.Precio BETWEEN ? AND ?
            // O parsear el string "0-50", "50-100", etc.
            // Ejemplo simple para "0-50":
            // if ($filtros['rango_precio'] == '0-50') { $sql .= " AND p.Precio <= 50"; }
        }
        $sql .= " ORDER BY p.FechaCreacion DESC";

        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return [];
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }
        $stmt->close();
        return $productos;
    }

    public function getListasParaProducto($id_usuario) {
        $listas = [];
        $stmt = $this->conexion->prepare("SELECT id_lista, NombreLista FROM ListaUsuario WHERE id_usuario = ? ORDER BY NombreLista ASC");
        if ($stmt) {
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            while ($lista = $resultado->fetch_assoc()) {
                $listas[] = $lista;
            }
            $stmt->close();
        }
        return $listas;
    }

    public function agregarProductoALista($id_producto, $id_lista, $id_usuario_actual) {
        $valStmt = $this->conexion->prepare("SELECT id_lista FROM ListaUsuario WHERE id_lista = ? AND id_usuario = ?");
        if(!$valStmt) return ['success' => false, 'mensaje' => 'Error al validar lista.'];
        $valStmt->bind_param("ii", $id_lista, $id_usuario_actual);
        $valStmt->execute();
        $resultVal = $valStmt->get_result();
        $valStmt->close();

        if ($resultVal->num_rows === 0) {
            return ['success' => false, 'mensaje' => 'Lista no válida o no pertenece al usuario.'];
        }

        $stmt = $this->conexion->prepare("INSERT INTO ProductoEnLista (id_producto, id_lista) VALUES (?, ?)");
        if(!$stmt) return ['success' => false, 'mensaje' => 'Error al preparar inserción.'];
        $stmt->bind_param("ii", $id_producto, $id_lista);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'mensaje' => 'Producto añadido a la lista.'];
        } else {
            $error = $stmt->error;
            $stmt->close();
            if (strpos(strtolower($error), 'duplicate entry') !== false) {
                 return ['success' => false, 'mensaje' => 'Este producto ya está en la lista seleccionada.'];
            }
            return ['success' => false, 'mensaje' => 'Error al añadir producto a la lista: ' . $error];
        }
    }

    public function getCategoriasActivas() {
        $categorias = [];
        $query = "SELECT DISTINCT c.id_categoria, c.NombreCategoria 
                  FROM Categoria c 
                  JOIN Producto p ON c.id_categoria = p.id_categoria
                  WHERE p.Estado = 'Aprobado' OR p.id_vendedor IN (SELECT id_usuario FROM Usuario) /* O alguna otra lógica para mostrar categorías relevantes */
                  ORDER BY c.NombreCategoria ASC";
        $result = $this->conexion->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categorias[] = $row;
            }
        }
        return $categorias;
    }

    public function getProductosPorEstadoVendedor($id_vendedor, $filtros = []) {
        $sql = "SELECT p.id_producto, p.Nombre, p.Precio, p.Estado, p.FechaCreacion,
                       (SELECT mp.URL FROM MultimediaProducto mp WHERE mp.id_producto = p.id_producto ORDER BY mp.id_multimedia ASC LIMIT 1) as imagen_principal
                FROM Producto p
                WHERE p.id_vendedor = ?";

        $params = [$id_vendedor];
        $types = "i";

        if (!empty($filtros['estado_producto']) && $filtros['estado_producto'] !== 'all') {
            $sql .= " AND p.Estado = ?";
            $params[] = $filtros['estado_producto'];
            $types .= "s";
        }
        if (!empty($filtros['termino_busqueda'])) {
            $sql .= " AND p.Nombre LIKE ?";
            $searchTerm = '%' . $filtros['termino_busqueda'] . '%';
            $params[] = $searchTerm;
            $types .= "s";
        }
        $sql .= " ORDER BY p.FechaCreacion DESC";

        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) return [];
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }
        $stmt->close();
        return $productos;
    }
}
?>