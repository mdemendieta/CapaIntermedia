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
    
    <div id="main-content" class="flex transition-all duration-300 p-6 bg-orange-100 min-h-screen justify-center ml-64">

    <div id="AltaProductoForm" class="grid grid-cols-2 flex-col items-center justify-center w-[70%]">
                
                <h2 class="col-span-2 text-lg text-center font-bold mb-4">Crear una Nueva Publicación</h2>
                <div>
                <div class="relative flex items-center justify-center grid grid-cols-2 gap-4">
                <img src="../recursos/iconos/agregarfoto.png" alt="Foto de prodoucto" class="bg-orange-500 h-[150px] w-[150px] round-l" id="fotoproducto">
                <img src="../recursos/iconos/agregarfoto.png" alt="Foto de prodoucto" class="bg-orange-500 h-[150px] w-[150px] round-l" id="fotoproducto">
                <img src="../recursos/iconos/agregarfoto.png" alt="Foto de prodoucto" class="bg-orange-500 h-[150px] w-[150px] round-l" id="fotoproducto">
                <img src="../recursos/iconos/agregarfoto.png" alt="Foto de prodoucto" class="bg-orange-500 h-[150px] w-[150px] round-l" id="fotoproducto">
                    
                </div>
                </div>

                <div>
                <input type="text" placeholder="Titulo" class="w-full p-2 mb-2 border rounded">
                <input type="text" placeholder="Descripcion" class="w-full p-2 mb-2 border rounded">
                <input type="text" placeholder="Precio" class="w-full p-2 mb-2 border rounded">
                <input type="text" placeholder="Stock" class="w-full p-2 mb-2 border rounded">
                <input type="text" placeholder="Categoria(s)" class="w-full p-2 mb-2 border rounded">
                <div class="mb-2">
                    <label>Tipo de publicación:</label>
                    <input type="radio" name="tipo" value="cotización"> Cotización
                    <input type="radio" name="tipo" value="venta"> Venta
                </div>
                <input type="date" placeholder="Fecha de nacimiento" class="w-full p-2 mb-2 border rounded">
                </div>

                <button class="w-full bg-green-500 text-white py-2 rounded col-start-2">Publicar</button>
                
            </div>
    </div>

    

</body>
</html>
