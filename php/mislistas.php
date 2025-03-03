<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SwiperJS CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css">
</head>
<header>
    <?php include('navbar.php'); ?>
</header>
<body class="bg-gray-100">
    
    <div id="main-content" class="flex-1 transition-all duration-300 ml-0 p-6 bg-orange-100">

        <div class=" sticky top-0 mt-10 p-4 bg-white shadow-md rounded-lg mb-6 z-30">
            <h2 class="text-xl font-bold mb-4">Filtrar Productos</h2>
            
            <div class="grid grid-cols-3 gap-4">
                <!-- Barra de búsqueda -->
                <div>
                    <label for="search" class="block text-gray-700 font-medium mb-1">Buscar:</label>
                    <input type="text" id="search" name="search" class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <!-- Filtro de precio -->
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

                <!-- Filtro de categorías -->
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
            $rutaImagenes = "../recursos/productos/";
            $imagenes = array_diff(scandir($rutaImagenes), array('.', '..'));

            for ($fila = 0; $fila < 4; $fila++) {
                for ($columna = 0; $columna < 4; $columna++) {
                    echo '<div class="h-[400px] bg-gray-800 rounded-[10px] p-2 flex-col">';
            
                    // Carrusel con SwiperJS
                    echo '<div class="h-[300px] w-full bg-orange-500 rounded-[8px]  overflow-hidden">
                            <div class="swiper mySwiper h-full">
                                <div class="swiper-wrapper">';
            
                    // Mostrar imágenes dinámicamente
                    foreach ($imagenes as $imagen) {
                        echo '<div class="swiper-slide flex items-center justify-center">
                                <img src="'.$rutaImagenes.$imagen.'" class="h-full object-cover w-full rounded-[8px]" alt="Producto">
                              </div>';
                    }
            
                    echo '</div> 
                            <!-- Paginación y navegación -->
                            <div class="swiper-pagination"></div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-button-next"></div>
                          </div>
                        </div>'; // Fin carrusel
            
                    // Nuevo div para TÍTULO y PRECIO
                    echo '<div class="p-4 text-white">';
                    echo '<h2 class="text-lg font-bold">TÍTULO</h2>';
                    echo '<p class="text-md">PRECIO</p>';
                    echo '</div>'; // Fin texto
            
                    echo '</div>'; // Fin tarjeta
                }
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
