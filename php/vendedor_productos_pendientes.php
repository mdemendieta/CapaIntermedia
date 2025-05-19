<?php
// Incluido por profile.php. $profile_owner_id y $is_own_profile vienen de la sesión temporal.
// Esta sección SOLO debe ser accesible si $is_own_profile es true.
// $current_profile_owner_id es el id_usuario del vendedor logueado.

$current_profile_owner_id = $_SESSION['profile_owner_id_for_section'] ?? null;

if (!($_SESSION['is_own_profile_for_section'] ?? false) || !$current_profile_owner_id) {
    echo "<p class='text-red-500 text-center py-4'>Acceso denegado a esta sección.</p>";
    return; // No mostrar nada más si no es el perfil propio
}

$productos_propios_estado = [];
// Asumo que tienes una clase Database y se instancia correctamente.
// require_once '../modelos/conexion.php'; // Descomenta si es necesario y ajusta la ruta
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
} else {
    // Considera registrar este error o mostrar un mensaje más amigable si la preparación falla.
    echo "<p class='text-red-500 text-center py-4'>Error al preparar la consulta de productos.</p>";
}
$conexion_vpp->close();
?>

<div class="w-full p-4 md:p-6">
    <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Mis Publicaciones Pendientes o Rechazadas</h2>

    <?php if (!empty($productos_propios_estado)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($productos_propios_estado as $producto): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-300 ease-in-out">
                    <div class="relative">
                        <img src="<?php echo htmlspecialchars($producto['imagen_principal'] ?? '../recursos/placeholder.png'); ?>"
                             alt="Imagen de <?php echo htmlspecialchars($producto['Nombre']); ?>"
                             class="w-full h-56 object-cover">
                        <?php
                            $estadoClassBg = '';
                            $estadoClassText = '';
                            switch ($producto['Estado']) {
                                case 'Pendiente':
                                    $estadoClassBg = 'bg-yellow-500';
                                    $estadoClassText = 'text-yellow-800';
                                    break;
                                case 'Rechazado':
                                    $estadoClassBg = 'bg-red-500';
                                    $estadoClassText = 'text-red-800';
                                    break;
                            }
                        ?>
                        <span class="absolute top-2 right-2 <?php echo $estadoClassBg; ?> <?php echo $estadoClassText; ?> text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider">
                            <?php echo htmlspecialchars($producto['Estado']); ?>
                        </span>
                    </div>
                    <div class="p-5">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2 truncate" title="<?php echo htmlspecialchars($producto['Nombre']); ?>">
                            <?php echo htmlspecialchars($producto['Nombre']); ?>
                        </h3>

                        <div class="text-sm text-gray-600 space-y-1">
                            <?php if($producto['Tipo'] == 'Vender' && isset($producto['Precio'])): ?>
                                <p>
                                    <span class="font-medium text-gray-700">Precio:</span>
                                    <span class="text-green-600 font-bold">$<?php echo htmlspecialchars(number_format($producto['Precio'], 2)); ?></span>
                                </p>
                            <?php elseif($producto['Tipo'] == 'Cotizar'): ?>
                                <p class="text-blue-600 font-semibold">Producto para Cotizar</p>
                            <?php endif; ?>
                            <p>
                                <span class="font-medium text-gray-700">Stock:</span>
                                <?php echo htmlspecialchars($producto['Inventario']); ?>
                            </p>
                            <?php if (!empty($producto['Descripcion'])): ?>
                                <p class="pt-1">
                                    <span class="font-medium text-gray-700">Descripción:</span>
                                    <?php echo htmlspecialchars(substr($producto['Descripcion'], 0, 70)) . (strlen($producto['Descripcion']) > 70 ? '...' : ''); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2zm3-5a1 1 0 100-2 1 1 0 000 2z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">No hay publicaciones pendientes o rechazadas</h3>
            <p class="mt-1 text-sm text-gray-500">
                ¡Buen trabajo! Parece que todo está al día por aquí.
            </p>
        </div>
    <?php endif; ?>
</div>