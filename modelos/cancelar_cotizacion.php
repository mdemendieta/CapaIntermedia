<?php
require_once 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);
$id_cotizacion = $data['id_cotizacion'];


$db = new Database();
$conexion = $db->getConexion();
$stmt = $conexion->prepare("UPDATE Cotizacion SET estado = 'Rechazado' WHERE id_cotizacion = ?");
$stmt->bind_param("i", $id_cotizacion);

$response = [];
if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['success'] = false;
}
echo json_encode($response);
?>
