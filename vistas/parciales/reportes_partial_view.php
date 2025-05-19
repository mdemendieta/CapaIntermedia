<?php
// vistas/parciales/reportes_partial_view.php
// Var: $resumenVentas
// $_GET: mes_reporte
?>
<div class="reportes-container-partial p-4 bg-gray-100 rounded-lg shadow" style="width:100%;">
    <h2 class="text-2xl font-bold mb-6 text-gray-700">Resumen de Ventas</h2>

    <div class="filters mb-6 p-4 bg-white rounded-md shadow-sm">
        <label for="filter-mes-reporte" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Mes y Año:</label>
        <div class="flex items-center gap-2">
            <input type="month" id="filter-mes-reporte" name="mes_reporte" class="p-2 border border-gray-300 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500" value="<?php echo htmlspecialchars($_GET['mes_reporte'] ?? date('Y-m')); ?>">
            <button id="btn-aplicar-filtros-reporte" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded">
                Ver Reporte
            </button>
        </div>
    </div>

    <?php if (!empty($resumenVentas)): ?>
        <div class="overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="min-w-full">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Mes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Producto</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Unidades Vendidas</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Ingresos Totales</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($resumenVentas as $venta): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars(date("M Y", strtotime($venta['mes_venta']."-01"))); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($venta['nombre_producto']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 text-center"><?php echo htmlspecialchars($venta['total_unidades']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 text-right">$<?php echo htmlspecialchars(number_format((float)$venta['total_ingresos'], 2)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-gray-500 text-center py-10">No hay datos de ventas para el período seleccionado.</p>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnFiltrosReporte = document.getElementById('btn-aplicar-filtros-reporte');
    if (btnFiltrosReporte) {
        btnFiltrosReporte.addEventListener('click', function() {
            const mes_reporte = document.getElementById('filter-mes-reporte').value;
            let queryString = `&mes_reporte=${encodeURIComponent(mes_reporte)}`;
            
            if (typeof window.cargarContenidoGlobal === 'function') {
                window.cargarContenidoGlobal('reportes', null, queryString);
            }
        });
    }
});
</script>