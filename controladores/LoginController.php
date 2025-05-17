<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    require_once '../modelos/conexion.php';
    require_once '../modelos/LoginModel.php';

    $db = new Database();
    $conexion = $db->getConexion();
    $modelo = new LoginModel($conexion);
    $resultado = $modelo->autenticarUsuario($usuario);

    if ($resultado['success']) {
        $usuario_data = $resultado['usuario'];
        $hashed = $usuario_data['contrasena'];

        if (password_verify($contrasena, $hashed)) {
            // Guardar en sesión
            $_SESSION['id_usuario'] = $usuario_data['id_usuario'];
            $_SESSION['nombre'] = $usuario_data['nombre'];
            $_SESSION['apellido_P'] = $usuario_data['apellido_P'];
            $_SESSION['apellido_M'] = $usuario_data['apellido_M'];
            $_SESSION['email'] = $usuario_data['email'];
            $_SESSION['nombre_usuario'] = $usuario_data['nombre_usuario'];
            $_SESSION['tipo'] = $usuario_data['tipo'];
            $_SESSION['avatar'] = $usuario_data['avatar'];
            $_SESSION['genero'] = $usuario_data['genero'];
            $_SESSION['fecha_Nacimiento'] = $usuario_data['fecha_Nacimiento'];

            $redirect = ($usuario_data['tipo'] === 'Superadministrador') ? '../php/altaadmin.php' : '../php/landing.php';
            echo json_encode([
                'success' => true,
                'redirect' => $redirect,
                'usuario' => [
                    'nombre' => $usuario_data['nombre'],
                    'tipo' => $usuario_data['tipo']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => $resultado['error']]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
}
