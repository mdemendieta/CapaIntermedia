<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user= $_POST['usuario'];  // Puede ser el correo o nombre de usuario
    $pass = $_POST['contrasena'];

    include 'conexion.php';

    $stmt = $conn->prepare("CALL sp_IniciarSesion(?)");
    $stmt->bind_param("s", $user);
    
    if ($stmt->execute()) {

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            if ($pass === $user_data['contrasena']) {
                // Guardar datos en la sesión
                $_SESSION['id_usuario'] = $user_data['id_usuario'];
                $_SESSION['nombre'] = $user_data['nombre'];
                $_SESSION['apellido_P'] = $user_data['apellido_P'];
                $_SESSION['apellido_M'] = $user_data['apellido_M'];
                $_SESSION['email'] = $user_data['email'];
                $_SESSION['nombre_usuario'] = $user_data['nombre_usuario'];
                $_SESSION['tipo'] = $user_data['tipo'];
                $_SESSION['avatar'] = $user_data['avatar'];
                $_SESSION['genero'] = $user_data['genero'];
                $_SESSION['fecha_Nacimiento'] = $user_data['fecha_Nacimiento'];

                header("Location: landing.php");
                exit();
            } else {
                echo "Contraseña incorrecta.";
            }
        } else {
            echo "Usuario no encontrado.";
        }
    } else {
        echo "Error al ejecutar el procedimiento almacenado.";
    }

    $stmt->close();
    $conn->close();
}
?>
