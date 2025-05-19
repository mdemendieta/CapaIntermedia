<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../modelos/conexion.php';
$db = new Database();
$conexion = $db->getConexion();

$id_lista_actual = null;
$nombre_lista_actual = "Productos"; // Default
$is_list_public = false;
$list_owner_id_for_this_list = null;
$productos_de_lista = [];
$can_view_list = false;
$mensaje_edicion_lista = '';
$mensaje_edicion_tipo = ''; // 'success' o 'error'

$visitor_id = $_SESSION['id_usuario'] ?? null;
$profile_context_owner_id = isset($_GET['id_usuario_perfil']) ? (int)$_GET['id_usuario_perfil'] : null;


if (isset($_GET['id_lista'])) {
    $id_lista_actual = (int)$_GET['id_lista'];

    // --- PROCESAR ACTUALIZACIÓN DE DETALLES DE LA LISTA ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_list_details') {
        if (isset($_POST['id_lista_form']) && (int)$_POST['id_lista_form'] === $id_lista_actual && $visitor_id) {
            // Verificar que el usuario logueado es el propietario de la lista
            $stmtCheckOwner = $conexion->prepare("SELECT id_usuario FROM ListaUsuario WHERE id_lista = ?");
            $stmtCheckOwner->bind_param("i", $id_lista_actual);
            $stmtCheckOwner->execute();
            $resultOwner = $stmtCheckOwner->get_result();
            if ($ownerRow = $resultOwner->fetch_assoc()) {
                if ((int)$ownerRow['id_usuario'] === $visitor_id) {
                    $nuevo_nombre_lista = trim($_POST['nombre_lista_editar'] ?? '');
                    $nueva_publica_lista = isset($_POST['publica_lista_editar']) ? 1 : 0;

                    if (!empty($nuevo_nombre_lista)) {
                        $stmtUpdate = $conexion->prepare("UPDATE ListaUsuario SET NombreLista = ?, Publica = ? WHERE id_lista = ? AND id_usuario = ?");
                        $stmtUpdate->bind_param("siii", $nuevo_nombre_lista, $nueva_publica_lista, $id_lista_actual, $visitor_id);
                        if ($stmtUpdate->execute()) {
                            if ($stmtUpdate->affected_rows > 0) {
                                $mensaje_edicion_lista = "Detalles de la lista actualizados correctamente.";
                                $mensaje_edicion_tipo = 'success';
                                // Actualizar variables para mostrar los nuevos datos inmediatamente
                                $nombre_lista_actual = htmlspecialchars($nuevo_nombre_lista);
                                $is_list_public = (bool)$nueva_publica_lista;
                            } else {
                                $mensaje_edicion_lista = "No se realizaron cambios o la lista no pertenece a este usuario.";
                                $mensaje_edicion_tipo = 'info';
                            }
                        } else {
                            $mensaje_edicion_lista = "Error al actualizar la lista: " . $stmtUpdate->error;
                            $mensaje_edicion_tipo = 'error';
                        }
                        $stmtUpdate->close();
                    } else {
                        $mensaje_edicion_lista = "El nombre de la lista no puede estar vacío.";
                        $mensaje_edicion_tipo = 'error';
                    }
                } else {
                    $mensaje_edicion_lista = "No tienes permiso para editar esta lista.";
                    $mensaje_edicion_tipo = 'error';
                }
            }
            $stmtCheckOwner->close();
        }
    }


    // --- Obtener detalles de la lista y productos (después de posible actualización) ---
    $stmtListDetails = $conexion->prepare("SELECT NombreLista, id_usuario, Publica FROM ListaUsuario WHERE id_lista = ?");
    if ($stmtListDetails) {
        $stmtListDetails->bind_param("i", $id_lista_actual);
        $stmtListDetails->execute();
        $resultListDetails = $stmtListDetails->get_result();

        if ($listData = $resultListDetails->fetch_assoc()) {
            // Si no hubo POST de edición, o si falló, estos son los datos de la BD
            if (empty($mensaje_edicion_lista) || $mensaje_edicion_tipo === 'error') {
                 $nombre_lista_actual = htmlspecialchars($listData['NombreLista']);
                 $is_list_public = (bool)$listData['Publica'];
            }
            $list_owner_id_for_this_list = (int)$listData['id_usuario'];

            if ($is_list_public || ($visitor_id && $visitor_id === $list_owner_id_for_this_list)) {
                $can_view_list = true;
            }
        }
        $stmtListDetails->close();
    }

    if ($can_view_list) {
        $stmtProductos = $conexion->prepare(
            "SELECT p.id_producto, p.Nombre, p.Descripcion, p.Precio, p.Inventario, p.Tipo,
            (SELECT mp.URL FROM MultimediaProducto mp WHERE mp.id_producto = p.id_producto ORDER BY mp.id_multimedia ASC LIMIT 1) AS imagen_principal
             FROM Producto p
             JOIN ProductoEnLista pel ON p.id_producto = pel.id_producto
             WHERE pel.id_lista = ? AND p.Estado = 'Aprobado'"
        );
        if ($stmtProductos) {
            $stmtProductos->bind_param("i", $id_lista_actual);
            $stmtProductos->execute();
            $resultadoProductos = $stmtProductos->get_result();
            while ($producto = $resultadoProductos->fetch_assoc()) {
                $productos_de_lista[] = $producto;
            }
            $stmtProductos->close();
        }
    }
}

$id_usuario_actual_para_listas = $visitor_id; // Para el dropdown "Añadir a mi lista..."
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos en <?php echo $nombre_lista_actual; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css">
    <style>
        .product-card { height: auto; min-height:480px; display: flex; flex-direction: column; } /* Aumentar min-height para botón extra */
        .swiper-container-custom { width: 100%; height: 250px; border-radius: 8px; overflow: hidden; }
        .swiper-slide img { width: 100%; height: 100%; object-fit: cover; }
        .product-info { padding: 1rem; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .swiper-button-next, .swiper-button-prev { color: #fff; background-color: rgba(0,0,0,0.3); border-radius: 50%; width: 30px; height: 30px; line-height: 30px; text-align: center; }
        .swiper-button-next::after, .swiper-button-prev::after { font-size: 16px; }
        .mensaje-lista { padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; }
        .mensaje-lista.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje-lista.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .mensaje-lista.info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body class="bg-gray-100">

    <?php include('navbar.php'); ?>

    <div id="main-content" class="flex-1 transition-all duration-300 p-6 bg-orange-100 min-h-screen">
        <div class="flex justify-between items-center mb-2">
            <h1 class="text-3xl font-bold text-gray-800">
                <?php echo $nombre_lista_actual; ?>
            </h1>
            <?php
            $volver_a_perfil_id = $profile_context_owner_id ?: $list_owner_id_for_this_list ?: $visitor_id;
            if ($volver_a_perfil_id) {
                echo '<a href="profile.php?id_usuario_perfil=' . $volver_a_perfil_id . '&seccion=listas_personales_'.($visitor_id == $volver_a_perfil_id ? ($_SESSION['tipo'] == 'Vendedor' ? 'vendedor' : 'cliente') : 'cliente' ) .'" class="text-orange-600 hover:underline">&larr; Volver a listas del perfil</a>';
            }
            ?>
        </div>

        <?php if (!empty($mensaje_edicion_lista)): ?>
            <div class="mensaje-lista <?php echo $mensaje_edicion_tipo; ?>">
                <?php echo $mensaje_edicion_lista; ?>
            </div>
        <?php endif; ?>

        <?php if ($can_view_list && $visitor_id && $visitor_id === $list_owner_id_for_this_list): ?>
            <div class="my-6 p-4 bg-white shadow-md rounded-lg">
                <h2 class="text-xl font-semibold text-gray-700 mb-3">Editar Detalles de la Lista</h2>
                <form method="POST" action="listaproductos.php?id_lista=<?php echo $id_lista_actual; ?><?php if($profile_context_owner_id) echo '&id_usuario_perfil='.$profile_context_owner_id; ?>" class="space-y-3">
                    <input type="hidden" name="action" value="update_list_details">
                    <input type="hidden" name="id_lista_form" value="<?php echo $id_lista_actual; ?>">
                    <div>
                        <label for="nombre_lista_editar" class="block text-sm font-medium text-gray-700">Nombre de la Lista:</label>
                        <input type="text" name="nombre_lista_editar" id="nombre_lista_editar" value="<?php echo str_replace('"', '&quot;', strip_tags($nombre_lista_actual)); // strip_tags para evitar problemas con htmlspecialchars previo ?>" required class="mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="publica_lista_editar" id="publica_lista_editar" value="1" <?php if($is_list_public) echo 'checked'; ?> class="h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                        <label for="publica_lista_editar" class="ml-2 block text-sm text-gray-900">Hacer esta lista pública</label>
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-md hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                            Guardar Cambios de Lista
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>


        <?php if ($can_view_list): ?>
            <div class="p-4 bg-white shadow-md rounded-lg mb-6">
                 <h2 class="text-xl font-bold mb-4">Filtrar Productos en esta Lista</h2>
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="search-in-list" class="block text-gray-700 font-medium mb-1">Buscar en lista:</label>
                        <input type="text" id="search-in-list" class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label for="price-filter-list" class="block text-gray-700 font-medium mb-1">Rango de Precio:</label>
                        <select id="price-filter-list" class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Todos</option> <option value="0-50">$0 - $50</option> <option value="50-100">$50 - $100</option> <option value="100-200">$100 - $200</option> <option value="200+">$200+</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="product-grid-lp">
                <?php if (!empty($productos_de_lista)): ?>
                    <?php foreach ($productos_de_lista as $producto):
                        $producto_id_js = $producto['id_producto'];
                    ?>
                        <div class="product-card bg-gray-800 rounded-[10px] shadow-lg flex flex-col overflow-hidden"
                             data-id-producto="<?php echo $producto_id_js; ?>"
                             data-nombre="<?php echo htmlspecialchars(strtolower($producto['Nombre'])); ?>"
                             data-precio="<?php echo htmlspecialchars($producto['Precio'] ?? '0'); ?>">

                            <div class="swiper-container-custom swiper-container-lp-<?php echo $producto_id_js; ?>">
                                <div class="swiper-wrapper">
                                    <?php
                                    $db_img_lp = new Database(); $conn_img_lp = $db_img_lp->getConexion();
                                    $stmtImagenes_lp = $conn_img_lp->prepare("SELECT URL FROM MultimediaProducto WHERE id_producto = ? ORDER BY id_multimedia ASC");
                                    $tieneImagenes_lp = false;
                                    if($stmtImagenes_lp){
                                        $stmtImagenes_lp->bind_param("i", $producto['id_producto']);
                                        $stmtImagenes_lp->execute();
                                        $resImagenes_lp = $stmtImagenes_lp->get_result();
                                        while ($img_lp = $resImagenes_lp->fetch_assoc()) {
                                            echo '<div class="swiper-slide"><img src="' . htmlspecialchars(str_replace('..', '.', $img_lp['URL'])) . '" alt="' . htmlspecialchars($producto['Nombre']) . '"></div>';
                                            $tieneImagenes_lp = true;
                                        }
                                        if (!$tieneImagenes_lp) echo '<div class="swiper-slide"><img src="../recursos/placeholder.png" alt="Sin imagen"></div>';
                                        $stmtImagenes_lp->close();
                                    }
                                    $conn_img_lp->close();
                                    ?>
                                </div>
                                <div class="swiper-pagination swiper-pagination-lp-<?php echo $producto_id_js; ?>"></div>
                                <div class="swiper-button-prev swiper-button-prev-lp-<?php echo $producto_id_js; ?>"></div>
                                <div class="swiper-button-next swiper-button-next-lp-<?php echo $producto_id_js; ?>"></div>
                            </div>

                            <div class="product-info text-white">
                                <div> {/* Contenedor para info de producto y botón de eliminar de esta lista */}
                                    <h3 class="text-lg font-bold truncate" title="<?php echo htmlspecialchars($producto['Nombre']); ?>"><?php echo htmlspecialchars($producto['Nombre']); ?></h3>
                                    <?php if ($producto['Tipo'] == 'Vender' && isset($producto['Precio'])): ?>
                                        <p class="text-md text-green-400">$<?php echo htmlspecialchars(number_format($producto['Precio'], 2)); ?></p>
                                    <?php elseif ($producto['Tipo'] == 'Cotizar'): ?>
                                        <p class="text-md text-blue-400">Producto para Cotizar</p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-400 mb-1 h-10 overflow-hidden"><?php echo htmlspecialchars(substr($producto['Descripcion'], 0, 50)) . (strlen($producto['Descripcion']) > 50 ? '...' : ''); ?></p>
                                </div>
                                
                                <div class="mt-auto space-y-2"> {/* Contenedor para ambos formularios/botones */}
                                    <?php if ($visitor_id && $visitor_id === $list_owner_id_for_this_list): // Botón para eliminar de ESTA lista (dueño de la lista) ?>
                                        <button onclick="removeProductFromCurrentList(<?php echo $id_lista_actual; ?>, <?php echo $producto_id_js; ?>, this)"
                                                class="w-full bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 transition text-xs">
                                            Eliminar de esta lista
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($id_usuario_actual_para_listas !== null): // Formulario para añadir a OTRA lista (cualquier usuario logueado) ?>
                                        <form action="../modelos/agregar_a_lista.php" method="POST" class="add-to-other-list-form">
                                            <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
                                            <select name="id_lista" class="w-full p-1.5 mb-1 border border-gray-600 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-xs">
                                                <option value="">Añadir a otra lista...</option>
                                                <?php
                                                $db_list_lp = new Database(); $conn_list_lp = $db_list_lp->getConexion();
                                                $consultaListasUsuario_lp = $conn_list_lp->prepare("SELECT id_lista, NombreLista FROM ListaUsuario WHERE id_usuario = ?");
                                                if($consultaListasUsuario_lp){
                                                    $consultaListasUsuario_lp->bind_param("i", $id_usuario_actual_para_listas);
                                                    $consultaListasUsuario_lp->execute();
                                                    $resultadoListasUsuario_lp = $consultaListasUsuario_lp->get_result();
                                                    while ($listaUser_lp = $resultadoListasUsuario_lp->fetch_assoc()) {
                                                        if ($listaUser_lp['id_lista'] != $id_lista_actual) { // No mostrar la lista actual en el dropdown
                                                            echo '<option value="' . $listaUser_lp['id_lista'] . '">' . htmlspecialchars($listaUser_lp['NombreLista']) . '</option>';
                                                        }
                                                    }
                                                    $consultaListasUsuario_lp->close();
                                                }
                                                $conn_list_lp->close();
                                                ?>
                                            </select>
                                            <button type="submit" class="w-full bg-orange-500 text-white px-3 py-1.5 rounded-lg hover:bg-orange-600 transition text-sm">Añadir</button>
                                        </form>
                                    <?php elseif (!$visitor_id): ?>
                                        <p class="text-xs text-gray-400">Inicia sesión para guardar productos.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-600 col-span-full text-center py-10">Esta lista está vacía o los productos no están disponibles.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-red-500 col-span-full text-center text-xl py-10">No tienes permiso para ver esta lista o la lista no existe.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Swiper initialization
        document.querySelectorAll('#product-grid-lp .product-card').forEach(card => {
            const swiperElement = card.querySelector('.swiper-container-custom');
            if (swiperElement) {
                const productIdMatch = swiperElement.className.match(/swiper-container-lp-(\d+)/);
                if (productIdMatch && productIdMatch[1]) {
                    const productId = productIdMatch[1];
                    new Swiper(`.swiper-container-lp-${productId}`, {
                        loop: true,
                        pagination: { el: `.swiper-pagination-lp-${productId}`, clickable: true },
                        navigation: { nextEl: `.swiper-button-next-lp-${productId}`, prevEl: `.swiper-button-prev-lp-${productId}` },
                    });
                }
            }
        });

        // Filtros (básicos, como antes)
        const searchInput = document.getElementById('search-in-list');
        const priceFilter = document.getElementById('price-filter-list');
        function applyFilters() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : "";
            const priceRange = priceFilter ? priceFilter.value : "";
            document.querySelectorAll('#product-grid-lp .product-card').forEach(card => {
                const nombre = (card.dataset.nombre || '').toLowerCase();
                const precio = parseFloat(card.dataset.precio || "0");
                let show = true;
                if (searchTerm && !nombre.includes(searchTerm)) show = false;
                if (priceRange) {
                    const [minStr, maxStr] = priceRange.split('-');
                    const min = parseFloat(minStr);
                    const max = maxStr ? parseFloat(maxStr) : Infinity; // Para rangos como "200+"
                    if (max === Infinity && precio < min) show = false;
                    else if (max !== Infinity && (precio < min || precio > max)) show = false;
                }
                card.style.display = show ? 'flex' : 'none';
            });
        }
        if (searchInput) searchInput.addEventListener('input', applyFilters);
        if (priceFilter) priceFilter.addEventListener('change', applyFilters);

        // Manejo de formularios "Añadir a OTRA lista"
        document.querySelectorAll('.add-to-other-list-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const button = this.querySelector('button[type="submit"]');
                if (!formData.get('id_lista')) { // Evitar envío si no se seleccionó lista
                    alert('Por favor, selecciona una lista de destino.');
                    return;
                }
                button.disabled = true; button.textContent = 'Añadiendo...';
                fetch(this.action, { method: 'POST', body: formData })
                .then(response => response.text())
                .then(data => {
                    alert(data); button.disabled = false; button.textContent = 'Añadir';
                    this.querySelector('select[name="id_lista"]').value = "";
                })
                .catch(error => {
                    console.error('Error:', error); alert('Error al añadir a la lista.');
                    button.disabled = false; button.textContent = 'Añadir';
                });
            });
        });
    });

    // Función para eliminar producto de la lista actual
    function removeProductFromCurrentList(idLista, idProducto, buttonElement) {
        if (!confirm("¿Estás seguro de que quieres eliminar este producto de la lista actual?")) {
            return;
        }
        const formData = new FormData();
        formData.append('id_lista', idLista);
        formData.append('id_producto', idProducto);
        // No necesitamos 'action' aquí si el script PHP solo hace una cosa.
        // Si el script PHP maneja múltiples acciones, añade: formData.append('action', 'remove_product');

        const originalButtonText = buttonElement.textContent;
        buttonElement.disabled = true;
        buttonElement.textContent = 'Eliminando...';

        fetch('../controladores/eliminar_producto_de_lista.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Eliminar la tarjeta del producto del DOM
                const productCard = buttonElement.closest('.product-card');
                if (productCard) {
                    productCard.style.transition = 'opacity 0.5s ease';
                    productCard.style.opacity = '0';
                    setTimeout(() => productCard.remove(), 500);
                }
                // Actualizar contador de productos si tienes uno
            } else {
                alert('Error: ' + data.message);
                buttonElement.disabled = false;
                buttonElement.textContent = originalButtonText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al intentar eliminar el producto.');
            buttonElement.disabled = false;
            buttonElement.textContent = originalButtonText;
        });
    }
    </script>
</body>
</html>