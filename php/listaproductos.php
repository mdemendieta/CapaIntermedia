<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../modelos/conexion.php';
$db = new Database();
$conexion = $db->getConexion();

$id_lista_actual = null;
$nombre_lista_actual = "Productos";
$productos_de_lista = [];
$can_view_list = false;
$list_owner_id_for_this_list = null;

$visitor_id = $_SESSION['id_usuario'] ?? null;
// id_usuario_perfil is the owner of the profile page we MIGHT be coming from,
// but the list itself has its own owner.
$profile_context_owner_id = isset($_GET['id_usuario_perfil']) ? (int)$_GET['id_usuario_perfil'] : null;


if (isset($_GET['id_lista'])) {
    $id_lista_actual = (int)$_GET['id_lista'];

    // Fetch list details: owner and public status
    $stmtListDetails = $conexion->prepare("SELECT NombreLista, id_usuario, Publica FROM ListaUsuario WHERE id_lista = ?");
    if ($stmtListDetails) {
        $stmtListDetails->bind_param("i", $id_lista_actual);
        $stmtListDetails->execute();
        $resultListDetails = $stmtListDetails->get_result();

        if ($listData = $resultListDetails->fetch_assoc()) {
            $nombre_lista_actual = htmlspecialchars($listData['NombreLista']);
            $list_owner_id_for_this_list = (int)$listData['id_usuario'];
            $is_list_public = (bool)$listData['Publica'];

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

$id_usuario_actual_para_listas = $visitor_id; // For the "Add to my list" dropdown

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos en <?php echo $nombre_lista_actual; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css">
    <style>
        .product-card { height: 450px; display: flex; flex-direction: column; }
        .swiper-container-custom { width: 100%; height: 250px; border-radius: 8px; overflow: hidden; }
        .swiper-slide img { width: 100%; height: 100%; object-fit: cover; }
        .product-info { padding: 1rem; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .swiper-button-next, .swiper-button-prev { color: #fff; background-color: rgba(0,0,0,0.3); border-radius: 50%; width: 30px; height: 30px; line-height: 30px; text-align: center; }
        .swiper-button-next::after, .swiper-button-prev::after { font-size: 16px; }
    </style>
</head>
<body class="bg-gray-100">

    <?php include('navbar.php'); ?>

    <div id="main-content" class="flex-1 transition-all duration-300 p-6 bg-orange-100 min-h-screen">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <?php echo $nombre_lista_actual; ?>
            </h1>
            <?php
            // Determine the correct profile owner ID for the "Volver" link.
            // If we have $list_owner_id_for_this_list, that's the owner of THIS list.
            // If $profile_context_owner_id is set, it means we likely came from a specific profile page.
            $volver_a_perfil_id = $profile_context_owner_id ?: $list_owner_id_for_this_list ?: $visitor_id;
            if ($volver_a_perfil_id) {
                echo '<a href="profile.php?id_usuario_perfil=' . $volver_a_perfil_id . '&seccion=listas" class="text-orange-600 hover:underline">&larr; Volver a las listas del perfil</a>';
            }
            ?>
        </div>

        <?php if ($can_view_list): ?>
            <div class="p-4 bg-white shadow-md rounded-lg mb-6">
                <h2 class="text-xl font-bold mb-4">Filtrar Productos en esta Lista</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="search-in-list" class="block text-gray-700 font-medium mb-1">Buscar en lista:</label>
                        <input type="text" id="search-in-list" name="search" class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label for="price-filter-list" class="block text-gray-700 font-medium mb-1">Rango de Precio:</label>
                        <select id="price-filter-list" name="price-filter" class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Todos</option> <option value="0-50">$0 - $50</option> <option value="50-100">$50 - $100</option> <option value="100-200">$100 - $200</option> <option value="200+">$200+</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="product-grid">
                <?php if (!empty($productos_de_lista)): ?>
                    <?php foreach ($productos_de_lista as $producto):
                        $producto_id_para_js = $producto['id_producto'];
                    ?>
                        <div class="product-card bg-gray-800 rounded-[10px] shadow-lg flex flex-col overflow-hidden"
                             data-nombre="<?php echo htmlspecialchars(strtolower($producto['Nombre'])); ?>"
                             data-precio="<?php echo htmlspecialchars($producto['Precio'] ?? '0'); ?>"
                             data-categoria="">

                            <div class="swiper-container-custom swiper-container-<?php echo $producto_id_para_js; ?>">
                                <div class="swiper-wrapper">
                                    <?php
                                    $stmtImagenes = $conexion->prepare("SELECT URL FROM MultimediaProducto WHERE id_producto = ? ORDER BY id_multimedia ASC");
                                    if ($stmtImagenes) {
                                        $stmtImagenes->bind_param("i", $producto['id_producto']);
                                        $stmtImagenes->execute();
                                        $resImagenes = $stmtImagenes->get_result();
                                        $tieneImagenes = false;
                                        while ($img = $resImagenes->fetch_assoc()) {
                                            echo '<div class="swiper-slide"><img src="' . htmlspecialchars(str_replace('..', '.', $img['URL'])) . '" alt="' . htmlspecialchars($producto['Nombre']) . '"></div>';
                                            $tieneImagenes = true;
                                        }
                                        if (!$tieneImagenes) echo '<div class="swiper-slide"><img src="../recursos/placeholder.png" alt="Sin imagen"></div>';
                                        $stmtImagenes->close();
                                    }
                                    ?>
                                </div>
                                <div class="swiper-pagination swiper-pagination-<?php echo $producto_id_para_js; ?>"></div>
                                <div class="swiper-button-prev swiper-button-prev-<?php echo $producto_id_para_js; ?>"></div>
                                <div class="swiper-button-next swiper-button-next-<?php echo $producto_id_para_js; ?>"></div>
                            </div>

                            <div class="product-info text-white">
                                <div>
                                    <h2 class="text-lg font-bold truncate" title="<?php echo htmlspecialchars($producto['Nombre']); ?>"><?php echo htmlspecialchars($producto['Nombre']); ?></h2>
                                    <?php if ($producto['Tipo'] == 'Vender' && isset($producto['Precio'])): ?>
                                        <p class="text-md text-green-400">$<?php echo htmlspecialchars($producto['Precio']); ?></p>
                                    <?php elseif ($producto['Tipo'] == 'Cotizar'): ?>
                                        <p class="text-md text-blue-400">Producto para Cotizar</p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-400 mb-2 h-10 overflow-hidden"><?php echo htmlspecialchars(substr($producto['Descripcion'], 0, 50)) . (strlen($producto['Descripcion']) > 50 ? '...' : ''); ?></p>
                                </div>

                                <?php if ($id_usuario_actual_para_listas !== null): ?>
                                    <form action="../modelos/agregar_a_lista.php" method="POST" class="mt-auto add-to-list-form">
                                        <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
                                        <select name="id_lista" class="w-full p-2 mb-2 border border-gray-600 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm">
                                            <option value="">Añadir a mi lista...</option>
                                            <?php
                                            $consultaListasUsuario = $conexion->prepare("SELECT id_lista, NombreLista FROM ListaUsuario WHERE id_usuario = ?");
                                            if($consultaListasUsuario){
                                                $consultaListasUsuario->bind_param("i", $id_usuario_actual_para_listas);
                                                $consultaListasUsuario->execute();
                                                $resultadoListasUsuario = $consultaListasUsuario->get_result();
                                                while ($listaUser = $resultadoListasUsuario->fetch_assoc()) {
                                                    echo '<option value="' . $listaUser['id_lista'] . '">' . htmlspecialchars($listaUser['NombreLista']) . '</option>';
                                                }
                                                $consultaListasUsuario->close();
                                            }
                                            ?>
                                        </select>
                                        <button type="submit" class="w-full bg-orange-500 text-white px-3 py-2 rounded-lg hover:bg-orange-600 transition text-sm">Añadir</button>
                                    </form>
                                <?php else: ?>
                                    <p class="text-red-500 text-sm mt-auto">Inicia sesión para guardar productos.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-600 col-span-full text-center">Esta lista está vacía o los productos no están disponibles.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-red-500 col-span-full text-center text-xl">No tienes permiso para ver esta lista o la lista no existe.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.product-card').forEach(card => {
                const swiperElement = card.querySelector('.swiper-container-custom');
                if (swiperElement) {
                    const productIdMatch = swiperElement.className.match(/swiper-container-(\d+)/);
                    if (productIdMatch && productIdMatch[1]) {
                        const productId = productIdMatch[1];
                        new Swiper(`.swiper-container-${productId}`, {
                            loop: true,
                            pagination: { el: `.swiper-pagination-${productId}`, clickable: true },
                            navigation: { nextEl: `.swiper-button-next-${productId}`, prevEl: `.swiper-button-prev-${productId}` },
                        });
                    }
                }
            });

            const searchInput = document.getElementById('search-in-list');
            const priceFilter = document.getElementById('price-filter-list');
            function applyFilters() {
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : "";
                const priceRange = priceFilter ? priceFilter.value : "";
                document.querySelectorAll('#product-grid .product-card').forEach(card => {
                    const nombre = (card.dataset.nombre || '').toLowerCase();
                    const precio = parseFloat(card.dataset.precio || "0");
                    let show = true;
                    if (searchTerm && !nombre.includes(searchTerm)) show = false;
                    if (priceRange) {
                        const [minStr, maxStr] = priceRange.split('-');
                        const min = parseFloat(minStr);
                        const max = maxStr ? parseFloat(maxStr) : Infinity;
                        if (precio < min || precio > max) show = false;
                    }
                    card.style.display = show ? 'flex' : 'none';
                });
            }
            if (searchInput) searchInput.addEventListener('input', applyFilters);
            if (priceFilter) priceFilter.addEventListener('change', applyFilters);

            document.querySelectorAll('.add-to-list-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const button = this.querySelector('button[type="submit"]');
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
    </script>
</body>
</html>