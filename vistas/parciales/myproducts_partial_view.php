<?php
// vistas/parciales/myproducts_partial_view.php
// Var: $productosPendientes
// $_GET: q_prodpend, est_prodpend
?>
<div class="my-container-partial p-4 bg-gray-100 rounded-lg shadow" style="width:100%;">
    <div class="filters mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="search-bar-prodpend" class="block text-sm font-medium text-gray-700">Buscar producto/solicitud:</label>
                <input type="text" id="search-bar-prodpend" name="q_prodpend" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="Nombre del producto..." value="<?php echo htmlspecialchars($_GET['q_prodpend'] ?? ''); ?>">
            </div>
            <div>
                <label for="filter-status-prodpend" class="block text-sm font-medium text-gray-700">Estado:</label>
                <select id="filter-status-prodpend" name="est_prodpend" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    <option value="all" <?php echo (($_GET['est_prodpend'] ?? 'all') == 'all') ? 'selected' : ''; ?>>Todos</option>
                    <option value="Pendiente" <?php echo (($_GET['est_prodpend'] ?? '') == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="Aprobado" <?php echo (($_GET['est_prodpend'] ?? '') == 'Aprobado') ? 'selected' : ''; ?>>Aprobado</option>
                    <option value="Rechazado" <?php echo (($_GET['est_prodpend'] ?? '') == 'Rechazado') ? 'selected' : ''; ?>>Rechazado</option>
                </select>
            </div>
        </div>
         <button id="btn-aplicar-filtros-prodpend" class="w-full mt-4 bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded">
            Aplicar Filtros
        </button>
    </div>

    <div id="myProduct-list-partial" class="myProduct-list space-y-4">
        <?php if (!empty($productosPendientes)): ?>
            <?php foreach ($productosPendientes as $producto): ?>
                <div class="product-item bg-white p-4 rounded-lg shadow-md flex gap-4 items-center">
                    <img src="<?php echo htmlspecialchars(!empty($producto['imagen_principal']) && file_exists($producto['imagen_principal']) ? $producto['imagen_principal'] : '../recursos/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($producto['Nombre']); ?>" class="w-20 h-20 object-cover rounded-md">
                    <div class="product-details flex-grow">
                        <div class="flex justify-between items-start">
                            <a href="#" class="link-item text-md font-semibold text-gray-800 hover:text-orange-600"><?php echo htmlspecialchars($producto['Nombre']); ?></a>
                            <label class="product-date text-xs text-gray-500"><?php echo htmlspecialchars(date("d/m/Y", strtotime($producto['FechaCreacion']))); ?></label>
                        </div>
                        <p class="text-sm text-gray-600">Precio: <span class="font-medium text-green-600">$<?php echo htmlspecialchars(number_format((float)$producto['Precio'], 2)); ?></span></p>
                        <div class="flex justify-between items-center mt-1">
                            <?php
                                $estadoClase = '';
                                $estadoTexto = htmlspecialchars($producto['Estado']);
                                switch (strtolower($estadoTexto)) {
                                    case 'pendiente': $estadoClase = 'bg-yellow-100 text-yellow-700'; break;
                                    case 'aprobado': $estadoClase = 'bg-green-100 text-green-700'; break;
                                    case 'rechazado': $estadoClase = 'bg-red-100 text-red-700'; break;
                                    default: $estadoClase = 'bg-gray-100 text-gray-700'; break;
                                }
                            ?>
                            <p class="status <?php echo $estadoClase; ?> text-xs font-semibold px-2 py-1 rounded-full"><?php echo $estadoTexto; ?></p>
                            <button class="details-btn bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs px-3 py-1 rounded-md">Ver Detalles</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-500 text-center py-10">No tienes productos o solicitudes que coincidan con los filtros.</p>
        <?php endif; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnFiltrosProdPend = document.getElementById('btn-aplicar-filtros-prodpend');
    if (btnFiltrosProdPend) {
        btnFiltrosProdPend.addEventListener('click', function() {
            const q_prodpend = document.getElementById('search-bar-prodpend').value;
            const est_prodpend = document.getElementById('filter-status-prodpend').value;

            let queryString = `&q_prodpend=${encodeURIComponent(q_prodpend)}`;
            queryString += `&est_prodpend=${encodeURIComponent(est_prodpend)}`;
            
            if (typeof window.cargarContenidoGlobal === 'function') {
                window.cargarContenidoGlobal('productospend', null, queryString);
            }
        });
    }
});
</script>