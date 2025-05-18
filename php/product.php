<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Producto</title>
</head>

<body class="bg-orange-100">
    <header>
        <?php include 'navbar.php'; ?>
    </header>

    <div id="main-content" class="flex-1 transition-all duration-300 p-6 bg-orange-100 min-h-screen">
        <div class="flex flex-col md:flex-row gap-6 bg-white p-6 rounded-lg shadow-md">
            <!-- Galería -->
            <div class="flex">
                <!-- Miniaturas -->
                <div class="flex flex-col gap-2 mr-4">
                    <img src="../recursos/categorias/JAULAS.jpg" alt="" class="w-16 h-16 object-cover rounded cursor-pointer border">
                    <img src="../recursos/categorias/JUGUETES.jpg" alt="" class="w-16 h-16 object-cover rounded cursor-pointer border">
                    <img src="../recursos/categorias/ALIMENTOSCAT.jpg" alt="" class="w-16 h-16 object-cover rounded cursor-pointer border">
                </div>
                <!-- Imagen principal -->
                <div class="flex-shrink-0">
                    <img id="imagenGrande" src="../recursos/categorias/JAULAS.jpg" alt="Producto" class="w-96 h-auto rounded">
                </div>
            </div>

            <!-- Detalles -->
            <div class="flex-1 space-y-3">
                <h2 class="text-blue-500 text-lg ">Accesorios para mascotas</h2>
                <p class="text-lg text-gray-700">Publicado por <strong class="text-indigo-600">PetCo</strong></p>
                <p class="text-2xl font-bold text-green-600">$200</p>
                <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded">
                    Añadir a una lista
                </button>
                <button class="bg-[#ffae00] hover:bg-[#ff9d00] text-white font-semibold py-2 px-4 rounded">
                    Añadir al carrito
                </button>
                <p class="text-yellow-500 text-xl">★★★★☆</p>
            </div>
        </div>

        <!-- Comentarios -->
        <section class="mt-10">
            <h3 class="text-xl font-semibold mb-2">Comentarios</h3>
            <textarea class="w-full p-3 border rounded resize-none mb-2" rows="4"
                placeholder="Escribe un comentario..."></textarea>
            <button class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">Enviar</button>
        </section>

        <!-- Más productos del mismo vendedor -->
        <section class="mt-12">
            <h3 class="text-xl font-semibold mb-4">Más productos de este vendedor</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="h-[400px] bg-gray-800 rounded-[10px] p-2 flex-col">
                    <img src="../recursos/productos/6826d7eb20fc7.png" alt="" class="w-full h-[300px] object-cover rounded mb-2">
                    <p class="text-lg text-white font-semibold">Producto 1</p>
                    <span class="text-green-600 font-bold">$100</span>
                </div>
                <div class="h-[400px] bg-gray-800 rounded-[10px] p-2 flex-col">
                    <img src="../recursos/productos/6826d7eb20fc7.png" alt="" class="w-full h-[300px] object-cover rounded mb-2">
                    <p class="text-lg text-white font-semibold">Producto 2</p>
                    <span class="text-green-600 font-bold">$150</span>
                </div>
                <!-- Más productos... -->
            </div>
        </section>


        <script src="../js/script_product.js"></script>
    </div>
</body>

</html>