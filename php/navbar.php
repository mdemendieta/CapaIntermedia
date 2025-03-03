<?php
    $paginas = [
        'Inicio' => 'index.php',
        'Servicios' => 'servicios.php',
        'Portafolio' => 'portafolio.php',
        'Contacto' => 'contacto.php'
    ];
    $paginaActual = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar Dinámica</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .nav-item {
            position: relative;
            transition: color 0.3s;
        }
        .nav-item::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -2px;
            width: 0;
            height: 5px;
            background-color: #f97316;
            transition: width 0.3s ease, left 0.3s ease;
            border-radius: calc(infinity * 1px);
        }
        .nav-item:hover::after, .active::after {
            width: 100%;
            left: 0;
        }
        .custom-nav {
            background-color: #242F5A;
            padding: 10px;
        }
    </style>
</head>
<body class="bg-gray-100 ">
    
    <nav class="bg shadow-md custom-nav flex items-center pl-4 transition-all duration-300" id="navbar">
        <!-- Botón de menú -->
        <button id="menuToggle" class="text-white bg-orange-500 p-2 rounded ml- 5 mr-5">
            &#9776;
        </button>

        <!-- Logo -->
        <a href="landing.php" class="mr-auto">
            <img src="https://png.pngtree.com/element_our/png/20180926/pets-vector-logo-template-this-cat-and-dog-logo-could-be-png_113815.jpg" alt="Logo" class="h-10">
        </a>

        <?php foreach ($paginas as $nombre => $url): ?>
            <a href="<?php echo $url; ?>" 
            class="nav-item px-4 py-2 text-sky-500 hover:text-orange-500 <?php echo ($paginaActual == $url) ? 'active font-bold text-orange-500' : ''; ?>">
                <?php echo $nombre; ?>
            </a>
        <?php endforeach; ?>

        <!-- Foto de perfil -->
        <div class=" ml-5 mr-5">
            <img src="../recursos/perfilvacio.jpg" alt="Foto de perfil" class="h-10 w-10 rounded-full cursor-pointer" id="profilePic">
        </div>
    </nav>

    <!-- Panel lateral -->
    <div id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 p-4 mr-5">
        <h2 class="text-lg font-bold">Menú</h2>
        <ul>
            <li class="mt-2"><a href="#" class="hover:text-orange-500">Opción 1</a></li>
            <li class="mt-2"><a href="#" class="hover:text-orange-500">Opción 2</a></li>
            <li class="mt-2"><a href="#" class="hover:text-orange-500">Opción 3</a></li>
        </ul>
    </div>

    <script>
        document.getElementById("menuToggle").addEventListener("click", function() {
            let sidebar = document.getElementById("sidebar");
            let navbar = document.getElementById("navbar");
            let body = document.getElementById("main-content");
            if (sidebar.classList.contains("-translate-x-full")) {
                sidebar.classList.remove("-translate-x-full");
                navbar.classList.add("ml-64");
                body.classList.add("ml-64");
            } else {
                sidebar.classList.add("-translate-x-full");
                navbar.classList.remove("ml-64");
                body.classList.remove("ml-64");
            }
        });
    </script>
</body>
</html>
