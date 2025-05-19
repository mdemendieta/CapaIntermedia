<?php
// modelos/PedidoModel.php

class PedidoModel {
    private $conexion;

    public function __construct($dbConexion) {
        $this->conexion = $dbConexion;
    }

    public function getHistorialPedidosUsuario($id_usuario, $filtros = []) {
        // Para el historial de pedidos, el usuario es el cliente (comprador)
        $sql = "SELECT v.id_venta, p.Nombre as nombre_producto, 
                       u_vendedor.nombre_usuario as vendedor_nombre, v.PrecioTotal, v.FechaHoraVenta, v.CantidadVendida,
                       (SELECT mp.URL FROM MultimediaProducto mp WHERE mp.id_producto = v.id_producto ORDER BY mp.id_multimedia ASC LIMIT 1) as imagen_producto,
                       'Entregado' as estado_pedido -- Este estado debería venir de una tabla de 'EstadoPedido' o similar si la tienes.
                FROM Venta v
                JOIN Producto p ON v.id_producto = p.id_producto
                JOIN Usuario u_vendedor ON v.id_vendedor = u_vendedor.id_usuario
                WHERE v.id_cliente = ?";
        
        $params = [$id_usuario];
        $types = "i";

        if (!empty($filtros['termino_busqueda'])) {
            $sql .= " AND p.Nombre LIKE ?";
            $params[] = '%' . $filtros['termino_busqueda'] . '%';
            $types .= "s";
        }
        // Asumiendo que tienes un id_categoria en la tabla Producto para filtrar por categoría de producto en el pedido.
        // Y que $filtros['categoria'] es el id_categoria.
        if (!empty($filtros['categoria']) && $filtros['categoria'] !== 'all') {
            $sql .= " AND p.id_categoria = ?";
            $params[] = $filtros['categoria'];
            $types .= "i";
        }
        // El filtro de estado_pedido es más complejo si no tienes un campo 'estado' en la tabla 'Venta'.
        // Si 'Entregado', 'Transportado', etc., son estados que manejas externamente o no tienes, esta parte del filtro no funcionará directamente.
        // Por ahora, la consulta base solo trae 'Entregado' como placeholder.
        if (!empty($filtros['estado']) && $filtros['estado'] !== 'all') {
            // Esta condición necesitaría un campo real en la BD para funcionar.
            // $sql .= " AND v.estado_actual_del_pedido = ?"; 
            // $params[] = $filtros['estado'];
            // $types .= "s";
        }
        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND DATE(v.FechaHoraVenta) >= ?";
            $params[] = $filtros['fecha_inicio'];
            $types .= "s";
        }
        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND DATE(v.FechaHoraVenta) <= ?";
            $params[] = $filtros['fecha_fin'];
            $types .= "s";
        }

        $sql .= " ORDER BY v.FechaHoraVenta DESC";
        
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            // Log error: $this->conexion->error
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $pedidos = [];
        while ($row = $result->fetch_assoc()) {
            $pedidos[] = $row;
        }
        $stmt->close();
        return $pedidos;
    }

    public function getCategoriasFiltro() {
        $categorias = [];
        $query = "SELECT id_categoria, NombreCategoria FROM Categoria ORDER BY NombreCategoria ASC";
        $result = $this->conexion->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categorias[] = $row;
            }
        }
        return $categorias;
    }

    public function getResumenVentasVendedor($id_vendedor, $filtros_fecha = []) {
        $sql = "SELECT p.Nombre as nombre_producto, SUM(v.CantidadVendida) as total_unidades, SUM(v.PrecioTotal) as total_ingresos, 
                       DATE_FORMAT(v.FechaHoraVenta, '%Y-%m') as mes_venta
                FROM Venta v
                JOIN Producto p ON v.id_producto = p.id_producto
                WHERE v.id_vendedor = ?";
        
        $params = [$id_vendedor];
        $types = "i";

        if (!empty($filtros_fecha['mes_anio'])) {
            $sql .= " AND DATE_FORMAT(v.FechaHoraVenta, '%Y-%m') = ?";
            $params[] = $filtros_fecha['mes_anio'];
            $types .= "s";
        }
        $sql .= " GROUP BY p.id_producto, p.Nombre, mes_venta ORDER BY mes_venta DESC, total_ingresos DESC";
        
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            // Log error
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $ventas = [];
        while ($row = $result->fetch_assoc()) {
            $ventas[] = $row;
        }
        $stmt->close();
        return $ventas;
    }
}
?>