<?php
// Incluido por profile.php. $current_profile_owner_id es el id_usuario del vendedor logueado.
$current_profile_owner_id = $_SESSION['profile_owner_id_for_section'] ?? null;

if (!($_SESSION['is_own_profile_for_section'] ?? false) || !$current_profile_owner_id) {
    echo "<p class='text-red-500 text-center'>Acceso denegado a esta sección.</p>";
    return;
}
// Verificar que el tipo de usuario sea Vendedor
$db_check_type = new Database(); $conn_check_type = $db_check_type->getConexion();
$stmt_check_type = $conn_check_type->prepare("SELECT tipo FROM Usuario WHERE id_usuario = ?");
$is_vendedor = false;
if($stmt_check_type){
    $stmt_check_type->bind_param("i", $current_profile_owner_id);
    $stmt_check_type->execute();
    $res_check_type = $stmt_check_type->get_result();
    if($row_check = $res_check_type->fetch_assoc()){
        if($row_check['tipo'] === 'Vendedor') $is_vendedor = true;
    }
    $stmt_check_type->close();
}
$conn_check_type->close();
if(!$is_vendedor){
     echo "<p class='text-orange-600 text-center'>Esta sección es solo para Vendedores.</p>";
    return;
}


$db_vrv = new Database();
$conexion_vrv = $db_vrv->getConexion();

// Obtener categorías para el filtro
$categorias_filtro = [];
$queryCat = "SELECT id_categoria, NombreCategoria FROM Categoria ORDER BY NombreCategoria ASC";
$resultCat = $conexion_vrv->query($queryCat);
if ($resultCat) {
    while($rowCat = $resultCat->fetch_assoc()){
        $categorias_filtro[] = $rowCat;
    }
}

// Variables para los datos del reporte
$ventas_reporte = [];
$tipo_despliegue = $_POST['tipo_despliegue'] ?? 'detallada'; // 'detallada' o 'agrupada'
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';
$categoria_seleccionada = $_POST['categoria_id'] ?? '';

$sql_base = "
    SELECT 
        v.FechaHoraVenta,
        c.NombreCategoria,
        p.Nombre AS NombreProducto,
        p.Valoracion AS CalificacionProducto,
        v.PrecioTotal AS PrecioVenta,
        p.Inventario AS StockActual,
        MONTH(v.FechaHoraVenta) AS MesVenta,
        YEAR(v.FechaHoraVenta) AS AnioVenta
    FROM Venta v
    JOIN Producto p ON v.id_producto = p.id_producto
    JOIN Categoria c ON p.id_categoria = c.id_categoria
    WHERE v.id_vendedor = ?
";
$params = [$current_profile_owner_id];
$types = "i";

if (!empty($fecha_inicio)) {
    $sql_base .= " AND DATE(v.FechaHoraVenta) >= ?";
    $params[] = $fecha_inicio;
    $types .= "s";
}
if (!empty($fecha_fin)) {
    $sql_base .= " AND DATE(v.FechaHoraVenta) <= ?";
    $params[] = $fecha_fin;
    $types .= "s";
}
if (!empty($categoria_seleccionada)) {
    $sql_base .= " AND p.id_categoria = ?";
    $params[] = $categoria_seleccionada;
    $types .= "i";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_reporte'])) {
    $stmt_reporte = $conexion_vrv->prepare($sql_base . " ORDER BY v.FechaHoraVenta DESC");
    if ($stmt_reporte) {
        $stmt_reporte->bind_param($types, ...$params);
        $stmt_reporte->execute();
        $result_reporte = $stmt_reporte->get_result();
        while ($row_rep = $result_reporte->fetch_assoc()) {
            $ventas_reporte[] = $row_rep;
        }
        $stmt_reporte->close();
    }
}

$conexion_vrv->close();
?>
<div class="w-full">
    <h2 class="text-2xl font-bold text-gray-700 mb-6 text-center">Reporte de Mis Ventas</h2>

    <form method="POST" action="#reporte" class="p-6 bg-white rounded-lg shadow-md mb-8 space-y-4">
        <input type="hidden" name="generar_reporte" value="1">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Desde:</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" class="mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
            </div>
            <div>
                <label for="fecha_fin" class="block text-sm font-medium text-gray-700">Hasta:</label>
                <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" class="mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
            </div>
            <div>
                <label for="categoria_id" class="block text-sm font-medium text-gray-700">Categoría:</label>
                <select name="categoria_id" id="categoria_id" class="mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                    <option value="">Todas</option>
                    <?php foreach($categorias_filtro as $cat): ?>
                        <option value="<?php echo $cat['id_categoria']; ?>" <?php if($categoria_seleccionada == $cat['id_categoria']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['NombreCategoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Método de Despliegue:</label>
            <div class="mt-2 space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="tipo_despliegue" value="detallada" class="form-radio text-orange-600" <?php if($tipo_despliegue === 'detallada') echo 'checked'; ?>>
                    <span class="ml-2">Consulta Detallada</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="tipo_despliegue" value="agrupada" class="form-radio text-orange-600" <?php if($tipo_despliegue === 'agrupada') echo 'checked'; ?>>
                    <span class="ml-2">Consulta Agrupada</span>
                </label>
            </div>
        </div>
        <div class="text-center">
            <button type="submit" class="bg-orange-500 hover:bg-orange-700 text-white font-bold py-2 px-6 rounded-lg transition">
                Generar Reporte
            </button>
        </div>
    </form>

    <div id="reporte" class="bg-white p-6 rounded-lg shadow-md">
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_reporte'])): ?>
            <?php if (!empty($ventas_reporte)): ?>
                <?php if ($tipo_despliegue === 'detallada'): ?>
                    <h3 class="text-xl font-semibold mb-4">Reporte Detallado de Ventas</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha y Hora</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calificación</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Venta</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Actual</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($ventas_reporte as $venta): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars((new DateTime($venta['FechaHoraVenta']))->format('Y-m-d H:i:s')); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($venta['NombreCategoria']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($venta['NombreProducto']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars(number_format($venta['CalificacionProducto'],1)); ?> ★</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo htmlspecialchars(number_format($venta['PrecioVenta'],2)); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($venta['StockActual']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($tipo_despliegue === 'agrupada'): 
                    // Procesar para agrupar:
                    $ventas_agrupadas = [];
                    foreach($ventas_reporte as $venta){
                        $mes_anio = $venta['AnioVenta'] . '-' . str_pad($venta['MesVenta'], 2, '0', STR_PAD_LEFT);
                        $categoria = $venta['NombreCategoria'];
                        if(!isset($ventas_agrupadas[$mes_anio][$categoria])){
                            $ventas_agrupadas[$mes_anio][$categoria] = ['total_ventas_dinero' => 0, 'cantidad_ventas' => 0];
                        }
                        $ventas_agrupadas[$mes_anio][$categoria]['total_ventas_dinero'] += $venta['PrecioVenta'];
                        $ventas_agrupadas[$mes_anio][$categoria]['cantidad_ventas']++;
                    }
                    ksort($ventas_agrupadas); // Ordenar por mes-año
                ?>
                    <h3 class="text-xl font-semibold mb-4">Reporte Agrupado de Ventas</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mes-Año</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Vendido ($)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"># Ventas</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($ventas_agrupadas as $mes_anio_key => $categorias_data): ?>
                                    <?php foreach($categorias_data as $cat_nombre => $data_venta): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($mes_anio_key); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($cat_nombre); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo htmlspecialchars(number_format($data_venta['total_ventas_dinero'],2)); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($data_venta['cantidad_ventas']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center">No se encontraron ventas para los filtros seleccionados.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-gray-500 text-center">Selecciona los filtros y presiona "Generar Reporte" para ver los resultados.</p>
        <?php endif; ?>
    </div>
</div>