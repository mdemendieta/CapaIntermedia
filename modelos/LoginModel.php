<?php
class LoginModel {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function autenticarUsuario($usuario) {
        $stmt = $this->conexion->prepare("CALL sp_IniciarSesion(?)");
        $stmt->bind_param("s", $usuario);

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                return [
                    'success' => true,
                    'usuario' => $result->fetch_assoc()
                ];
            } else {
                return ['success' => false, 'error' => 'Usuario no encontrado.'];
            }
        } else {
            return ['success' => false, 'error' => 'Error al ejecutar el procedimiento.'];
        }
    }
}

?>
