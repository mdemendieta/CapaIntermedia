<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<header>
    <?php include('navbar.php'); ?>
</header>
<body class="bg-gray-100">
    
    <div id="main-content" class="flex-1 transition-all duration-300 p-6 bg-orange-100 min-h-screen">
        
        <!-- Título del carrito -->
        <h1 class="text-2xl font-bold mb-2">Carrito de Compras</h1>
        
        <div>
            <?php
            // Aquí simulo una lista de productos en el carrito (normalmente vendría de una base de datos)
            $productosEnCarrito = [
                ["nombre" => "Producto 1", "precio" => 19.99, "imagen" => "gato1.jpg", "cantidad" => 1],
                ["nombre" => "Producto 2", "precio" => 29.99, "imagen" => "gato2.jpeg", "cantidad" => 2],
                ["nombre" => "Producto 3", "precio" => 9.99, "imagen" => "huron.jpg", "cantidad" => 3],
                ["nombre" => "Producto 4", "precio" => 49.99, "imagen" => "gato1.jpg", "cantidad" => 1]
            ];

            foreach ($productosEnCarrito as $producto) {
                echo '<div class="grid grid-cols-5 gap-5 h-[266px] bg-gray-800 rounded-[10px] p-2 flex-col mt-2 mb-2">';
                
                // Imagen del producto
                echo '<div class="h-[250px] w-[250px] bg-orange-500 rounded-[8px] overflow-hidden">';
                echo '<img src="../recursos/productos/'.$producto['imagen'].'" class="h-full object-cover w-full rounded-[8px]" alt="'.$producto['nombre'].'">';
                echo '</div>';
                
                // Información del producto
                echo '<div class="col-span-3 text-white mt-3 ml-3">';
                echo '<h2 class="text-lg font-bold">'.$producto['nombre'].'</h2>';
                echo '<p class="text-md mb-2">$'.$producto['precio'].' x '.$producto['cantidad'].'</p>';
                
                
                
                echo '</div>'; // Fin texto
                // Botón para eliminar
                echo '<div class=" flex p-[40px] items-center justify-center">';
                echo '<button class="bg-red-500 text-white px-4 py-2 rounded-[8px] ">Eliminar</button>';
                echo '</div>';
                echo '</div>'; // Fin tarjeta
            }
            ?>
        </div>

        <!-- Total -->
        <div class="mt-6 text-right">
            <h2 class="text-xl font-bold">Total: $149.95</h2>
        </div>

        <!-- Botón de Checkout -->
        <div class="mt-4 text-center">
            <button class="bg-green-500 text-white px-6 py-3 rounded-[8px]">Proceder al Pago</button>
        </div>
    </div>

</body>
</html>
