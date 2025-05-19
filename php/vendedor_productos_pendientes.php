<?php
// Incluido por profile.php. $profile_owner_id y $is_own_profile vienen de la sesión temporal.
// Esta sección SOLO debe ser accesible si $is_own_profile es true.
// $current_profile_owner_id es el id_usuario del vendedor logueado.

$current_profile_owner_id = $_SESSION['profile_owner_id_for_section'] ?? null;

if (!($_SESSION['is_own_profile_for_section'] ?? false) || !$current_profile_owner_id) {
    echo "<p class='text-red-500 text-center'>Acceso denegado a esta sección.</p>";
    return; // No mostrar nada más si no es el perfil propio
}

$productos_propios_estado = [];
$db_vpp = new Database();
$conexion_vpp = $db_vpp->getConexion();

$stmt = $conexion_vpp->prepare(
    "SELECT p.id_producto, p.Nombre, p.Descripcion, p.Precio, p.Inventario, p.Estado, p.Tipo,
    (SELECT mp.URL FROM MultimediaProducto mp WHERE mp.id_producto = p.id_producto ORDER BY mp.id_multimedia ASC LIMIT 1) AS imagen_principal
     FROM Producto p
     WHERE p.id_vendedor = ? AND p.Estado IN ('Pendiente', 'Rechazado') ORDER BY p.FechaCreacion DESC"
);
if ($stmt) {
    $stmt->bind_param("i", $current_profile_owner_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    while ($fila = $resultado->fetch_assoc()) {
        $productos_propios_estado[] = $fila;
    }
    $stmt->close();
}
$conexion_vpp->close();
?>

<div class="w-full">
    <h2 class="text-2xl font-bold text-gray-700 mb-6 text-center">Mis Publicaciones Pendientes o Rechazadas</h2>

    <?php if (!empty($productos_propios_estado)): ?>
        <div class="space-y-4">
            <?php foreach ($productos_propios_estado as $producto): ?>
                <div class_pers="product-item flex items-center bg-white p-4 rounded-lg shadow">
                    <img src="<?php echo htmlspecialchars(str_replace('..', '.', $producto['imagen_principal'] ?? '../recursos/placeholder.png')); ?>" 
                         alt="<?php echo htmlspecialchars($producto['Nombre']); ?>" 
                         class_pers="w-24 h-24 object-cover rounded-md mr-4">
                    <div class_pers="product-details flex-grow">
                        <h3 class_pers="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($producto['Nombre']); ?></h3>
                        <p class_pers="text-sm text-gray-600">
                            Estado: 
                            <?php 
                                $estadoClass = '';
                                switch ($producto['Estado']) {
                                    case 'Pendiente': $estadoClass = 'text-yellow-600 bg-yellow-100'; break;
                                    case 'Rechazado': $estadoClass = 'text-red-600 bg-red-100'; break;
                                }
                            ?>
                            <span class_pers="font-medium px-2 py-1 rounded-full text-xs <?php echo $estadoClass; ?>">
                                <?php echo htmlspecialchars($producto['Estado']); ?>
                            </span>
                        </p>
                        <?php if($producto['Tipo'] == 'Vender' && isset($producto['Precio'])): ?>
                            <p class_pers="text-sm text-gray-500">Precio: $<?php echo htmlspecialchars(number_format($producto['Precio'], 2)); ?></p>
                        <?php endif; ?>
                        <p class_pers="text-sm text-gray-500">Stock: <?php echo htmlspecialchars($producto['Inventario']); ?></p>
                    </div>
                    </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-600 text-center py-10">No tienes publicaciones pendientes o rechazadas en este momento.</p>
    <?php endif; ?>
</div>