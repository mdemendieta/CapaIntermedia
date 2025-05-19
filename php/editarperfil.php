<?php
session_start();
require_once '../modelos/conexion.php';

// 1. Asegurar que el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php"); // O landing.php con modal de login
    exit();
}

$id_usuario_actual = $_SESSION['id_usuario'];
$usuario_data = null;
$mensaje = '';
$mensaje_tipo = ''; // 'success' o 'error'

$db = new Database();
$conexion = $db->getConexion();

// 2. Obtener datos actuales del usuario para mostrar en el formulario
// Usamos los campos que están en tu tabla Usuario y los que son relevantes para editar
$stmt_get = $conexion->prepare("SELECT nombre, apellido_P, apellido_M, nombre_usuario, email, genero, fecha_Nacimiento, avatar FROM Usuario WHERE id_usuario = ?");
if ($stmt_get) {
    $stmt_get->bind_param("i", $id_usuario_actual);
    $stmt_get->execute();
    $result_get = $stmt_get->get_result();
    if ($result_get->num_rows > 0) {
        $usuario_data = $result_get->fetch_assoc();
    } else {
        // Esto no debería pasar si el id_usuario en sesión es válido
        $mensaje = "Error: No se pudieron cargar los datos del usuario.";
        $mensaje_tipo = 'error';
    }
    $stmt_get->close();
} else {
    die("Error al preparar la consulta para obtener datos del usuario.");
}

// 3. Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    // Recoger datos del formulario
    $nombre = $_POST['nombre'] ?? $usuario_data['nombre'];
    $apellido_P = $_POST['apellido_P'] ?? $usuario_data['apellido_P'];
    $apellido_M = $_POST['apellido_M'] ?? $usuario_data['apellido_M']; // Puede ser opcional
    $nombre_usuario = $_POST['nombre_usuario'] ?? $usuario_data['nombre_usuario'];
    $email = $_POST['email'] ?? $usuario_data['email'];
    $genero = $_POST['genero'] ?? $usuario_data['genero'];
    $fecha_Nacimiento = $_POST['fecha_Nacimiento'] ?? $usuario_data['fecha_Nacimiento'];
    
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

    $avatar_actual = $usuario_data['avatar'];
    $ruta_avatar_subida = $avatar_actual; // Por defecto, mantiene el avatar actual

    // --- VALIDACIONES (similar a RegisterController) ---
    $errores_validacion = [];
    if (preg_match('/[0-9]/', $nombre) || preg_match('/[^a-zA-Z\sÁÉÍÓÚáéíóúñÑ]/u', $nombre)) {
        $errores_validacion[] = "El nombre no debe contener números ni caracteres especiales (excepto espacios y acentos).";
    }
    if (preg_match('/[0-9]/', $apellido_P) || preg_match('/[^a-zA-Z\sÁÉÍÓÚáéíóúñÑ]/u', $apellido_P)) {
        $errores_validacion[] = "El apellido paterno no debe contener números ni caracteres especiales.";
    }
    if (!empty($apellido_M) && (preg_match('/[0-9]/', $apellido_M) || preg_match('/[^a-zA-Z\sÁÉÍÓÚáéíóúñÑ]/u', $apellido_M))) {
        $errores_validacion[] = "El apellido materno no debe contener números ni caracteres especiales.";
    }
    if (strlen($nombre_usuario) < 3) {
        $errores_validacion[] = "El nombre de usuario debe tener al menos 3 caracteres.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores_validacion[] = "El formato del correo electrónico no es válido.";
    }

    // Validar unicidad de email y nombre_usuario (si cambiaron)
    if ($email !== $usuario_data['email']) {
        $stmt_check_email = $conexion->prepare("SELECT id_usuario FROM Usuario WHERE email = ? AND id_usuario != ?");
        $stmt_check_email->bind_param("si", $email, $id_usuario_actual);
        $stmt_check_email->execute();
        if ($stmt_check_email->get_result()->num_rows > 0) {
            $errores_validacion[] = "El nuevo correo electrónico ya está en uso.";
        }
        $stmt_check_email->close();
    }
    if ($nombre_usuario !== $usuario_data['nombre_usuario']) {
        $stmt_check_user = $conexion->prepare("SELECT id_usuario FROM Usuario WHERE nombre_usuario = ? AND id_usuario != ?");
        $stmt_check_user->bind_param("si", $nombre_usuario, $id_usuario_actual);
        $stmt_check_user->execute();
        if ($stmt_check_user->get_result()->num_rows > 0) {
            $errores_validacion[] = "El nuevo nombre de usuario ya está en uso.";
        }
        $stmt_check_user->close();
    }


    // Validar contraseña si se intenta cambiar
    $contrasena_sql_update = ""; // Fragmento SQL para la contraseña
    $params_sql = []; // Parámetros para el bind_param
    $types_sql = "";    // Tipos para el bind_param

    if (!empty($nueva_contrasena)) {
        if ($nueva_contrasena !== $confirmar_contrasena) {
            $errores_validacion[] = "Las nuevas contraseñas no coinciden.";
        } elseif (strlen($nueva_contrasena) < 8 || !preg_match('/[0-9]/', $nueva_contrasena) || !preg_match('/[\W_]/', $nueva_contrasena) || !preg_match('/[a-z]/', $nueva_contrasena) || !preg_match('/[A-Z]/', $nueva_contrasena)) {
            $errores_validacion[] = "La nueva contraseña debe tener mínimo 8 caracteres, incluir mayúscula, minúscula, número y carácter especial.";
        } else {
            $contrasena_hashed = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
            $contrasena_sql_update = ", contrasena = ?";
            $params_sql[] = $contrasena_hashed; // Se añadirá a los parámetros generales después
            $types_sql .= "s"; // Se añadirá a los tipos generales después
        }
    }

    // Manejo de subida de avatar
    if (isset($_FILES['avatar_nuevo']) && $_FILES['avatar_nuevo']['error'] == UPLOAD_ERR_OK) {
        $uploadDirectory = "../recursos/usuarios/";
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }
        $nombreArchivo = basename($_FILES['avatar_nuevo']['name']);
        $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($extension, $permitidas)) {
            if ($_FILES['avatar_nuevo']['size'] <= 2000000) { // Límite de 2MB
                $nuevoNombreArchivo = "avatar_" . $id_usuario_actual . "_" . time() . "." . $extension;
                $rutaCompleta = $uploadDirectory . $nuevoNombreArchivo;
                if (move_uploaded_file($_FILES['avatar_nuevo']['tmp_name'], $rutaCompleta)) {
                    // Si hay un avatar anterior y no es el placeholder, eliminarlo
                    if (!empty($avatar_actual) && file_exists($uploadDirectory . $avatar_actual) && $avatar_actual !== 'perfilvacio.jpg') {
                        unlink($uploadDirectory . $avatar_actual);
                    }
                    $ruta_avatar_subida = $nuevoNombreArchivo; // Guardar solo el nombre del archivo
                } else {
                    $errores_validacion[] = "Error al mover el archivo del avatar.";
                }
            } else {
                $errores_validacion[] = "El archivo del avatar es demasiado grande (máximo 2MB).";
            }
        } else {
            $errores_validacion[] = "Formato de archivo no permitido para el avatar (solo JPG, JPEG, PNG, GIF).";
        }
    }


    if (empty($errores_validacion)) {
        // Construir la consulta SQL
        $sql_update = "UPDATE Usuario SET 
                        nombre = ?, 
                        apellido_P = ?, 
                        apellido_M = ?, 
                        nombre_usuario = ?, 
                        email = ?, 
                        genero = ?, 
                        fecha_Nacimiento = ?,
                        avatar = ? 
                        $contrasena_sql_update 
                       WHERE id_usuario = ?";
        
        // Preparar los parámetros en el orden correcto
        $final_params = [$nombre, $apellido_P, $apellido_M, $nombre_usuario, $email, $genero, $fecha_Nacimiento, $ruta_avatar_subida];
        $final_types = "ssssssss" . $types_sql; // 8 strings iniciales + tipo de contraseña si existe

        // Añadir el hash de la contraseña si se está actualizando
        if (!empty($contrasena_sql_update)) {
             // Este ya está en $params_sql, se une con el array final
        }
        // Unir $params_sql (que contiene la contraseña hasheada si existe) a $final_params
        $final_params = array_merge($final_params, $params_sql);


        $final_params[] = $id_usuario_actual; // El id_usuario para el WHERE
        $final_types .= "i";


        $stmt_update = $conexion->prepare($sql_update);
        if ($stmt_update) {
            // bind_param necesita referencias, no valores directos para el array unpacking
            $stmt_update->bind_param($final_types, ...$final_params);

            if ($stmt_update->execute()) {
                $mensaje = "Perfil actualizado exitosamente.";
                $mensaje_tipo = 'success';
                // Actualizar datos en sesión para reflejar cambios inmediatamente
                $_SESSION['nombre'] = $nombre;
                $_SESSION['apellido_P'] = $apellido_P;
                $_SESSION['apellido_M'] = $apellido_M;
                $_SESSION['nombre_usuario'] = $nombre_usuario;
                $_SESSION['email'] = $email;
                $_SESSION['genero'] = $genero;
                $_SESSION['fecha_Nacimiento'] = $fecha_Nacimiento;
                $_SESSION['avatar'] = $ruta_avatar_subida;

                // Volver a cargar los datos del usuario para el formulario
                $stmt_get_again = $conexion->prepare("SELECT nombre, apellido_P, apellido_M, nombre_usuario, email, genero, fecha_Nacimiento, avatar FROM Usuario WHERE id_usuario = ?");
                $stmt_get_again->bind_param("i", $id_usuario_actual);
                $stmt_get_again->execute();
                $usuario_data = $stmt_get_again->get_result()->fetch_assoc();
                $stmt_get_again->close();

            } else {
                $mensaje = "Error al actualizar el perfil: " . $stmt_update->error;
                $mensaje_tipo = 'error';
            }
            $stmt_update->close();
        } else {
            $mensaje = "Error al preparar la actualización: " . $conexion->error;
            $mensaje_tipo = 'error';
        }
    } else {
        $mensaje = "Por favor corrige los siguientes errores:<br>" . implode("<br>", $errores_validacion);
        $mensaje_tipo = 'error';
    }
}
$conexion->close();

$avatar_mostrado = (isset($usuario_data['avatar']) && !empty($usuario_data['avatar']))
    ? '../recursos/usuarios/' . htmlspecialchars($usuario_data['avatar'])
    : '../recursos/perfilvacio.jpg';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .mensaje { padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; }
        .mensaje.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        #profilePicPreview { max-width: 150px; max-height: 150px; border-radius: 50%; margin-bottom: 1rem; object-fit: cover; border: 3px solid #fdba74; /* orange-300 */ }
        .file-input-label {
            display: inline-block;
            padding: 8px 12px;
            cursor: pointer;
            background-color: #fb923c; /* orange-500 */
            color: white;
            border-radius: 5px;
            font-size: 0.875rem;
        }
        .file-input-label:hover { background-color: #f97316; /* orange-600 */ }
        #avatar_nuevo { display: none; } /* Ocultar el input file por defecto */
    </style>
</head>
<body class="bg-orange-100">
    <?php include('navbar.php'); ?>

    <div id="main-content" class="flex justify-center transition-all duration-300 p-6 min-h-screen">
        <div class="w-full max-w-2xl bg-white p-8 rounded-xl shadow-2xl my-10">
            <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Modificar Información Personal</h2>

            <?php if (!empty($mensaje)): ?>
                <div class="mensaje <?php echo $mensaje_tipo; ?>">
                    <?php echo $mensaje; // Cuidado con HTML aquí si el mensaje viene de $stmt->error ?>
                </div>
            <?php endif; ?>

            <?php if ($usuario_data): ?>
            <form id="EditarPerfilForm" method="POST" action="editarperfil.php" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="actualizar_perfil" value="1">

                <div class="flex flex-col items-center mb-6">
                    <img src="<?php echo $avatar_mostrado; ?>" alt="Foto de perfil actual" id="profilePicPreview">
                    <label for="avatar_nuevo" class="file-input-label">Cambiar foto</label>
                    <input type="file" name="avatar_nuevo" id="avatar_nuevo" accept="image/png, image/jpeg, image/gif" onchange="previewImage(event)">
                    <p class="text-xs text-gray-500 mt-1">Max 2MB. JPG, PNG, GIF.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre(s)*</label>
                        <input type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($usuario_data['nombre']); ?>" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label for="apellido_P" class="block text-sm font-medium text-gray-700">Apellido Paterno*</label>
                        <input type="text" name="apellido_P" id="apellido_P" value="<?php echo htmlspecialchars($usuario_data['apellido_P']); ?>" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>
                <div>
                    <label for="apellido_M" class="block text-sm font-medium text-gray-700">Apellido Materno</label>
                    <input type="text" name="apellido_M" id="apellido_M" value="<?php echo htmlspecialchars($usuario_data['apellido_M']); ?>" class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                    <label for="nombre_usuario" class="block text-sm font-medium text-gray-700">Nombre de Usuario*</label>
                    <input type="text" name="nombre_usuario" id="nombre_usuario" value="<?php echo htmlspecialchars($usuario_data['nombre_usuario']); ?>" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico*</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($usuario_data['email']); ?>" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Género*</label>
                        <select name="genero" id="genero" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                            <option value="Masculino" <?php if($usuario_data['genero'] === 'Masculino') echo 'selected'; ?>>Masculino</option>
                            <option value="Femenino" <?php if($usuario_data['genero'] === 'Femenino') echo 'selected'; ?>>Femenino</option>
                        </select>
                    </div>
                    <div>
                        <label for="fecha_Nacimiento" class="block text-sm font-medium text-gray-700">Fecha de Nacimiento*</label>
                        <input type="date" name="fecha_Nacimiento" id="fecha_Nacimiento" value="<?php echo htmlspecialchars($usuario_data['fecha_Nacimiento']); ?>" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500" max="2007-01-01" min="1925-01-01">
                    </div>
                </div>
                
                <hr class="my-6">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Cambiar Contraseña (opcional)</h3>
                <div>
                    <label for="nueva_contrasena" class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                    <input type="password" name="nueva_contrasena" id="nueva_contrasena" placeholder="Dejar en blanco para no cambiar" class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                    <label for="confirmar_contrasena" class="block text-sm font-medium text-gray-700">Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div class="pt-5">
                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-md transition duration-150 ease-in-out">
                        Actualizar Datos
                    </button>
                </div>
            </form>
            <?php else: ?>
                <?php if (empty($mensaje)): // Mostrar solo si no hay otro mensaje de error ya ?>
                <p class="text-red-500 text-center">No se pudieron cargar los datos del perfil para editar.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById('profilePicPreview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>