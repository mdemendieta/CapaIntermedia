<?php
session_start();
require_once 'conexion.php'; 

$db = new Database();
$conexion = $db->getConexion();

$id_producto_redirect = null; // Para redirigir incluso si hay error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['id_producto'])) {
        $id_producto_redirect = filter_var($_POST['id_producto'], FILTER_VALIDATE_INT);
    }

    if (!isset($_SESSION['id_usuario'])) {
        // Podrías guardar el mensaje en una variable de sesión para mostrarlo después de redirigir
        $_SESSION['mensaje_valoracion'] = "Debes iniciar sesión para valorar un producto.";
        header("Location: ../php/product.php" . ($id_producto_redirect ? "?id=" . $id_producto_redirect : ""));
        exit;
    }

    $id_usuario = $_SESSION['id_usuario'];
    $id_producto = filter_var($_POST['id_producto'], FILTER_VALIDATE_INT);
    $puntuacion = filter_var($_POST['puntuacion'], FILTER_VALIDATE_INT);

    if (!$id_producto || !$puntuacion || $puntuacion < 1 || $puntuacion > 5) {
        $_SESSION['mensaje_valoracion'] = "Datos de valoración inválidos.";
        header("Location: ../php/product.php?id=" . $id_producto);
        exit;
    }

    // 1. Verificar si el usuario ha comprado el producto (seguridad adicional)
    $puede_valorar_db = false;
    $stmt_venta_check = $conexion->prepare("SELECT COUNT(*) AS total_ventas FROM Venta WHERE id_cliente = ? AND id_producto = ?");
    if ($stmt_venta_check) {
        $stmt_venta_check->bind_param("ii", $id_usuario, $id_producto);
        $stmt_venta_check->execute();
        $result_venta_check = $stmt_venta_check->get_result();
        $venta_info = $result_venta_check->fetch_assoc();
        if ($venta_info && $venta_info['total_ventas'] > 0) {
            $puede_valorar_db = true;
        }
        $stmt_venta_check->close();
    }

    if (!$puede_valorar_db) {
        $_SESSION['mensaje_valoracion'] = "Debes haber comprado este producto para poder valorarlo.";
        header("Location: ../php/product.php?id=" . $id_producto);
        exit;
    }

    // 2. Insertar o actualizar la valoración del usuario en la tabla 'ValoracionesUsuario'
    $stmt_guardar_valoracion = $conexion->prepare(
        "INSERT INTO ValoracionesUsuario (id_producto, id_usuario, puntuacion) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE puntuacion = VALUES(puntuacion), fecha_valoracion = CURRENT_TIMESTAMP"
    );

    if ($stmt_guardar_valoracion) {
        $stmt_guardar_valoracion->bind_param("iii", $id_producto, $id_usuario, $puntuacion);
        if ($stmt_guardar_valoracion->execute()) {
            // 3. Recalcular la valoración promedio del producto
            $stmt_avg = $conexion->prepare("SELECT AVG(puntuacion) AS nueva_valoracion_promedio FROM ValoracionesUsuario WHERE id_producto = ?");
            if ($stmt_avg) {
                $stmt_avg->bind_param("i", $id_producto);
                $stmt_avg->execute();
                $result_avg = $stmt_avg->get_result();
                $avg_data = $result_avg->fetch_assoc();
                $nueva_valoracion_promedio = $avg_data['nueva_valoracion_promedio'] ?? 0; // Si no hay valoraciones, default a 0
                $stmt_avg->close();

                // 4. Actualizar la tabla Producto
                $stmt_update_producto = $conexion->prepare("UPDATE Producto SET Valoracion = ? WHERE id_producto = ?");
                if ($stmt_update_producto) {
                    $stmt_update_producto->bind_param("di", $nueva_valoracion_promedio, $id_producto); // 'd' para decimal/double
                    $stmt_update_producto->execute();
                    $stmt_update_producto->close();
                    $_SESSION['mensaje_valoracion'] = "¡Gracias por tu valoración!";
                } else {
                    $_SESSION['mensaje_valoracion'] = "Error al actualizar el promedio del producto: " . $conexion->error;
                }
            } else {
                 $_SESSION['mensaje_valoracion'] = "Error al calcular el promedio: " . $conexion->error;
            }
        } else {
            $_SESSION['mensaje_valoracion'] = "Error al guardar tu valoración: " . $stmt_guardar_valoracion->error;
        }
        $stmt_guardar_valoracion->close();
    } else {
        $_SESSION['mensaje_valoracion'] = "Error al preparar la consulta para guardar valoración: " . $conexion->error;
    }

    $conexion->close();
    header("Location: ../php/product.php?id=" . $id_producto);
    exit;

} else {
    // Si no es POST, redirigir o mostrar error
    $_SESSION['mensaje_valoracion'] = "Método no permitido.";
    header("Location: ../php/product.php" . ($id_producto_redirect ? "?id=" . $id_producto_redirect : "")); // Redirige a la página del producto si es posible
    exit;
}
?>