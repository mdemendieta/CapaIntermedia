<?php
class RegisterModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function ValidarUsuario($nombre_usuario, $email)
    {
        $stmt = $this->conexion->prepare("CALL sp_ValidarUsuarioCorreo(?, ?)");
        $stmt->bind_param("ss", $nombre_usuario, $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $existe = $resultado->num_rows > 0;
        $stmt->close();
        $this->conexion->next_result();
        return $existe;
    }

    public function registrarUsuario($nombre, $apellido_P, $apellido_M, $nombre_usuario, $email, $contrasena, $genero, $fecha_Nacimiento, $tipo)
    {
        if ($this->ValidarUsuario($nombre_usuario, $email)) {
            return ['success' => false, 'mensaje' => 'El usuario o correo ya existen.'];
        }

        $contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);

        $stmt = $this->conexion->prepare("CALL sp_RegistrarUsuario(?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sssssssss",
            $nombre,
            $apellido_P,
            $apellido_M,
            $nombre_usuario,
            $email,
            $contrasena_hashed,
            $genero,
            $fecha_Nacimiento,
            $tipo
        );

        if ($stmt->execute()) {
            // Obtener el último ID insertado 
            $result = $this->conexion->query("SELECT LAST_INSERT_ID() AS id_usuario;");
            $id_usuario = $result->fetch_assoc()['id_usuario'];

            return [
            'success' => true,
            'id_usuario' => $id_usuario,
            'mensaje' => 'Usuario registrado correctamente'
        ];
        } else {
            return ['success' => false, 'mensaje' => 'Error al registrar: ' . $stmt->error];
        }
    }
}

