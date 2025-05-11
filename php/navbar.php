<?php
//session_start(); // Iniciar la sesión para acceder a las variables de sesión
$paginas = [
    'Inicio' => 'landing.php',
    'Mis Listas' => 'mislistas.php',
    'Carrito' => 'carrito.php',
    'Chat' => 'chat.php',
    'historial de pedidos' => 'orders.php',
    'crear admin' => 'altaadmin.php',
    'productos pendientes por aprobar' => 'myproducts.php',
    'Publicar un producto' => 'altaproducto.php',
    'Mi perfil' => 'profile.php',
    'Editar' => 'editarperfil.php',

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
            border-radius: 9999px;
        }

        .nav-item:hover::after,
        .active::after {
            width: 100%;
            left: 0;
        }

        .custom-nav {
            background-color: #242F5A;
            padding: 10px;
        }
    </style>
</head>

<body class="bg-gray-100">

    <nav class=" ml-64 bg shadow-md custom-nav flex items-center pl-4 transition-all duration-300 z-30" id="navbar">
        <button id="menuToggle" class="text-white bg-orange-500 p-2 rounded ml-5 mr-5">&#9776;</button>

        <a href="landing.php" class="mr-auto">
            <img src="https://png.pngtree.com/element_our/png/20180926/pets-vector-logo-template-this-cat-and-dog-logo-could-be-png_113815.jpg"
                alt="Logo" class="h-10">
        </a>

        <?php foreach ($paginas as $nombre => $url): ?>
            <a href="<?php echo $url; ?>"
                class="nav-item px-4 py-2 text-sky-500 hover:text-orange-500 <?php echo ($paginaActual == $url) ? 'active font-bold text-orange-500' : ''; ?>">
                <?php echo $nombre; ?>
            </a>
        <?php endforeach; ?>



        <div class="ml-5 mr-5">
            <img src="../recursos/perfilvacio.jpg" alt="Foto de perfil" class="h-10 w-10 rounded-full cursor-pointer"
                id="profilePic">
        </div>
    </nav>

    <div id="sidebar"
        class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white transition-transform duration-300 p-4">
        <h2 class="text-lg font-bold">Menú</h2>
        <ul>
            <li class="mt-2"><a href="#" class="hover:text-orange-500">Opción 1</a></li>
            <li class="mt-2"><a href="#" class="hover:text-orange-500">Opción 2</a></li>
            <li class="mt-2"><a href="logout.php" class="hover:text-orange-500"><?php if(isset($_SESSION['nombre'])){ echo "Cerrar Sesión";}else{ echo "Opción 3";}?></a></li>
        </ul>
    </div>

    <div id="authModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">

            <button id="closeModal" class="ml-[300px] text-orange-500 hover:text-gray-700 text-4xl">&times;</button>

            <form action="login.php" id="loginForm" method="post">
                <h2 class="text-lg font-bold mb-4">Iniciar Sesión</h2>
                <input name="usuario" type="text" placeholder="Usuario/Correo" class="w-full p-2 mb-2 border rounded">
                <input name="contrasena" type="password" placeholder="Contraseña" class="w-full p-2 mb-4 border rounded">
                <button class="w-full bg-orange-500 text-white py-2 rounded">Ingresar</button>
                <p class="mt-2 text-sm">¿No tienes cuenta? <a href="#" id="showRegister"
                        class="text-blue-500">Regístrate</a></p>
            </form>

            <form action="register.php" id="registerForm" class="hidden" method="post">
                <h2 class="text-lg font-bold mb-4">Registro</h2>
                <input id="nombre" name="nombre" type="text" placeholder="Nombre(s)"
                    class="w-full p-2 mb-2 border rounded" required>
                <input id="apellido_P" name="apellido_P" type="text" placeholder="Apellido paterno"
                    class="w-full p-2 mb-2 border rounded" required>
                <input id="apellido_M" name="apellido_M" type="text" placeholder="Apellido materno"
                    class="w-full p-2 mb-2 border rounded" required>
                <input id="nombre_usuario" name="nombre_usuario" type="text" placeholder="Nombre de usuario"
                    class="w-full p-2 mb-2 border rounded" required>
                <input id="email" name="email" type="email" placeholder="Correo" class="w-full p-2 mb-2 border rounded"
                    required>
                <input id="contrasena" name="contrasena" type="password" placeholder="Contraseña"
                    class="w-full p-2 mb-2 border rounded" required>
                <input id="contrasena2" name="confirmar_contrasena" type="password" placeholder="Confirmar Contraseña"
                    class="w-full p-2 mb-2 border rounded" required>
                <div class="mb-2">
                    <label>Género:</label>
                    <input id="genero1" name="genero" type="radio" value="masculino" required> Masculino
                    <input id="genero2" name="genero" type="radio" value="femenino" required> Femenino
                </div>
                <label for="fecha_Nacimiento">Fecha de nacimiento:</label>
                <input id="fecha_Nacimiento" name="fecha_Nacimiento" type="date" placeholder="Fecha de nacimiento"
                    class="w-full p-2 mb-2 border rounded" value="2003-01-01" max="2007-01-01" min="1925-01-01" required>
                <div class="mb-2">
                    <label>Rol:</label>
                    <input id="tipo1" name="rol" type="radio" value="comprador" required> Comprador
                    <input id="tipo2" name="rol" type="radio" value="vendedor" required> Vendedor
                </div>
                <button class="w-full bg-green-500 text-white py-2 rounded">Registrarse</button>
                <p class="mt-2 text-sm">¿Ya tienes cuenta? <a href="#" id="showLogin" class="text-blue-500">Inicia
                        sesión</a></p>
            </form>
        </div>
    </div>

    <script>
        document.getElementById("menuToggle").addEventListener("click", function () {
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

        document.getElementById("profilePic").addEventListener("click", function () {
            document.getElementById("authModal").classList.remove("hidden");
            document.body.classList.add("overflow-hidden");
        });

        document.getElementById("closeModal").addEventListener("click", function () {
            document.getElementById("authModal").classList.add("hidden");
            document.body.classList.remove("overflow-hidden");
        });

        document.getElementById("showRegister").addEventListener("click", function () {
            document.getElementById("loginForm").classList.add("hidden");
            document.getElementById("registerForm").classList.remove("hidden");
        });

        document.getElementById("showLogin").addEventListener("click", function () {
            document.getElementById("registerForm").classList.add("hidden");
            document.getElementById("loginForm").classList.remove("hidden");
        });

        document.getElementById('registerForm').addEventListener('submit', function (e) {
            // Evitar que el formulario se envíe de inmediato (feedback rápido)
            e.preventDefault();

            // Capturar datos
            const nombreUsuario = document.getElementById('nombre_usuario').value.trim();
            const contrasena = document.getElementById('contrasena').value;
            const confirmarContrasena = document.getElementById('contrasena2').value;

            // --- VALIDACIONES ---

            // Nombre de usuario mínimo 3 caracteres
            if (nombreUsuario.length < 3) {
                alert('El nombre de usuario debe tener al menos 3 caracteres.');
                return;
            }

            // Contraseñas iguales
            if (contrasena !== confirmarContrasena) {
                alert('Las contraseñas no coinciden.');
                return;
            }

            // Contraseña mínimo 8 caracteres
            if (contrasena.length < 8) {
                alert('La contraseña debe tener al menos 8 caracteres.');
                return;
            }

            // Contraseña debe contener al menos un número
            if (!/\d/.test(contrasena)) {
                alert('La contraseña debe incluir al menos un número.');
                return;
            }

            // Contraseña debe contener al menos un carácter especial
            if (!/[!@#$%^&*(),.?":{}|<>_\-]/.test(contrasena)) {
                alert('La contraseña debe incluir al menos un carácter especial.');
                return;
            }

            if (!/[a-z]/.test(contrasena)) {
                alert('La contraseña debe incluir al menos una letra minúscula.');
                return;
            }

            if (!/[A-Z]/.test(contrasena)) {
                alert('La contraseña debe incluir al menos una letra mayúscula.');
                return;
            }
            // Si pasa todas las validaciones, enviar el formulario
            this.submit();
        });

    </script>
</body>

</html>