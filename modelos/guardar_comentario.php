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
        $_SESSION['mensaje_comentario'] = "Debes iniciar sesión para comentar.";
        header("Location: ../php/product.php" . ($id_producto_redirect ? "?id=" . $id_producto_redirect : ""));
        exit;
    }

    $id_usuario = $_SESSION['id_usuario']; // En product.php se llama 'id_autor' para la tabla Comentario
    $id_producto = filter_var($_POST['id_producto'], FILTER_VALIDATE_INT);
    $texto_comentario = trim($_POST['texto_comentario']);

    if (!$id_producto || empty($texto_comentario)) {
        $_SESSION['mensaje_comentario'] = "El comentario no puede estar vacío.";
        header("Location: ../php/product.php?id=" . $id_producto);
        exit;
    }

    // Validación de longitud del comentario (opcional, pero recomendada)
    if (strlen($texto_comentario) > 500) { // El campo Texto en la tabla Comentario es VARCHAR(500)
        $_SESSION['mensaje_comentario'] = "El comentario no puede exceder los 500 caracteres.";
        header("Location: ../php/product.php?id=" . $id_producto);
        exit;
    }

    // 1. Verificar si el usuario ha comprado el producto (seguridad adicional)
    $puede_comentar_db = false;
    $stmt_venta_check = $conexion->prepare("SELECT COUNT(*) AS total_ventas FROM Venta WHERE id_cliente = ? AND id_producto = ?");
    if ($stmt_venta_check) {
        $stmt_venta_check->bind_param("ii", $id_usuario, $id_producto);
        $stmt_venta_check->execute();
        $result_venta_check = $stmt_venta_check->get_result();
        $venta_info = $result_venta_check->fetch_assoc();
        if ($venta_info && $venta_info['total_ventas'] > 0) {
            $puede_comentar_db = true;
        }
        $stmt_venta_check->close();
    }

    if (!$puede_comentar_db) {
        $_SESSION['mensaje_comentario'] = "Debes haber comprado este producto para poder comentarlo.";
        header("Location: ../php/product.php?id=" . $id_producto);
        exit;
    }

    // 2. Insertar el comentario en la tabla 'Comentario'
    // La tabla Comentario tiene: id_comentario, id_producto, id_autor, Texto, FechaHora
    $stmt_guardar_comentario = $conexion->prepare(
        "INSERT INTO Comentario (id_producto, id_autor, Texto) VALUES (?, ?, ?)"
    );

    if ($stmt_guardar_comentario) {
        // Sanitizar el texto del comentario para prevenir XSS si se va a mostrar directamente sin procesar.
        // htmlspecialchars es una buena práctica, aunque al usar sentencias preparadas, el riesgo de SQL injection es bajo.
        $texto_comentario_saneado = htmlspecialchars($texto_comentario, ENT_QUOTES, 'UTF-8');
        
        $stmt_guardar_comentario->bind_param("iis", $id_producto, $id_usuario, $texto_comentario_saneado);
        if ($stmt_guardar_comentario->execute()) {
            $_SESSION['mensaje_comentario'] = "Comentario añadido exitosamente.";
        } else {
            $_SESSION['mensaje_comentario'] = "Error al guardar tu comentario: " . $stmt_guardar_comentario->error;
        }
        $stmt_guardar_comentario->close();
    } else {
        $_SESSION['mensaje_comentario'] = "Error al preparar la consulta para guardar comentario: " . $conexion->error;
    }

    $conexion->close();
    header("Location: ../php/product.php?id=" . $id_producto . "#comentarios_seccion"); // Redirige de vuelta a la sección de comentarios
    exit;

} else {
    $_SESSION['mensaje_comentario'] = "Método no permitido.";
    header("Location: ../php/product.php" . ($id_producto_redirect ? "?id=" . $id_producto_redirect : ""));
    exit;
}
?>