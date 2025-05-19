<?php
// vistas/parciales/orders_partial_view.php
// Vars: $pedidos, $categorias_filtro_pedidos
// $_GET params son usados para pre-llenar filtros: q_pedido, cat_pedido, est_pedido, fi_pedido, ff_pedido
?>
<div class="orders-container-partial" style="width: 100%;">
    <div class="filters p-4 bg-gray-100 rounded-lg shadow">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="search-bar-pedidos" class="block text-sm font-medium text-gray-700">Buscar pedido:</label>
                <input type="text" id="search-bar-pedidos" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Nombre del producto..." value="<?php echo htmlspecialchars($_GET['q_pedido'] ?? ''); ?>">
            </div>
            <div>
                <label for="filter-category-pedidos" class="block text-sm font-medium text-gray-700">Categoría:</label>
                <select id="filter-category-pedidos" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                    <option value="all">Todas</option>
                    <?php foreach ($categorias_filtro_pedidos as $categoria): ?>
                        <option value="<?php echo htmlspecialchars($categoria['id_categoria']); ?>" <?php echo (($_GET['cat_pedido'] ?? '') == $categoria['id_categoria']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria['NombreCategoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label for="filter-status-pedidos" class="block text-sm font-medium text-gray-700">Estado:</label>
                <select id="filter-status-pedidos" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                    <option value="all" <?php echo (($_GET['est_pedido'] ?? 'all') == 'all') ? 'selected' : ''; ?>>Todos</option>
                    <option value="Entregado" <?php echo (($_GET['est_pedido'] ?? '') == 'Entregado') ? 'selected' : ''; ?>>Entregado</option>
                    <option value="Transportado" <?php echo (($_GET['est_pedido'] ?? '') == 'Transportado') ? 'selected' : ''; ?>>Transportado</option>
                    <option value="Pendiente" <?php echo (($_GET['est_pedido'] ?? '') == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="Cancelado" <?php echo (($_GET['est_pedido'] ?? '') == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            <div>
                <label for="filter-date-start-pedidos" class="block text-sm font-medium text-gray-700">Desde:</label>
                <input type="date" id="filter-date-start-pedidos" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500" value="<?php echo htmlspecialchars($_GET['fi_pedido'] ?? ''); ?>">
            </div>
            <div>
                <label for="filter-date-end-pedidos" class="block text-sm font-medium text-gray-700">Hasta:</label>
                <input type="date" id="filter-date-end-pedidos" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500" value="<?php echo htmlspecialchars($_GET['ff_pedido'] ?? ''); ?>">
            </div>
        </div>
        <button id="btn-aplicar-filtros-pedidos" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded">
            Aplicar Filtros
        </button>
    </div>

    <div id="orders-list-partial" class="orders-list mt-6 space-y-4">
        <?php if (!empty($pedidos)): ?>
            <?php foreach ($pedidos as $pedido): ?>
                <div class="order-item bg-white p-4 rounded-lg shadow-md flex gap-4 items-center">
                    <img src="<?php echo htmlspecialchars(!empty($pedido['imagen_producto']) && file_exists($pedido['imagen_producto']) ? $pedido['imagen_producto'] : '../recursos/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($pedido['nombre_producto']); ?>" class="w-24 h-24 object-cover rounded-md">
                    <div class="order-details flex-grow">
                        <div class="flex justify-between items-start">
                            <a href="#" class="link-item text-lg font-semibold text-orange-600 hover:underline"><?php echo htmlspecialchars($pedido['nombre_producto']); ?></a>
                            <label class="order-date text-xs text-gray-500"><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($pedido['FechaHoraVenta']))); ?></label>
                        </div>
                        <p class="text-sm text-gray-600">Vendido por: <a href="#" class="link-seller font-medium text-blue-600 hover:underline"><?php echo htmlspecialchars($pedido['vendedor_nombre']); ?></a></p>
                        <p class="text-sm text-gray-600">Cantidad: <?php echo htmlspecialchars($pedido['CantidadVendida']); ?></p>
                        <p class="text-md font-semibold">Precio Total:<label class="text-green-600"> $<?php echo htmlspecialchars(number_format((float)$pedido['PrecioTotal'], 2)); ?></label></p>
                        <div class="flex justify-between items-center mt-2">
                            <p class="status <?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $pedido['estado_pedido']))); ?> text-xs font-semibold px-2 py-1 rounded-full 
                                <?php 
                                    switch(strtolower($pedido['estado_pedido'])){
                                        case 'entregado': echo 'bg-green-100 text-green-700'; break;
                                        case 'pendiente': echo 'bg-yellow-100 text-yellow-700'; break;
                                        case 'transportado': echo 'bg-blue-100 text-blue-700'; break;
                                        case 'cancelado': echo 'bg-red-100 text-red-700'; break;
                                        default: echo 'bg-gray-100 text-gray-700'; break;
                                    }
                                ?>">
                                <?php echo htmlspecialchars($pedido['estado_pedido']); ?>
                            </p>
                            <button class="details-btn bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs px-3 py-1 rounded-md">Ver Detalles</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-500 text-center py-10">No hay pedidos en tu historial que coincidan con los filtros.</p>
        <?php endif; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() { // Asegura que el script se ejecute después de cargar el DOM de esta vista parcial
    const btnAplicarFiltros = document.getElementById('btn-aplicar-filtros-pedidos');
    if (btnAplicarFiltros) {
        btnAplicarFiltros.addEventListener('click', function() {
            const q_pedido = document.getElementById('search-bar-pedidos').value;
            const cat_pedido = document.getElementById('filter-category-pedidos').value;
            const est_pedido = document.getElementById('filter-status-pedidos').value;
            const fi_pedido = document.getElementById('filter-date-start-pedidos').value;
            const ff_pedido = document.getElementById('filter-date-end-pedidos').value;

            let queryString = `&q_pedido=${encodeURIComponent(q_pedido)}`;
            queryString += `&cat_pedido=${encodeURIComponent(cat_pedido)}`;
            queryString += `&est_pedido=${encodeURIComponent(est_pedido)}`;
            queryString += `&fi_pedido=${encodeURIComponent(fi_pedido)}`;
            queryString += `&ff_pedido=${encodeURIComponent(ff_pedido)}`;
            
            if (typeof window.cargarContenidoGlobal === 'function') {
                 // El segundo argumento 'null' es porque no estamos haciendo clic en un botón de sección principal
                 window.cargarContenidoGlobal('historial', null, queryString);
            } else {
                console.warn("Función cargarContenidoGlobal no definida en profile_view.php. Los filtros de pedidos no funcionarán vía AJAX.");
            }
        });
    }
});
</script>