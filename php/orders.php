<?php
// Incluido por profile.php. $profile_owner_id y $is_own_profile vienen de la sesión temporal.
// Esta sección SOLO debe ser accesible si $is_own_profile es true y el usuario es Cliente.

$current_profile_owner_id = $_SESSION['profile_owner_id_for_section'] ?? null;
$profile_owner_tipo = null; // Necesitamos saber el tipo del dueño del perfil

if (!($_SESSION['is_own_profile_for_section'] ?? false) || !$current_profile_owner_id) {
    echo "<p class='text-red-500 text-center'>Acceso denegado a esta sección.</p>";
    return;
}

// Obtener el tipo de usuario del dueño del perfil actual (debería ser Cliente)
$db_user_type = new Database();
$conn_user_type = $db_user_type->getConexion();
$stmt_user_type = $conn_user_type->prepare("SELECT tipo FROM Usuario WHERE id_usuario = ?");
if ($stmt_user_type) {
    $stmt_user_type->bind_param("i", $current_profile_owner_id);
    $stmt_user_type->execute();
    $res_user_type = $stmt_user_type->get_result();
    if ($row_user_type = $res_user_type->fetch_assoc()) {
        $profile_owner_tipo = $row_user_type['tipo'];
    }
    $stmt_user_type->close();
}
$conn_user_type->close();


if ($profile_owner_tipo !== 'Cliente') {
     echo "<p class='text-orange-600 text-center'>Esta sección es solo para Clientes.</p>";
    return;
}


$pedidos_del_cliente = [];
$db_orders = new Database();
$conexion_orders = $db_orders->getConexion();

// La tabla Venta tiene id_cliente. Asumimos que esta es la fuente.
// Necesitamos nombre del producto e imagen.
$queryPedidos = "
    SELECT 
        v.id_venta, 
        v.FechaHoraVenta, 
        v.PrecioTotal,
        v.CantidadVendida,
        p.Nombre AS nombre_producto,
        (SELECT mp.URL FROM MultimediaProducto mp WHERE mp.id_producto = v.id_producto ORDER BY mp.id_multimedia ASC LIMIT 1) AS imagen_producto,
        vend.nombre_usuario AS nombre_vendedor,
        v.id_producto
    FROM Venta v
    JOIN Producto p ON v.id_producto = p.id_producto
    JOIN Usuario vend ON v.id_vendedor = vend.id_usuario
    WHERE v.id_cliente = ? 
    ORDER BY v.FechaHoraVenta DESC
";
$stmt = $conexion_orders->prepare($queryPedidos);

if ($stmt) {
    $stmt->bind_param("i", $current_profile_owner_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    while ($fila = $resultado->fetch_assoc()) {
        $pedidos_del_cliente[] = $fila;
    }
    $stmt->close();
}
$conexion_orders->close();

?>
<!DOCTYPE html> <html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles_orders.css"> </head>
<body class="bg-gray-100"> <div class="orders-container w-full max-w-4xl mx-auto"> 
    <h2 class="text-2xl font-bold text-gray-700 mb-6 text-center">Mi Historial de Pedidos</h2>

    <div id="orders-list" class="orders-list space-y-4">
        <?php if (!empty($pedidos_del_cliente)): ?>
            <?php foreach ($pedidos_del_cliente as $pedido): 
                $fechaPedido = new DateTime($pedido['FechaHoraVenta']);
            ?>
                <div class="order-item bg-white p-4 rounded-lg shadow flex items-center gap-4">
                    <img src="<?php echo htmlspecialchars(str_replace('..', '.', $pedido['imagen_producto'] ?? '../recursos/placeholder.png')); ?>" 
                         alt="<?php echo htmlspecialchars($pedido['nombre_producto']); ?>" 
                         class="w-20 h-20 md:w-28 md:h-28 object-cover rounded-md">
                    <div class="order-details flex-grow">
                        <div class="order-split flex justify-between items-start">
                            <a href="product.php?id_producto=<?php echo $pedido['id_producto']; ?>" class="link-item text-lg font-semibold text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($pedido['nombre_producto']); ?>
                            </a>
                            <label class="order-date text-xs text-gray-500"><?php echo $fechaPedido->format('d/m/Y H:i'); ?></label>
                        </div>
                        <p class="text-sm">Vendido por: <a href="profile.php?id_usuario_perfil=<?php /* ASUMIENDO QUE TIENES ID DEL VENDEDOR EN $pedido */ echo $pedido['id_vendedor_placeholder_o_real']; ?>" class="link-seller"><?php echo htmlspecialchars($pedido['nombre_vendedor']); ?></a></p>
                        <p class="text-sm">Cantidad: <span class="font-medium"><?php echo htmlspecialchars($pedido['CantidadVendida']); ?></span></p>
                        <p class="text-sm">Precio Total: <span class="font-medium text-green-600">$<?php echo htmlspecialchars(number_format($pedido['PrecioTotal'], 2)); ?></span></p>
                        
                        <div class="order-split mt-2">
                             <p class="status Entregado text-xs">Entregado</p> {/* Asumimos estado, o necesitarás una tabla de 'Envio' */}
                            <button class="details-btn text-xs" onclick="alert('Detalles del pedido <?php echo $pedido['id_venta']; ?> aún no implementados.')">Ver Detalles</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-600 text-center py-10">Aún no has realizado ningún pedido.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>