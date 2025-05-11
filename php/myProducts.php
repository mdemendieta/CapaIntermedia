<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles_myProducts.css">
</head>

<header>
    <?php include('navbar.php'); ?>
</header>

<body>
<div id="main-content" class="flex-1 transition-all duration-300 p-6 bg-orange-100">
    <div class="my-container">
        <!-- Barra de búsqueda y filtros -->
        <div class="filters">   
            <div class="row-filter">
                <input type="text" id="search-bar" placeholder="Buscar pedido...">
                <label for="filter-status" style="width:18%;">Estado:</label>
                <select id="filter-status" style="width: 40%;">
                    <!---Opciones deben llenarse dinamicamente -->
                    <option value="all">Todos</option>
                    <option value="Aceptado">Aceptado</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Rechazado">Rechazado</option>
                </select>
            </div>
        </div>
        <!-- Lista de pedidos -->
        <div id="myProduct-list" class="myProduct-list">
            <!-- Los productos se generarán dinámicamente aquí -->
        </div>
    </div>

    <script src="../js/script_myProducts.js"></script>
</div>
</body>

</html>