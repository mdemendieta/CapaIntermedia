    <!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Pedidos</title>
    <link rel="stylesheet" href="../css/styles_orders.css">
</head>


<body class="flex flex-col">

<div id="main-content" class="flex justify-center transition-all duration-300 p-6 bg-orange-100">

    <div class="orders-container">
        <!-- Barra de búsqueda y filtros -->
        <div class="filters">   
            <div class="row-filter">
                <input type="text" id="search-bar" placeholder="Buscar pedido...">
                <label for="filter-category" style="width:18%;">Categoria:</label>
                <select id="filter-category" style="width: 40%;">
                    <!---Opciones deben llenarse dinamicamente -->
                    <option value="all">Todas</option>
                    <option value="Alimentos">Alimentos</option>
                    <option value="Juguetes">Juguetes</option>
                    <option value="Accesorios">Accesorios</option>
                </select>
            </div>
            <div class="row-filter">
                <select id="filter-status">
                    <option value="all">Todos</option>
                    <option value="Entregado">Entregado</option>
                    <option value="Transportado">Transportado</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
                <label for="filter-date-start">Fecha de pedido:</label>
                <input type="date" id="filter-date-start">
                <label for="filter-date-end">Fecha de entregado:</label>
                <input type="date" id="filter-date-end">
            </div>
        </div>

        <!-- Lista de pedidos -->
        <div id="orders-list" class="orders-list">
            <!-- Los pedidos se generarán dinámicamente aquí -->
        </div>
    </div>
</div>
    <script src="../js/script_orders.js"></script>
</body>

</html>