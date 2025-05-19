<?php
// modelos/ProfileModel.php
require_once 'conexion.php';

class ProfileModel {
    private $conexion;

    public function __construct() {
        $db = new Database();
        $this->conexion = $db->getConexion();
    }

    public function getUserProfileData($id_usuario) {
        $userData = [
            'nombre_completo' => 'Usuario Invitado',
            'nombre_usuario' => 'N/A',
            'tipo_usuario' => 'Cliente',
            'avatar' => '../recursos/perfilvacio.jpg', // Ruta por defecto
            'fecha_union' => 'Fecha Desconocida'
        ];

        // Intentar obtener datos de la sesión primero como fallback o complemento
        if (isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == $id_usuario) {
            $userData['nombre_completo'] = trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido_P'] ?? '') . ' ' . ($_SESSION['apellido_M'] ?? ''));
            $userData['nombre_usuario'] = $_SESSION['nombre_usuario'] ?? 'N/A';
            $userData['tipo_usuario'] = $_SESSION['tipo'] ?? 'Cliente';
            if (isset($_SESSION['avatar']) && $_SESSION['avatar'] !== null && file_exists('../recursos/usuarios/' . $_SESSION['avatar'])) {
                $userData['avatar'] = '../recursos/usuarios/' . $_SESSION['avatar'];
            } elseif (file_exists('../recursos/perfilvacio.jpg')) {
                 $userData['avatar'] = '../recursos/perfilvacio.jpg';
            } else {
                 $userData['avatar'] = '../recursos/placeholder.png'; // Un placeholder genérico si nada más existe
            }
        }
        
        // Consulta a la base de datos para datos más precisos o adicionales
        $stmt = $this->conexion->prepare("SELECT nombre, apellido_P, apellido_M, nombre_usuario, tipo, avatar, fecha_Registro FROM Usuario WHERE id_usuario = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($dbUser = $result->fetch_assoc()) {
                $userData['nombre_completo'] = trim(($dbUser['nombre'] ?? '') . ' ' . ($dbUser['apellido_P'] ?? '') . ' ' . ($dbUser['apellido_M'] ?? ''));
                $userData['nombre_usuario'] = $dbUser['nombre_usuario'] ?? $userData['nombre_usuario'];
                $userData['tipo_usuario'] = $dbUser['tipo'] ?? $userData['tipo_usuario'];
                if (!empty($dbUser['avatar']) && file_exists('../recursos/usuarios/' . $dbUser['avatar'])) {
                    $userData['avatar'] = '../recursos/usuarios/' . $dbUser['avatar'];
                }
                if (!empty($dbUser['fecha_Registro'])) {
                     // Formatear fecha_Registro a un formato legible
                    $date = new DateTime($dbUser['fecha_Registro']);
                    $formatter = new IntlDateFormatter('es_ES', IntlDateFormatter::LONG, IntlDateFormatter::NONE); // Formato largo para fecha en español
                    $userData['fecha_union'] = $formatter->format($date);
                    if (!$userData['fecha_union']) { // Fallback si Intl no está disponible o falla
                        $userData['fecha_union'] = date("d \d\e F \d\e Y", strtotime($dbUser['fecha_Registro']));
                    }
                }
            }
            $stmt->close();
        }
        return $userData;
    }
}
?>