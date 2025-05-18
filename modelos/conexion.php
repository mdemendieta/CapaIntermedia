
<?php
class Database {
    private $host = "localhost";
    private $usuario = "root";
    private $contrasena = "";
    private $bd = "bd_capaInter"; 
    private $puerto = 33065; // Default 3306
    public $conexion;

    public function __construct() {
        $this->conexion = new mysqli($this->host, $this->usuario, $this->contrasena,  $this->bd, $this->puerto);

        if ($this->conexion->connect_error) {
            die("Conexión fallida: " . $this->conexion->connect_error);
        }
    }

    public function getConexion() {
        return $this->conexion;
    }
}
