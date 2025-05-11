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

        <div class="profile-card">
            <div class="card-header">
                <div class="section-left">
                    <img src="../recursos/productos/huron.jpg" alt="Foto de perfil" class="profile-img">
                </div>
                <div class="section-middle">
                    <h2 class="username"><?php echo $_SESSION['nombre'] ?? 'Usuario'; ?></h2>
                    <p class="text-v1">Se unió el: 15 de febrero de 2023</p>
                    <span class="role seller"><?php echo $_SESSION['rol'] ?? 'Comprador'; ?></span>
                    <span class="text-v1">Público</span>
                </div>
                <div class="section-right">
                    <button onclick="window.location.href='chat.php'" class="btn-v2">Mensaje</button>
                </div>
            </div>
            <hr>
            <div class="section-lists">
                <label for="id-profile">Lista</label>
                <select id="list-profile">
                    <!---Opciones deben llenarse dinamicamente -->
                    <option value="Favoritos">Favoritos</option>
                    <option value="Alimentos">Alimentos</option>
                </select>
                <div id="product-container">
                    <!-- Aquí se genera la tabla de whishlist-->
                </div>
            </div>
        </div>
     </div>
    <script type="module" src="../js/script_profile.js"></script>
</body>

</html>