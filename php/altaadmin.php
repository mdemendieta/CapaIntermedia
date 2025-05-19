<?php
session_start();
require_once '../modelos/conexion.php';

$message = '';
$message_type = ''; // 'success' o 'error'

// Verificar si el usuario en sesión es Superadministrador
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'Superadministrador') {
  
    $message = "Acceso denegado. Se requiere ser Superadministrador.";
    $message_type = 'error';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message_type !== 'error') {
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $email = trim($_POST['email']);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    $tipo_usuario = 'Administrador'; // El nuevo usuario será Administrador
    $estado = 'Activo';

    // --- VALIDACIONES ---
    $errors = [];

    // Validar que el nombre de usuario no esté vacío y longitud
    if (empty($nombre_usuario)) {
        $errors[] = "El nombre de usuario es obligatorio.";
    } elseif (strlen($nombre_usuario) < 3) {
        $errors[] = "El nombre de usuario debe tener al menos 3 caracteres.";
    }

    // Validar que el email no esté vacío y sea un formato válido
    if (empty($email)) {
        $errors[] = "El correo electrónico es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del correo electrónico no es válido.";
    }

    // Validar que las contraseñas coincidan
    if ($contrasena !== $confirmar_contrasena) {
        $errors[] = "Las contraseñas no coinciden.";
    }

    // Validar seguridad de la contraseña
    if (empty($contrasena)) {
        $errors[] = "La contraseña es obligatoria.";
    } else {
        if (strlen($contrasena) < 8) {
            $errors[] = "La contraseña debe tener mínimo 8 caracteres.";
        }
        if (!preg_match('/[A-Z]/', $contrasena)) {
            $errors[] = "La contraseña debe incluir al menos una letra mayúscula.";
        }
        if (!preg_match('/[a-z]/', $contrasena)) {
            $errors[] = "La contraseña debe incluir al menos una letra minúscula.";
        }
        if (!preg_match('/[0-9]/', $contrasena)) {
            $errors[] = "La contraseña debe incluir al menos un número.";
        }
        if (!preg_match('/[\W_]/', $contrasena)) { // \W es cualquier no alfanumérico, _ es underscore
            $errors[] = "La contraseña debe incluir al menos un carácter especial.";
        }
    }

    if (empty($errors)) {
        $db = new Database();
        $conexion = $db->getConexion();

        // Verificar si el nombre de usuario o email ya existen
        $stmt_check = $conexion->prepare("SELECT id_usuario FROM Usuario WHERE nombre_usuario = ? OR email = ?");
        $stmt_check->bind_param("ss", $nombre_usuario, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $message = "Error: El nombre de usuario o el correo electrónico ya están registrados.";
            $message_type = 'error';
        } else {
            // Hashear la contraseña
            $contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);


            $stmt = $conexion->prepare(
                "INSERT INTO Usuario (nombre_usuario, email, contrasena, tipo, estado, nombre, apellido_P, apellido_M, fecha_Nacimiento, genero) 
                 VALUES (?, ?, ?, ?, ?, NULL, NULL, NULL, NULL, NULL)"
            );

            $stmt->bind_param("sssss", $nombre_usuario, $email, $contrasena_hashed, $tipo_usuario, $estado);

            if ($stmt->execute()) {
                $message = "¡Nuevo administrador registrado exitosamente!";
                $message_type = 'success';
            } else {
                $message = "Error al registrar el administrador: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
        $stmt_check->close();
        $conexion->close();
    } else {
        $message = implode("<br>", $errors);
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta Administrador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
            font-weight: bold;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body class="bg-orange-100 flex flex-col items-center justify-center min-h-screen">
    <?php if (isset($_SESSION['id_usuario']) && $_SESSION['tipo'] === 'Superadministrador'): ?>
        <a id="logoutLink" href="../modelos/logout.php" class="absolute top-5 right-5 bg-orange-200 justify-center my-5 rounded-[30px] p-3 hover:bg-orange-300">Cerrar Sesión</a>
    <?php endif; ?>

    <div class="bg-blue-950 rounded-[30px] flex items-center justify-center flex-col p-8 md:p-12 shadow-2xl w-full max-w-lg">
        <h2 class="text-2xl text-orange-500 font-bold mb-6">Nuevo Usuario Administrador</h2>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?> w-full mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'Superadministrador'): ?>
            <?php if (empty($message) || $message_type !== 'error' || $message !== "Acceso denegado. Se requiere ser Superadministrador."): // Evitar doble mensaje de acceso denegado ?>
                <div class="message error w-full mb-4">
                    Acceso denegado. Debes ser Super-Administrador para registrar nuevos administradores.
                    Si eres Super-Administrador, <a href="../php/navbar.php" class="underline hover:text-orange-300">inicia sesión</a>. </div>
            <?php endif; ?>
        <?php else: ?>
            <form action="altaadmin.php" method="POST" class="w-full space-y-4">
                <div>
                    <label for="nombre_usuario" class="block text-sm font-medium text-orange-300 mb-1">Nombre de Usuario</label>
                    <input type="text" name="nombre_usuario" id="nombre_usuario" placeholder="Ej: admin01" class="w-full p-3 border rounded-full bg-gray-800 text-white focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-500" required>
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-orange-300 mb-1">Correo Electrónico</label>
                    <input type="email" name="email" id="email" placeholder="admin@ejemplo.com" class="w-full p-3 border rounded-full bg-gray-800 text-white focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-500" required>
                </div>
                <div>
                    <label for="contrasena" class="block text-sm font-medium text-orange-300 mb-1">Contraseña</label>
                    <input type="password" name="contrasena" id="contrasena" placeholder="********" class="w-full p-3 border rounded-full bg-gray-800 text-white focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-500" required>
                </div>
                <div>
                    <label for="confirmar_contrasena" class="block text-sm font-medium text-orange-300 mb-1">Confirmar Contraseña</label>
                    <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" placeholder="********" class="w-full p-3 border rounded-full bg-gray-800 text-white focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-500" required>
                </div>
                <button type="submit" class="w-full bg-orange-500 text-white py-3 rounded-full hover:bg-orange-600 transition-colors duration-300 font-semibold text-lg">
                    Autorizar Administrador
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>