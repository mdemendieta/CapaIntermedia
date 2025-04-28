<?php
//Registro
if ($_POST) {
    // Recibir datos del formulario
    $nombre = $_POST['nombre']; // Campo "Nombre(s)"
    $apellido_P = $_POST['apellido_P']; // Campo "Apellido Paterno"
    $apellido_M = $_POST['apellido_M']; // Campo "Apellido Materno"
    $nombre_usuario = $_POST['nombre_usuario']; // Campo "Nombre de usuario"
    $email = $_POST['email']; // Campo "Correo"
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    $genero = $_POST['genero']; // Campo "Genero"
    $fecha_Nacimiento = $_POST['fecha_Nacimiento']; // Campo "Fecha de nacimiento"
    $tipo = $_POST['rol']; // Campo "Rol"

    // --- VALIDACIONES PERSONALIZADAS ---

    // Validar longitud del nombre de usuario
    if (strlen($nombre_usuario) < 3) {
        echo "Error: El nombre de usuario debe tener al menos 3 caracteres.";
        exit();
    }
    //  Validar que las contraseñas sean iguales
    if ($contrasena !== $confirmar_contrasena) {
        echo "Error: Las contraseñas no coinciden.";
        exit();
    }

    // Validar seguridad de la contraseña
    if (
        strlen($contrasena) < 8 ||                      // Mínimo 8 caracteres
        !preg_match('/[0-9]/', $contrasena) ||            // Al menos un número
        !preg_match('/[\W_]/', $contrasena)               // Al menos un carácter especial (no letras ni números)
    ) {
        echo "Error: La contraseña debe tener mínimo 8 caracteres, incluir al menos un número y un carácter especial.";
        exit();
    }
    // Validar que contenga al menos una minúscula
    if (!preg_match('/[a-z]/', $_POST['contrasena'])) {
        die('La contraseña debe incluir al menos una letra minúscula.');
    }

    // Validar que contenga al menos una mayúscula
    if (!preg_match('/[A-Z]/', $_POST['contrasena'])) {
        die('La contraseña debe incluir al menos una letra mayúscula.');
    }
    // Verificar si el nombre de usuario o correo ya existen
    include('conexion.php');
    $stmt = $conn->prepare("CALL sp_ValidarUsuarioCorreo(?, ?)");
    $stmt->bind_param("ss", $nombre_usuario, $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        echo "Error: El nombre de usuario o correo electrónico ya están en uso.";
        exit();
    }
    $stmt->close();
    $conn->next_result(); // Importante para limpiar el buffer de resultados anteriores.

    // Cifrar contraseña
    $contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("CALL sp_RegistrarUsuario(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "sssssssss",
        $nombre,
        $apellido_P,
        $apellido_M,
        $nombre_usuario,
        $email,
        $contrasena,
        $genero,
        $fecha_Nacimiento,
        $tipo
    );

    if ($stmt->execute()) {
        // Registro exitoso: crear sesión
        session_start();
        $_SESSION['nombre_usuario'] = $nombre_usuario;
        $_SESSION['email'] = $email;
        $_SESSION['rol'] = $tipo;

        // Redireccionar a landing.php
        header("Location: landing.php");
        exit();
    } else {
        echo "Error al registrar usuario: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();// Cerrar la conexión
}
?>