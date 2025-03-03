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
    
    <div id="main-content" class="flex-1 transition-all duration-300 ml-0 p-6 bg-orange-300">
        <div class="grid grid-cols-4 gap-5">
            <?php
            $rutaImagenes = "../recursos/productos/";
            $imagenes = array_diff(scandir($rutaImagenes), array('.', '..'));

            for ($fila = 0; $fila < 4; $fila++) {
                for ($columna = 0; $columna < 4; $columna++) {
                    echo '<div class="h-[400px] bg-gray-800 rounded-[10px] p-3 flex items-center justify-center">';
                    
                    // Carrusel con SwiperJS
                    echo '<div class="h-[300px] w-full bg-orange-500 rounded-[8px] overflow-hidden">
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
