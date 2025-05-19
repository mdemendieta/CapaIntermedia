// ... dentro de navbar.php ...
$avatar = (isset($_SESSION['avatar']) && $_SESSION['avatar'] !== null && file_exists('../recursos/usuarios/' . $_SESSION['avatar']))
    ? '../recursos/usuarios/' . $_SESSION['avatar'] // Ruta desde vistas/
    : '../recursos/perfilvacio.jpg'; // Ruta desde vistas/

// Enlaces a páginas PHP (si siguen en la carpeta php/)
// 'Inicio' => '../php/landing.php',
// 'Chat' => '../php/chat.php',

// Si el logo está en recursos:
// <img src="../recursos/logo.jpg" alt="Logo" class="h-10">

// El script de login.js:
// <script src="../js/login.js"></script>

// El logout:
// <a id="logoutLink" href="../modelos/logout.php" class="block hover:text-orange-500">Cerrar Sesión</a>

// El action del form de registro:
// <form action="../controladores/RegisterController.php" id="registerForm" class="hidden" method="post">