<?php
require_once '../modelos/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir datos
    $nombre = $_POST['nombre'];
    $apellido_P = $_POST['apellido_P'];
    $apellido_M = $_POST['apellido_M'];
    $nombre_usuario = $_POST['nombre_usuario'];
    $email = $_POST['email'];
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    $genero = $_POST['genero'];
    $fecha_Nacimiento = $_POST['fecha_Nacimiento'];
    $tipo = $_POST['rol'];

    // --- VALIDACIONES PERSONALIZADAS ---
// Validar que el nombre no contenga números
    if (preg_match('/[0-9]/', $nombre)) {
        echo "Error: El nombre no debe contener números.";
        exit();
    }
    //Validar que el nombre no contenga caracteres especiales
    if (preg_match('/[^a-zA-Z\s]/', $nombre)) {
        echo "Error: El nombre no debe contener caracteres especiales.";
        exit();
    }
    // Validar que el apellido no contenga números
    if (preg_match('/[0-9]/', $apellido_P) || preg_match('/[0-9]/', $apellido_M)) {
        echo "Error: El apellido no debe contener números.";
        exit();
    }
    // Validar que el apellido no contenga caracteres especiales
    if (preg_match('/[^a-zA-Z\s]/', $apellido_P) || preg_match('/[^a-zA-Z\s]/', $apellido_M)) {
        echo "Error: El apellido no debe contener caracteres especiales.";
        exit();
    }

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

    require_once '../modelos/RegisterModel.php';

    // Conexión y modelo
    $db = new Database();
    $conexion = $db->getConexion();
    $registro = new RegisterModel($conexion);

    $resultado = $registro->registrarUsuario(
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

    if ($resultado['success']) {
        // Guardar en sesión
        session_start();
        $_SESSION['id_usuario'] = $resultado['id_usuario'];
        $_SESSION['nombre'] = $nombre;
        $_SESSION['apellido_P'] = $apellido_P;
        $_SESSION['apellido_M'] = $apellido_M;
        $_SESSION['email'] = $email;
        $_SESSION['nombre_usuario'] = $nombre_usuario;
        $_SESSION['tipo'] = $tipo;
        $_SESSION['genero'] = $genero;
        $_SESSION['fecha_Nacimiento'] = $fecha_Nacimiento;

        header("Location: ../php/landing.php");
        exit;
    } else {
        echo $resultado['mensaje'];
    }
}
