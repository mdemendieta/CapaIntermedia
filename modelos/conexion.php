<?php
class Database {
    private $host = "localhost";
    private $usuario = "root";
    private $contrasena = "1234"; // Tu contraseña de DB
    private $bd = "bd_capaInter";
    public $conexion;

    public function __construct() {
        $this->conexion = new mysqli($this->host, $this->usuario, $this->contrasena, $this->bd);

        if ($this->conexion->connect_error) {
            die("Conexión fallida: " . $this->conexion->connect_error);
        }
        // Es buena práctica establecer el charset después de conectar
        if (!$this->conexion->set_charset("utf8mb4")) {
            // printf("Error cargando el conjunto de caracteres utf8mb4: %s\n", $this->conexion->error);
            // Considera registrar este error en lugar de solo imprimirlo en producción
        }
    }

    public function getConexion() {
        return $this->conexion;
    }

    // Es buena idea tener un método para cerrar la conexión, aunque PHP lo hace al final del script.
    public function cerrar() {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }
}
?>