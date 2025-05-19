<?php
// Este archivo es incluido por profile.php, por lo que session_start() y la conexión ya existen.
// Las variables $profile_owner_id y $is_own_profile se esperan de la sesión temporal.

$current_profile_owner_id = $_SESSION['profile_owner_id_for_section'] ?? null;
$visitor_id = $_SESSION['id_usuario'] ?? null; // Para la funcionalidad "Añadir a mi lista"

$productos_del_vendedor = [];

if ($current_profile_owner_id) {
    if (class_exists('Database')) {
        $db_vp = new Database(); 
        $conexion_vp = $db_vp->getConexion();

        $stmt = $conexion_vp->prepare(
            "SELECT p.id_producto, p.Nombre, p.Descripcion, p.Precio, p.Inventario, p.Tipo, p.id_categoria,
            (SELECT mp.URL FROM MultimediaProducto mp WHERE mp.id_producto = p.id_producto ORDER BY mp.id_multimedia ASC LIMIT 1) AS imagen_principal_single
             FROM Producto p
             WHERE p.id_vendedor = ? AND p.Estado = 'Aprobado' ORDER BY p.FechaCreacion DESC"
        );
        if ($stmt) {
            $stmt->bind_param("i", $current_profile_owner_id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            while ($fila = $resultado->fetch_assoc()) {
                $productos_del_vendedor[] = $fila;
            }
            $stmt->close();
        } else {
            echo "<p class='text-red-500 text-center py-4'>Error al preparar la consulta de productos publicados.</p>";
        }
        $conexion_vp->close();
    } else {
        echo "<p class='text-red-500 text-center py-4'>Error: La clase Database no está disponible.</p>";
    }
}
?>

<div class="w-full p-4 md:p-6">
    <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Productos Publicados</h2>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="vendedor-product-grid">
        <?php if (!empty($productos_del_vendedor)): ?>
            <?php foreach ($productos_del_vendedor as $producto): 
                $producto_id_js = $producto['id_producto'];
            ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-300 ease-in-out flex flex-col"
                 data-nombre="<?php echo htmlspecialchars(strtolower($producto['Nombre'])); ?>"
                 data-precio="<?php echo htmlspecialchars($producto['Precio'] ?? '0'); ?>">

                <div class="relative">
                    <div class="swiper-container-custom swiper-container-vp-<?php echo $producto_id_js; ?> w-full h-56">
                        <div class="swiper-wrapper">
                            <?php
                            if (class_exists('Database')) {
                                $db_img_swiper = new Database(); 
                                $conn_img_swiper = $db_img_swiper->getConexion();
                                $stmtImagenesSwiper = $conn_img_swiper->prepare("SELECT URL FROM MultimediaProducto WHERE id_producto = ? ORDER BY id_multimedia ASC");
                                $tieneImagenesSwiper = false;
                                if($stmtImagenesSwiper){
                                    $stmtImagenesSwiper->bind_param("i", $producto['id_producto']);
                                    $stmtImagenesSwiper->execute();
                                    $resImagenesSwiper = $stmtImagenesSwiper->get_result();
                                    while ($img_swiper = $resImagenesSwiper->fetch_assoc()) {
                                        // Corregir ruta para Swiper, asumiendo que $img_swiper['URL'] es como '../recursos/productos/...'
                                        $ruta_correcta_swiper = htmlspecialchars($img_swiper['URL'] ?? '../recursos/placeholder.png');
                                        echo '<div class="swiper-slide"><img src="' . $ruta_correcta_swiper . '" alt="' . htmlspecialchars($producto['Nombre']) . '" class="w-full h-full object-cover"></div>';
                                        $tieneImagenesSwiper = true;
                                    }
                                    $stmtImagenesSwiper->close();
                                }
                                if (!$tieneImagenesSwiper) {
                                    echo '<div class="swiper-slide"><img src="../recursos/placeholder.png" alt="Sin imagen" class="w-full h-full object-cover"></div>';
                                }
                                $conn_img_swiper->close();
                            }
                            ?>
                        </div>
                        <div class="swiper-pagination swiper-pagination-vp-<?php echo $producto_id_js; ?>"></div>
                        <div class="swiper-button-prev swiper-button-prev-vp-<?php echo $producto_id_js; ?> text-white bg-black bg-opacity-30 p-1 rounded-full"></div>
                        <div class="swiper-button-next swiper-button-next-vp-<?php echo $producto_id_js; ?> text-white bg-black bg-opacity-30 p-1 rounded-full"></div>
                    </div>
                </div>

                <div class="p-5 flex flex-col flex-grow"> 
                    <h3 class="text-xl font-semibold text-gray-900 mb-2 truncate" title="<?php echo htmlspecialchars($producto['Nombre']); ?>">
                        <a href="product.php?id=<?php echo $producto['id_producto']; ?>" class="hover:text-orange-600 transition-colors">
                            <?php echo htmlspecialchars($producto['Nombre']); ?>
                        </a>
                    </h3>
                    <div class="text-sm text-gray-600 space-y-1 mb-3">
                        <?php if ($producto['Tipo'] == 'Vender' && isset($producto['Precio'])): ?>
                            <p>
                                <span class="font-medium text-gray-700">Precio:</span>
                                <span class="text-green-600 font-bold">$<?php echo htmlspecialchars(number_format($producto['Precio'], 2)); ?></span>
                            </p>
                        <?php elseif ($producto['Tipo'] == 'Cotizar'): ?>
                            <p class="text-blue-600 font-semibold">Producto para Cotizar</p>
                        <?php endif; ?>
                         <p>
                            <span class="font-medium text-gray-700">Stock:</span>
                            <?php echo htmlspecialchars($producto['Inventario']); ?>
                        </p>
                        <p class="pt-1 h-10 overflow-hidden"> 
                            <span class="font-medium text-gray-700">Descripción:</span>
                            <?php echo htmlspecialchars(substr($producto['Descripcion'], 0, 50)) . (strlen($producto['Descripcion']) > 50 ? '...' : ''); ?>
                        </p>
                    </div>

                    <div class="mt-auto"> 
                        <?php if ($visitor_id && $visitor_id != $current_profile_owner_id): ?>
                            <form action="../modelos/agregar_a_lista.php" method="POST" class="add-to-list-form-vp">
                                <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
                                <select name="id_lista" class="w-full p-2 mb-2 border border-gray-300 bg-gray-50 text-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm">
                                    <option value="">Añadir a mi lista...</option>
                                    <?php
                                    if (class_exists('Database')) {
                                        $db_list = new Database(); $conn_list = $db_list->getConexion();
                                        $consultaListasUsuario = $conn_list->prepare("SELECT id_lista, NombreLista FROM ListaUsuario WHERE id_usuario = ?");
                                        if($consultaListasUsuario) {
                                            $consultaListasUsuario->bind_param("i", $visitor_id);
                                            $consultaListasUsuario->execute();
                                            $resultadoListasUsuario = $consultaListasUsuario->get_result();
                                            while ($listaUser = $resultadoListasUsuario->fetch_assoc()) {
                                                echo '<option value="' . $listaUser['id_lista'] . '">' . htmlspecialchars($listaUser['NombreLista']) . '</option>';
                                            }
                                            $consultaListasUsuario->close();
                                        }
                                        $conn_list->close();
                                    }
                                    ?>
                                </select>
                                <button type="submit" class="w-full bg-orange-500 text-white px-3 py-2 rounded-lg hover:bg-orange-600 transition text-sm font-semibold">Añadir a Lista</button>
                            </form>
                        <?php elseif (!$visitor_id): ?>
                             <p class="text-xs text-gray-500 mt-3 text-center">Inicia sesión para añadir a listas.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">Aún no hay productos publicados</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Este vendedor todavía no tiene productos activos. ¡Vuelve pronto!
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const productGridVendedor = document.getElementById('vendedor-product-grid');
    if (productGridVendedor) {
        productGridVendedor.querySelectorAll('.swiper-container-vp-').forEach(swiperElement => {
            const productIdMatch = swiperElement.className.match(/swiper-container-vp-(\d+)/);
            if (productIdMatch && productIdMatch[1]) {
                const productId = productIdMatch[1];
                new Swiper(`.swiper-container-vp-${productId}`, {
                    loop: true,
                    slidesPerView: 1,
                    spaceBetween: 0,
                    pagination: { 
                        el: `.swiper-pagination-vp-${productId}`, 
                        clickable: true,
                        type: 'bullets', // Puedes cambiar a 'fraction', 'progressbar'
                    },
                    navigation: {
                        nextEl: `.swiper-button-next-vp-${productId}`,
                        prevEl: `.swiper-button-prev-vp-${productId}`,
                    },
                    grabCursor: true,
                });
            }
        });

        document.querySelectorAll('.add-to-list-form-vp').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const selectLista = this.querySelector('select[name="id_lista"]');
                if (!selectLista.value) {
                    alert('Por favor, selecciona una lista.');
                    return;
                }
                const button = this.querySelector('button[type="submit"]');
                button.disabled = true; button.textContent = 'Añadiendo...';
                
                fetch(this.action, { method: 'POST', body: formData })
                .then(response => response.text()) // O response.json() si esperas JSON
                .then(data => {
                    // Aquí puedes procesar la respuesta 'data'. Por ejemplo, si es JSON:
                    // if(data.success) { alert(data.message); } else { alert(data.message); }
                    alert(data); // Para respuesta de texto simple
                    button.disabled = false; button.textContent = 'Añadir a Lista';
                    selectLista.value = ""; // Resetear el select
                })
                .catch(error => {
                    console.error('Error:', error); 
                    alert('Error al añadir a la lista. Intenta de nuevo.');
                    button.disabled = false; button.textContent = 'Añadir a Lista';
                });
            });
        });
    }
});
</script>