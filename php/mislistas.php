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
    
    <div id="main-content" class="flex-1 transition-all duration-300 p-6 bg-orange-100 ml-64">

                    
            
        <!-- Barra de búsqueda -->
        
        <input type="text" id="search" name="search" placeholder="Buscar una lista por su nombre..." class="w-full mb-10 p-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-orange-500">
        
        

        <div class="grid grid-cols-4 gap-5">
            <?php
            $rutaImagenes = "../recursos/productos/";
            $imagenes = array_diff(scandir($rutaImagenes), array('.', '..'));

            // Se obtienen las primeras 4 imágenes
            $imagenesLimitadas = array_slice($imagenes, 0, 4);
            for ($fila = 0; $fila < 4; $fila++) {
                for ($columna = 0; $columna < 4; $columna++) {
                    // Cada tarjeta mostrará 4 imágenes
                    
                        echo '<div class="h-[360px] bg-gray-800 rounded-[10px] p-2 flex-col">';
                        
                        // Crear un grid 2x2 para las imágenes
                        echo '<div class="grid grid-cols-2 gap-2 h-300">';

                        // Mostrar las 4 imágenes dentro del grid
                        for ($j = 0; $j < 4; $j++) {
                            // Se repiten las imágenes, pero puedes cambiar la lógica si es necesario
                            echo '<div class="h-[150px] bg-orange-500 rounded-[8px] overflow-hidden">';
                            echo '<img src="'.$rutaImagenes.$imagenesLimitadas[$j].'" class="h-full object-cover w-full rounded-[8px]" alt="Producto">';
                            echo '</div>';
                        }

                        echo '</div>'; // Fin del grid 2x2
                        
                        // Título de la tarjeta
                        echo '<div class="mt-[10px] text-center text-white">';
                        echo '<h2 class="text-lg font-bold">TÍTULO</h2>';
                        echo '</div>';
                        echo '</div>'; // Fin de la tarjeta
                    
                }
            }
            ?>
        </div>
    </div>

    

</body>
</html>
