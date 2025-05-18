
<?php
class Database {
    private $host = "localhost";
    private $usuario = "root";
    private $contrasena = "1234";
    private $bd = "bd_capaInter"; // Reemplaza por el nombre real
    public $conexion;

    public function __construct() {
        $this->conexion = new mysqli($this->host, $this->usuario, $this->contrasena,  $this->bd,);

        if ($this->conexion->connect_error) {
            die("Conexión fallida: " . $this->conexion->connect_error);
        }
    }

    public function getConexion() {
        return $this->conexion;
    }
}
