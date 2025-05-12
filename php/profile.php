<?php
session_start();

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
                    <h2 class="justify-left"><?php echo $_SESSION['nombre'] ?? 'Usuario'; ?></h2>
                    <p class="text-v1">Se unió el: 15 de febrero de 2023</p>
                    <span class="role seller"><?php echo $_SESSION['rol'] ?? 'Comprador'; ?></span>
                    <span class="text-v1">Público</span>
                    <button onclick="window.location.href='chat.php'" class="btn-v2 pl-4 pr-4 ml-20">Mensaje</button>
                </div>
            </div>
               
            <div class="flex space-x-4 items-start mb-4">
            <?php
            $activo = $_GET['seccion'] ?? 'listas';
            function claseBoton($nombre, $activo) {
                if ($nombre === $activo) {
                    return "bg-white text-orange-500 px-6 py-4 rounded-full transition";
                } else {
                    return "bg-blue-950 text-white px-6 py-4 rounded-full border-4 border-orange-500 hover:bg-orange-500 transition";
                }
            }
            ?>

            <div class="flex gap-4 mb-2">
                <button class="<?= claseBoton('listas', $activo) ?>" onclick="cargarContenido('listas')">Listas</button>
                <button class="<?= claseBoton('historial', $activo) ?>" onclick="cargarContenido('historial')">Historial de pedidos</button>
                <button class="<?= claseBoton('productospubli', $activo) ?>" onclick="cargarContenido('productospubli')">Productos Publicados</button>
                <button class="<?= claseBoton('productospend', $activo) ?>" onclick="cargarContenido('productospend')">Solicitudes de Publicaciones</button>
                <button class="<?= claseBoton('reportes', $activo) ?>" onclick="cargarContenido('reportes')">Ventas</button>
            </div>



                        </div>

                        <div id="contenedor" class="flex justify-center h-64">
                <!-- Aquí se carga el contenido -->
                        </div>

                    </div>


     </div>


            <script type="module" src="../js/script_profile.js"></script>
            <script>
        // Cargar contenido por AJAX
        function cargarContenido(seccion) {
            let archivo = '';

            switch(seccion) {
                case 'listas': archivo = 'mislistas.php'; break;
                case 'productospubli': archivo = 'listaproductos.php'; break;
                case 'historial': archivo = 'orders.php'; break;
                case 'productospend': archivo = 'myproducts.php'; break;
                case 'reportes': archivo = 'orders.php'; break;
            }

            fetch(archivo)
                .then(res => res.text())
                .then(data => {
                    document.getElementById('contenedor').innerHTML = data;
                    actualizarBotones(seccion);
                });
        }

        // Cambiar estilos visuales de los botones al hacer clic
        function actualizarBotones(seccionActiva) {
            const botones = document.querySelectorAll('button');
            botones.forEach(btn => {
                if (btn.textContent.trim().toLowerCase().includes(seccionActiva)) {
                    btn.className = "bg-white text-orange-500 px-6 py-4 rounded-full transition";
                } else {
                    btn.className = "bg-blue-950 text-white px-6 py-4 rounded-full border-4 border-orange-500 hover:bg-orange-500 transition";
                }
            });
        }

// Cargar por default
window.addEventListener('DOMContentLoaded', () => {
    cargarContenido('<?= $activo ?>');
});
</script>
</body>

</html>