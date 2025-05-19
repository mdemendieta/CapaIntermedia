<?php
// vistas/parciales/listaproductos_partial_view.php
// Vars: $productosPublicados, $listasDelUsuario, $categorias_filtro_prod
// $_GET: q_prodpub, cat_prodpub, precio_prodpub
?>
<div class="filtros-productos-publicados p-4 bg-gray-100 rounded-lg shadow mb-6">
    <h2 class="text-xl font-bold mb-4 text-gray-700">Filtrar Mis Productos Publicados</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="search-prodpub" class="block text-sm font-medium text-gray-700">Buscar:</label>
            <input type="text" id="search-prodpub" name="q_prodpub" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="Nombre del producto..." value="<?php echo htmlspecialchars($_GET['q_prodpub'] ?? ''); ?>">
        </div>
        <div>
            <label for="price-filter-prodpub" class="block text-sm font-medium text-gray-700">Rango de Precio:</label>
            <select id="price-filter-prodpub" name="precio_prodpub" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                <option value="">Cualquier precio</option>
                <option value="0-50" <?php echo (($_GET['precio_prodpub'] ?? '') == '0-50') ? 'selected' : ''; ?>>$0 - $50</option>
                <option value="50-100" <?php echo (($_GET['precio_prodpub'] ?? '') == '50-100') ? 'selected' : ''; ?>>$50 - $100</option>
                <option value="100-200" <?php echo (($_GET['precio_prodpub'] ?? '') == '100-200') ? 'selected' : ''; ?>>$100 - $200</option>
                <option value="200+" <?php echo (($_GET['precio_prodpub'] ?? '') == '200+') ? 'selected' : ''; ?>>$200+</option>
            </select>
        </div>
        <div>
            <label for="category-filter-prodpub" class="block text-sm font-medium text-gray-700">Categorías:</label>
            <select id="category-filter-prodpub" name="cat_prodpub" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                 <option value="all">Todas las categorías</option>
                <?php foreach ($categorias_filtro_prod as $categoria): ?>
                    <option value="<?php echo htmlspecialchars($categoria['id_categoria']); ?>" <?php echo (($_GET['cat_prodpub'] ?? '') == $categoria['id_categoria']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['NombreCategoria']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
     <button id="btn-aplicar-filtros-prodpub" class="w-full mt-4 bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded">
        Aplicar Filtros
    </button>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="lista-productos-publicados-container">
    <?php if (!empty($productosPublicados)): ?>
        <?php foreach ($productosPublicados as $producto): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col">
                <a href="../php/product.php?id=<?php echo $producto['id_producto']; ?>" class="block h-48 overflow-hidden"> <?php
                    $rutaImagenProducto = htmlspecialchars(!empty($producto['imagen_principal']) && file_exists($producto['imagen_principal']) ? $producto['imagen_principal'] : '../recursos/placeholder.png');
                    ?>
                    <img src="<?php echo $rutaImagenProducto; ?>" alt="<?php echo htmlspecialchars($producto['Nombre']); ?>" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                </a>
                <div class="p-4 flex flex-col flex-grow">
                    <h3 class="text-md font-semibold text-gray-800 mb-1 truncate" title="<?php echo htmlspecialchars($producto['Nombre']); ?>">
                        <a href="../php/product.php?id=<?php echo $producto['id_producto']; ?>" class="hover:text-orange-600">
                            <?php echo htmlspecialchars($producto['Nombre']); ?>
                        </a>
                    </h3>
                    <p class="text-xs text-gray-500 mb-2">Categoría: <?php echo htmlspecialchars($producto['NombreCategoria'] ?? 'N/A'); ?></p>
                    <p class="text-lg font-bold text-orange-500 mb-3">$<?php echo htmlspecialchars(number_format((float)$producto['Precio'], 2)); ?></p>
                    
                    <div class="mt-auto">
                        <?php if (isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] != $producto['id_vendedor'] && !empty($listasDelUsuario)): // No mostrar si es producto propio ?>
                            <form class="form-add-to-list-prodpub" method="POST" data-product-id="<?php echo $producto['id_producto']; ?>">
                                <input type="hidden" name="id_producto_a_lista" value="<?php echo $producto['id_producto']; ?>">
                                <select name="id_lista_destino" class="w-full p-2 mb-2 border border-gray-300 bg-gray-50 text-gray-700 rounded-md focus:outline-none focus:ring-1 focus:ring-orange-500 text-sm">
                                    <option value="">Añadir a lista...</option>
                                    <?php foreach ($listasDelUsuario as $lista): ?>
                                        <option value="<?php echo $lista['id_lista']; ?>"><?php echo htmlspecialchars($lista['NombreLista']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="w-full bg-blue-950 text-white px-3 py-1.5 rounded-md hover:bg-orange-500 transition-colors duration-150 text-sm font-semibold">Añadir</button>
                            </form>
                        <?php elseif (isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == $producto['id_vendedor']): ?>
                            <p class="text-xs text-gray-400 mt-2 text-center">Este es tu producto.</p>
                             <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-gray-500 col-span-full text-center py-10">No tienes productos publicados que coincidan con los filtros.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnFiltrosProdPub = document.getElementById('btn-aplicar-filtros-prodpub');
    if (btnFiltrosProdPub) {
        btnFiltrosProdPub.addEventListener('click', function() {
            const q_prodpub = document.getElementById('search-prodpub').value;
            const cat_prodpub = document.getElementById('category-filter-prodpub').value;
            const precio_prodpub = document.getElementById('price-filter-prodpub').value;

            let queryString = `&q_prodpub=${encodeURIComponent(q_prodpub)}`;
            queryString += `&cat_prodpub=${encodeURIComponent(cat_prodpub)}`;
            queryString += `&precio_prodpub=${encodeURIComponent(precio_prodpub)}`;
            
            if (typeof window.cargarContenidoGlobal === 'function') {
                window.cargarContenidoGlobal('productospubli', null, queryString);
            }
        });
    }

    const formsAddToPubList = document.querySelectorAll('.form-add-to-list-prodpub');
    formsAddToPubList.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const idListaDestino = formData.get('id_lista_destino');

            if (!idListaDestino) {
                alert('Por favor, selecciona una lista.');
                return;
            }
            
            fetch(`../controladores/ProfileController.php?action=loadSection&s=productospubli`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.mensaje || 'Producto añadido a la lista.');
                    this.querySelector('select[name="id_lista_destino"]').value = ""; // Reset select
                } else {
                    alert('Error: ' + (data.mensaje || 'No se pudo añadir el producto.'));
                }
            })
            .catch(error => {
                console.error('Error al añadir a lista:', error);
                alert('Error de comunicación al añadir a la lista.');
            });
        });
    });
});
</script>