<?php
// Este archivo es incluido por profile.php, por lo que session_start() y la conexión ya existen.
// Las variables $profile_owner_id y $is_own_profile se esperan de la sesión temporal.

$current_profile_owner_id = $_SESSION['profile_owner_id_for_section'] ?? null;
// $current_is_own_profile = $_SESSION['is_own_profile_for_section'] ?? false; // Para este archivo, no es tan relevante si es propio o no, solo de quién son los productos.
$visitor_id = $_SESSION['id_usuario'] ?? null; // Para la funcionalidad "Añadir a mi lista"

$productos_del_vendedor = [];

if ($current_profile_owner_id) {
    $db_vp = new Database(); // Nueva instancia para evitar conflictos si $conexion_global se usa en otro lado
    $conexion_vp = $db_vp->getConexion();

    $stmt = $conexion_vp->prepare(
        "SELECT p.id_producto, p.Nombre, p.Descripcion, p.Precio, p.Inventario, p.Tipo, p.id_categoria,
        (SELECT mp.URL FROM MultimediaProducto mp WHERE mp.id_producto = p.id_producto ORDER BY mp.id_multimedia ASC LIMIT 1) AS imagen_principal
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
    }
    $conexion_vp->close();
}
?>

<div class="w-full">
    <h2 class="text-2xl font-bold text-gray-700 mb-6 text-center">Productos Publicados</h2>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="vendedor-product-grid">
        <?php if (!empty($productos_del_vendedor)): ?>
            <?php foreach ($productos_del_vendedor as $producto): 
                $producto_id_js = $producto['id_producto'];
            ?>
            <div class="product-card bg-gray-800 rounded-[10px] shadow-lg flex flex-col overflow-hidden" 
                 data-nombre="<?php echo htmlspecialchars(strtolower($producto['Nombre'])); ?>"
                 data-precio="<?php echo htmlspecialchars($producto['Precio'] ?? '0'); // Default a 0 si es null (para cotizar) ?>">

                <div class="swiper-container-custom swiper-container-vp-<?php echo $producto_id_js; ?>">
                    <div class="swiper-wrapper">
                        <?php
                        // Re-abrir conexión para imágenes o pasar $conexion_global
                        $db_img = new Database(); $conn_img = $db_img->getConexion();
                        $stmtImagenes = $conn_img->prepare("SELECT URL FROM MultimediaProducto WHERE id_producto = ? ORDER BY id_multimedia ASC");
                        $tieneImagenes = false;
                        if($stmtImagenes){
                            $stmtImagenes->bind_param("i", $producto['id_producto']);
                            $stmtImagenes->execute();
                            $resImagenes = $stmtImagenes->get_result();
                            while ($img = $resImagenes->fetch_assoc()) {
                                echo '<div class="swiper-slide"><img src="' . htmlspecialchars(str_replace('..', '.', $img['URL'])) . '" alt="' . htmlspecialchars($producto['Nombre']) . '"></div>';
                                $tieneImagenes = true;
                            }
                            $stmtImagenes->close();
                        }
                        if (!$tieneImagenes) {
                            echo '<div class="swiper-slide"><img src="../recursos/placeholder.png" alt="Sin imagen"></div>';
                        }
                        $conn_img->close();
                        ?>
                    </div>
                    <div class="swiper-pagination swiper-pagination-vp-<?php echo $producto_id_js; ?>"></div>
                    <div class="swiper-button-prev swiper-button-prev-vp-<?php echo $producto_id_js; ?>"></div>
                    <div class="swiper-button-next swiper-button-next-vp-<?php echo $producto_id_js; ?>"></div>
                </div>

                <div class="product-info text-white p-4">
                    <div>
                        <h3 class="text-lg font-bold truncate" title="<?php echo htmlspecialchars($producto['Nombre']); ?>">
                            <a href="product.php?id_producto=<?php echo $producto['id_producto']; ?>" class="hover:underline">
                                <?php echo htmlspecialchars($producto['Nombre']); ?>
                            </a>
                        </h3>
                        <?php if ($producto['Tipo'] == 'Vender' && isset($producto['Precio'])): ?>
                            <p class="text-md text-green-400">$<?php echo htmlspecialchars(number_format($producto['Precio'], 2)); ?></p>
                        <?php elseif ($producto['Tipo'] == 'Cotizar'): ?>
                            <p class="text-md text-blue-400">Producto para Cotizar</p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-400 mt-1 mb-2 h-10 overflow-hidden"><?php echo htmlspecialchars(substr($producto['Descripcion'], 0, 50)) . (strlen($producto['Descripcion']) > 50 ? '...' : ''); ?></p>
                    </div>

                    <?php if ($visitor_id && $visitor_id != $current_profile_owner_id): // Solo mostrar si el visitante no es el dueño del perfil/producto ?>
                        <form action="../modelos/agregar_a_lista.php" method="POST" class="mt-auto add-to-list-form-vp">
                            <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
                            <select name="id_lista" class="w-full p-2 mb-2 border border-gray-600 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm">
                                <option value="">Añadir a mi lista...</option>
                                <?php
                                // Re-abrir conexión para listas del visitante o pasar $conexion_global
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
                                ?>
                            </select>
                            <button type="submit" class="w-full bg-orange-500 text-white px-3 py-2 rounded-lg hover:bg-orange-600 transition text-sm">Añadir</button>
                        </form>
                    <?php elseif (!$visitor_id): ?>
                         <p class="text-xs text-gray-400 mt-auto">Inicia sesión para añadir a listas.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-600 col-span-full text-center py-10">Este vendedor aún no tiene productos publicados.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Asegúrate que este script se ejecute después de que el DOM esté cargado.
// Si profile.php ya tiene un event listener para DOMContentLoaded, puedes mover esto adentro.
document.addEventListener('DOMContentLoaded', function () {
    const productGridVendedor = document.getElementById('vendedor-product-grid');
    if (productGridVendedor) { // Solo ejecutar si estamos en esta sección
        productGridVendedor.querySelectorAll('.product-card').forEach(card => {
            const swiperElement = card.querySelector('.swiper-container-custom');
            if (swiperElement) {
                const productIdMatch = swiperElement.className.match(/swiper-container-vp-(\d+)/);
                if (productIdMatch && productIdMatch[1]) {
                    const productId = productIdMatch[1];
                    new Swiper(`.swiper-container-vp-${productId}`, {
                        loop: true,
                        pagination: { el: `.swiper-pagination-vp-${productId}`, clickable: true },
                        navigation: { nextEl: `.swiper-button-next-vp-${productId}`, prevEl: `.swiper-button-prev-vp-${productId}` },
                    });
                }
            }
        });

        document.querySelectorAll('.add-to-list-form-vp').forEach(form => {
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
    }
});
</script>