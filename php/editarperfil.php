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
    
    <div id="main-content" class="flex transition-all duration-300 p-6 bg-orange-100 min-h-screen justify-center">

    <div id="EditarPerfilForm" class="flex flex-col items-center w-[70%]">
                <h2 class="text-lg font-bold mb-4">Modificar Información Personal</h2>
                <div class="relative flex items-center justify-center">
                    <img src="../recursos/perfilvacio.jpg" alt="Foto de perfil" class="h-[150px] w-[150px] rounded-full mb-4" id="profilePic">
                    <img src="../recursos/iconos/editar.png" class="absolute w-16 h-16 bg-red-500 rounded-full bottom-0 right-0 transform translate-x-1/2 -translate-y-1/2 cursor-pointer">
                </div>
                
                <input type="text" placeholder="Nombre" class="w-full p-2 mb-2 border rounded">
                <input type="text" placeholder="Apellidos" class="w-full p-2 mb-2 border rounded">
                <input type="text" placeholder="Nombre de usuario" class="w-full p-2 mb-2 border rounded">
                <input type="email" placeholder="Correo" class="w-full p-2 mb-2 border rounded">
                <input type="password" placeholder="Contraseña" class="w-full p-2 mb-2 border rounded">
                <div class="mb-2">
                    <label>Género:</label>
                    <input type="radio" name="genero" value="masculino"> Masculino
                    <input type="radio" name="genero" value="femenino"> Femenino
                </div>
                <input type="date" placeholder="Fecha de nacimiento" class="w-full p-2 mb-2 border rounded">
                
                <button class="w-full bg-green-500 text-white py-2 rounded">Actualizar Datos</button>
                
            </div>
    </div>

    

</body>
</html>
