<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css">
</head>

<body class="bg-gray-100">

    <header>
        <?php include('navbar.php'); ?>
    </header>

    <div id="main-content" class="flex-1 transition-all duration-300 p-6 bg-orange-100 ml-64">

        <div class="mt-10 p-4 bg-white shadow-md rounded-lg mb-6 z-30">
            <h2 class="text-xl font-bold mb-4">Filtrar Productos</h2>
            
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label for="search" class="block text-gray-700 font-medium mb-1">Buscar:</label>
                    <input type="text" id="search" name="search" class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label for="price-filter" class="block text-gray-700 font-medium mb-1">Rango de Precio:</label>
                    <select id="price-filter" name="price-filter" class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Selecciona un rango</option>
                        <option value="0-50">$0 - $50</option>
                        <option value="50-100">$50 - $100</option>
                        <option value="100-200">$100 - $200</option>
                        <option value="200+">$200+</option>
                    </select>
                </div>

                <div>
                    <label for="category-filter" class="block text-gray-700 font-medium mb-1">Categorías:</label>
                    <select id="category-filter" name="category-filter[]" multiple class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="electronica">Electrónica</option>
                        <option value="ropa">Ropa</option>
                        <option value="hogar">Hogar</option>
                        <option value="juguetes">Juguetes</option>
                        <option value="deportes">Deportes</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-5">
            <?php
            include('conexion.php'); // Asegúrate de tener la conexión a la base de datos

            $id_usuario = $_SESSION['id_usuario']; // Obtener ID del usuario

            // Consultar los productos disponibles
            $consultaProductos = "SELECT * FROM Producto WHERE Estado = 'Aprobado'";
            $resultadoProductos = mysqli_query($conexion, $consultaProductos);

            // Mostrar los productos
            if (mysqli_num_rows($resultadoProductos) > 0) {
                while ($producto = mysqli_fetch_assoc($resultadoProductos)) {
                    echo '<div class="h-[400px] bg-gray-800 rounded-[10px] p-2 flex-col">';
                    
                    // Carrusel con SwiperJS
                    echo '<div class="h-[300px] w-full bg-orange-500 rounded-[8px] overflow-hidden">
                            <div class="swiper mySwiper h-full">
                                <div class="swiper-wrapper">';
                    
                    // Mostrar imágenes dinámicamente
                    $rutaImagenes = "../recursos/productos/";
                    $imagenes = array_diff(scandir($rutaImagenes), array('.', '..'));
                    foreach ($imagenes as $imagen) {
                        echo '<div class="swiper-slide flex items-center justify-center">
                                <img src="'.$rutaImagenes.$imagen.'" class="h-full object-cover w-full rounded-[8px]" alt="Producto">
                              </div>';
                    }

                    echo '</div> 
                            <div class="swiper-pagination"></div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-button-next"></div>
                          </div>
                        </div>'; 

                    // TÍTULO y PRECIO
                    echo '<div class="p-4 text-white">';
                    echo '<h2 class="text-lg font-bold">'.$producto['Nombre'].'</h2>';
                    echo '<p class="text-md">$'.$producto['Precio'].'</p>';
                    echo '</div>';

                    // Formulario para añadir el producto a una lista
                    $consultaListas = "SELECT * FROM ListaUsuario WHERE id_usuario = $id_usuario";
                    $resultadoListas = mysqli_query($conexion, $consultaListas);
                    if (mysqli_num_rows($resultadoListas) > 0) {
                        echo '<form action="agregar_a_lista.php" method="POST" class="mt-4">';
                        echo '<input type="hidden" name="id_producto" value="'.$producto['id_producto'].'">';
                        echo '<select name="id_lista" class="w-full p-2 mb-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">';
                        while ($lista = mysqli_fetch_assoc($resultadoListas)) {
                            echo '<option value="'.$lista['id_lista'].'">'.$lista['NombreLista'].'</option>';
                        }
                        echo '</select>';
                        echo '<button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600">Añadir a la Lista</button>';
                        echo '</form>';
                    } else {
                        echo '<p>No tienes listas para añadir productos.</p>';
                    }

                    echo '</div>'; // Fin tarjeta
                }
            } else {
                echo '<p>No hay productos disponibles.</p>';
            }
            ?>
        </div>
    </div>

    <!-- SwiperJS Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
    <script>
        var swiper = new Swiper(".mySwiper", {
            loop: true,
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
        });
    </script>

</body>
</html>
