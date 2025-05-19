<?php
session_start();
require_once '../modelos/conexion.php';
require_once '../modelos/RegisterModel.php'; // Usaremos el modelo de registro

// Verificar si el usuario es Superadministrador
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'Superadministrador') {
    // Redirigir si no es Superadmin o no está logueado
    header("Location: ../php/landing.php"); // O a una página de error/acceso denegado
    exit();
}

$mensaje = '';
$mensaje_tipo = ''; // 'success' o 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'], $_POST['contrasena'], $_POST['nombre_usuario'], $_POST['nombre'], $_POST['apellido_P'], $_POST['apellido_M'], $_POST['genero'], $_POST['fecha_Nacimiento'])) {
        $email = $_POST['email'];
        $contrasena = $_POST['contrasena'];
        // Campos adicionales para un registro completo
        $nombre_usuario = $_POST['nombre_usuario'];
        $nombre = $_POST['nombre'];
        $apellido_P = $_POST['apellido_P'];
        $apellido_M = $_POST['apellido_M'];
        $genero = $_POST['genero'];
        $fecha_Nacimiento = $_POST['fecha_Nacimiento'];
        $tipo_rol_a_crear = 'Administrador'; // Rol fijo

        // Validaciones básicas (puedes expandirlas como en RegisterController)
        if (empty($email) || empty($contrasena) || empty($nombre_usuario) || empty($nombre) || empty($apellido_P)) {
            $mensaje = "Todos los campos marcados con * son obligatorios.";
            $mensaje_tipo = 'error';
        } elseif (strlen($contrasena) < 8) {
            $mensaje = "La contraseña debe tener al menos 8 caracteres.";
            $mensaje_tipo = 'error';
        } else {
            $db = new Database();
            $conexion = $db->getConexion();
            $registroModel = new RegisterModel($conexion);

            // Verificar si el usuario o email ya existen
            if ($registroModel->ValidarUsuario($nombre_usuario, $email)) {
                $mensaje = 'El nombre de usuario o correo electrónico ya están registrados.';
                $mensaje_tipo = 'error';
            } else {
                // Proceder con el registro
                // La contraseña se hashea dentro del modelo
                $resultadoRegistro = $registroModel->registrarUsuario(
                    $nombre,
                    $apellido_P,
                    $apellido_M,
                    $nombre_usuario,
                    $email,
                    $contrasena,
                    $genero,
                    $fecha_Nacimiento,
                    $tipo_rol_a_crear // Se registra como Administrador
                );

                if ($resultadoRegistro['success']) {
                    $mensaje = "Administrador '" . htmlspecialchars($nombre_usuario) . "' creado exitosamente.";
                    $mensaje_tipo = 'success';
                } else {
                    $mensaje = "Error al crear administrador: " . $resultadoRegistro['mensaje'];
                    $mensaje_tipo = 'error';
                }
            }
        }
    } else {
        $mensaje = "Formulario incompleto.";
        $mensaje_tipo = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta de Administradores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .mensaje { padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; }
        .mensaje.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body class="bg-orange-100 flex flex-col items-center justify-center min-h-screen">
    <?php include 'navbar.php'; // O un navbar específico para superadmin ?>
    
    <div class="container mx-auto p-4 mt-5">
        <div class="w-full max-w-2xl mx-auto bg-blue-950 rounded-[30px] p-8 shadow-xl">
            <h2 class="text-2xl text-orange-500 font-bold mb-6 text-center">Nuevo Usuario Administrador</h2>

            <?php if (!empty($mensaje)): ?>
                <div class="mensaje <?php echo $mensaje_tipo; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="altaadmin.php" class="space-y-4">
                <div>
                    <label for="nombre" class="block text-sm font-medium text-orange-300">Nombre(s)*</label>
                    <input type="text" name="nombre" id="nombre" required class="mt-1 block w-full p-2 border border-gray-600 bg-blue-900 text-white rounded-full focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="apellido_P" class="block text-sm font-medium text-orange-300">Apellido Paterno*</label>
                        <input type="text" name="apellido_P" id="apellido_P" required class="mt-1 block w-full p-2 border border-gray-600 bg-blue-900 text-white rounded-full focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label for="apellido_M" class="block text-sm font-medium text-orange-300">Apellido Materno</label>
                        <input type="text" name="apellido_M" id="apellido_M" class="mt-1 block w-full p-2 border border-gray-600 bg-blue-900 text-white rounded-full focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>
                 <div>
                    <label for="nombre_usuario" class="block text-sm font-medium text-orange-300">Nombre de Usuario*</label>
                    <input type="text" name="nombre_usuario" id="nombre_usuario" required class="mt-1 block w-full p-2 border border-gray-600 bg-blue-900 text-white rounded-full focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-orange-300">Correo Electrónico*</label>
                    <input type="email" name="email" id="email" required class="mt-1 block w-full p-2 border border-gray-600 bg-blue-900 text-white rounded-full focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                    <label for="contrasena" class="block text-sm font-medium text-orange-300">Contraseña*</label>
                    <input type="password" name="contrasena" id="contrasena" required class="mt-1 block w-full p-2 border border-gray-600 bg-blue-900 text-white rounded-full focus:ring-orange-500 focus:border-orange-500" minlength="8">
                    <p class="text-xs text-gray-400 mt-1">Mínimo 8 caracteres.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="fecha_Nacimiento" class="block text-sm font-medium text-orange-300">Fecha de Nacimiento*</label>
                        <input type="date" name="fecha_Nacimiento" id="fecha_Nacimiento" required class="mt-1 block w-full p-2 border border-gray-600 bg-blue-900 text-white rounded-full focus:ring-orange-500 focus:border-orange-500" max="2007-01-01" min="1925-01-01">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-orange-300">Género*</label>
                        <select name="genero" id="genero" required class="mt-1 block w-full p-2 border border-gray-600 bg-blue-900 text-white rounded-full focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Seleccione...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-full transition duration-150 ease-in-out">
                    Crear Administrador
                </button>
            </form>
        </div>
    </div>
</body>
</html>