<?php
//  código php aquí 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles_profile.css">
    <title>X titulo</title>
    
</head>

<header>
    <?php include('navbar.php'); ?>
</header>

<body>
    <div id="main-content" class="flex justify-center transition-all duration-300 p-6 min-h-screen bg-orange-100">

        <div class="w-full justify-center">
            <div class="card-header w-full bg-gradient-to-r from-orange-100 from-1% via-gray-50 via-20% to-orange-100 to-90% mb-5">
                
                <div class="section-left relative flex items-center justify-center">
                    <img src="../recursos/productos/huron.jpg" alt="Foto de perfil" class="profile-img">
                    <a href="editarperfil.php">
                        <img src="../recursos/iconos/editar.png" class="absolute w-8 h-8 bg-red-500 rounded-full top-4 right-16 transform  cursor-pointer z-30">
                    </a>
                </div>
                <div class="section-middle">
                    <h2 class="username"><?php echo $_SESSION['nombre'] ?? 'Usuario'; ?></h2>
                    <p class="text-v1">Se unió el: 15 de febrero de 2023</p>
                    <span class="role seller"><?php echo $_SESSION['rol'] ?? 'Comprador'; ?></span>
                    <span class="text-v1">Público</span>
                    <button onclick="window.location.href='chat.php'" class="btn-v2 pl-4 pr-4 ml-20">Mensaje</button>
                </div>
            </div>
            <div class="flex space-x-4 items-start">
                <button class="bg-blue-950  text-white px-6 py-2 rounded-full border-4 border-orange-500 hover:bg-blue-600 transition">Listas</button>
                <button class="bg-orange-500  text-white px-6 py-2 rounded-full border-4 border-orange-500 hover:bg-green-600 transition">Historial de pedidos</button>
            </div>
        </div>


     </div>
    <script type="module" src="../js/script_profile.js"></script>
</body>

</html>