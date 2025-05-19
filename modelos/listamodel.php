<?php
// modelos/ListaModel.php
// No necesita require_once 'conexion.php' si el controlador que lo instancia ya la cargó y le pasa la conexión.

class ListaModel {
    private $conexion;

    public function __construct($dbConexion) { // Recibe la conexión
        $this->conexion = $dbConexion;
    }

    public function getListasUsuario($id_usuario) {
        $listas = [];
        $stmt = $this->conexion->prepare("SELECT id_lista, NombreLista, Descripcion, Publica FROM ListaUsuario WHERE id_usuario = ? ORDER BY NombreLista ASC");
        if ($stmt) {
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            while ($lista = $resultado->fetch_assoc()) {
                $listas[] = $lista;
            }
            $stmt->close();
        }
        return $listas;
    }

    public function crearLista($id_usuario, $nombreLista, $descripcion, $publica) {
        $stmt = $this->conexion->prepare("INSERT INTO ListaUsuario (id_usuario, NombreLista, Descripcion, Publica) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $is_publica_int = $publica ? 1 : 0;
            $stmt->bind_param("issi", $id_usuario, $nombreLista, $descripcion, $is_publica_int);
            if ($stmt->execute()) {
                $id_lista = $stmt->insert_id;
                $stmt->close();
                
                // Devolver la lista recién creada para la respuesta AJAX
                $nuevaListaStmt = $this->conexion->prepare("SELECT id_lista, NombreLista, Descripcion, Publica FROM ListaUsuario WHERE id_lista = ?");
                if ($nuevaListaStmt) {
                    $nuevaListaStmt->bind_param("i", $id_lista);
                    $nuevaListaStmt->execute();
                    $resultado = $nuevaListaStmt->get_result()->fetch_assoc();
                    $nuevaListaStmt->close();
                    return ['success' => true, 'lista' => $resultado];
                }
                return ['success' => true, 'id_lista' => $id_lista, 'mensaje' => 'Lista creada, pero no se pudo recuperar.'];

            } else {
                $error = $stmt->error;
                $stmt->close();
                return ['success' => false, 'mensaje' => 'Error al crear la lista: ' . $error];
            }
        }
        return ['success' => false, 'mensaje' => 'Error al preparar la consulta para crear lista.'];
    }
}
?>