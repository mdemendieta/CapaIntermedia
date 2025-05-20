<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../modelos/conexion.php'; // Adjust path as needed

$totalVenta = 0;
if (isset($_GET['total'])) {
    $totalVenta = filter_var($_GET['total'], FILTER_VALIDATE_FLOAT);
}

$mensaje = '';
$mensaje_tipo = ''; // 'success' o 'error'

if (!isset($_SESSION['id_usuario'])) {
    // Redirect to login or show an error if user is not logged in
    // For simplicity, we'll just show an error here.
    $mensaje = "Debes iniciar sesión para completar la compra.";
    $mensaje_tipo = 'error';
    // You might want to redirect: header('Location: login.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_compra']) && $mensaje_tipo !== 'error') {
    $db = new Database();
    $conexion = $db->getConexion();

    $id_cliente = $_SESSION['id_usuario'];
    // In a real scenario, you would retrieve all product details from the cart session/database
    // to insert individual items if needed.
    // For now, the Venta table seems to be designed per product.
    // This is a simplified approach to make one entry for the whole cart, which might not fit your Venta table perfectly.

    // You need to decide how to handle id_vendedor and id_producto for a "total cart" sale.
    // If it's a single entry for the cart total:
    // Option 1: Store a generic id_producto (e.g., 0 or NULL if allowed) and an id_vendedor (if applicable, perhaps the marketplace itself or leave NULL).
    // Option 2: Create separate tables for Orders and OrderItems for a more robust solution.

    // Assuming a simplified single entry for the total for demonstration.
    // THIS IS A PLACEHOLDER. You need to decide how to populate these based on your application logic
    // and potentially the Venta table's constraints or if you intend to insert multiple rows (one per cart item).
    
    // Example: If you iterate through cart items (retrieved from session or CarritoModel)
    // $carritoModel = new CarritoModel($conexion); // Assuming you have this model
    // $productosEnCarrito = $carritoModel->obtenerProductosDelCarrito($id_cliente);

    $compra_exitosa_general = true;
    $errores_compra = [];

    // --- RETRIEVE CART ITEMS TO INSERT INTO VENTA TABLE ---
    // You would typically get cart items from the session or database here.
    // For this example, let's assume $_SESSION['carrito'] exists and is an array of items.
    // Each item should have 'id_producto', 'cantidad', 'precio_unitario', 'id_vendedor'.
    
    // Replace this with your actual cart retrieval logic:
    // $items_del_carrito = $_SESSION['carrito_items'] ?? []; // Example structure: [['id_producto'=>1, 'cantidad'=>2, 'precio_unitario'=>10.00, 'id_vendedor'=>5], ...]

    // Placeholder for demonstration: If you don't have individual items easily accessible here,
    // and the Venta table REQUIRES id_producto, this part will be problematic.
    // The Venta table structure (id_producto INT, CantidadVendida INT) implies it's per product.
    // So, you'd likely loop through cart items and insert one row in Venta per item.
    
    // Let's simulate getting items from CarritoModel (as used in carrito.php)
    require_once '../modelos/CarritoModel.php'; // Ensure path is correct
    $carritoModel = new CarritoModel($conexion); // Pass the existing connection
    $productosEnCarrito = $carritoModel->obtenerProductosDelCarrito($id_cliente);

    if (empty($productosEnCarrito)) {
        $mensaje = "Tu carrito está vacío. No se puede proceder con la venta.";
        $mensaje_tipo = 'error';
    } else {
        $conexion->begin_transaction(); // Start a transaction

        try {
            foreach ($productosEnCarrito as $item) {
                $id_producto_actual = $item['id_producto'];
                $cantidad_vendida_actual = $item['CantidadEnCarrito'];
                // The 'PrecioTotal' in Venta table is per line item according to its DDL comment.
                // The comment "Precio total entre la cantidad del producto" for PrecioTotal DECIMAL(10,2)
                // is a bit ambiguous. Let's assume it's (cantidad * precio_unitario_del_producto) for that row.
                $precio_total_item = $item['Precio'] * $cantidad_vendida_actual; 
                
                // You need to fetch id_vendedor for each product
                $stmt_prod_vendedor = $conexion->prepare("SELECT id_vendedor FROM Producto WHERE id_producto = ?");
                $id_vendedor_actual = null;
                if($stmt_prod_vendedor) {
                    $stmt_prod_vendedor->bind_param("i", $id_producto_actual);
                    $stmt_prod_vendedor->execute();
                    $res_prod_vendedor = $stmt_prod_vendedor->get_result();
                    if($row_vendedor = $res_prod_vendedor->fetch_assoc()){
                        $id_vendedor_actual = $row_vendedor['id_vendedor'];
                    }
                    $stmt_prod_vendedor->close();
                }

                if ($id_vendedor_actual === null) {
                    throw new Exception("No se pudo encontrar el vendedor para el producto ID: " . $id_producto_actual);
                }

                $stmt = $conexion->prepare("INSERT INTO Venta (id_vendedor, id_cliente, id_producto, CantidadVendida, PrecioTotal, FechaHoraVenta) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                if ($stmt) {
                    $stmt->bind_param("iiiid", $id_vendedor_actual, $id_cliente, $id_producto_actual, $cantidad_vendida_actual, $precio_total_item);
                    if (!$stmt->execute()) {
                        $compra_exitosa_general = false;
                        $errores_compra[] = "Error al registrar la venta del producto ID " . $id_producto_actual . ": " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $compra_exitosa_general = false;
                    $errores_compra[] = "Error al preparar la inserción para el producto ID " . $id_producto_actual . ": " . $conexion->error;
                }
                
                if(!$compra_exitosa_general) break; // Stop if one item fails
            }

            if ($compra_exitosa_general) {
                // If all items inserted successfully, also clear the cart
                $resultadoVaciar = $carritoModel->borrarCarrito($id_cliente); // You'd need to implement vaciarCarrito in CarritoModel
                if (!$resultadoVaciar['success']) {
                     // Log this error, but the sale itself might still be considered successful
                    error_log("Error al vaciar el carrito para el usuario $id_cliente: " . $resultadoVaciar['message']);
                }
                $conexion->commit(); // Commit transaction
                $mensaje = "¡Compra realizada con éxito!";
                $mensaje_tipo = 'success';
                // Optionally redirect to an order confirmation page
                // header('Location: confirmacion_pedido.php?id_venta_global=' . $some_order_id); exit;
            } else {
                $conexion->rollback(); // Rollback transaction
                $mensaje = "Error al procesar la compra. Detalles: <br>" . implode("<br>", $errores_compra);
                $mensaje_tipo = 'error';
            }

        } catch (Exception $e) {
            $conexion->rollback();
            $mensaje = "Error crítico durante la compra: " . $e->getMessage();
            $mensaje_tipo = 'error';
        }
    }
    if(isset($conexion)) $conexion->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venta</title> <?php // This title will be shown in the new window's title bar ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Basic styling for messages */
        .mensaje { padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; }
        .mensaje.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-center mb-6">Finalizar Compra</h1>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo $mensaje_tipo; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <?php if ($mensaje_tipo !== 'success' && $mensaje_tipo !== 'error' || ($mensaje_tipo === 'error' && empty($productosEnCarrito))): // Show form if no final message or if error but items were there ?>
            <?php if (!isset($_SESSION['id_usuario'])): ?>
                <p class="text-center text-red-600">Debes estar logueado para proceder.</p>
            <?php elseif ($totalVenta <= 0): ?>
                <p class="text-center text-red-600">No hay un total válido para la venta. Vuelve al carrito.</p>
            <?php else: ?>
                <form action="venta.php?total=<?php echo htmlspecialchars($totalVenta); ?>" method="POST" class="space-y-4">
                    
                    <h2 class="text-xl font-semibold">Total a Pagar: $<?php echo htmlspecialchars(number_format($totalVenta, 2)); ?></h2>
                    <hr>

                    <div>
                        <h3 class="text-lg font-medium mb-2">Datos de Envío</h3>
                        <div>
                            <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección Completa*</label>
                            <input type="text" name="direccion" id="direccion" required class="mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="ciudad" class="block text-sm font-medium text-gray-700">Ciudad*</label>
                            <input type="text" name="ciudad" id="ciudad" required class="mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="codigo_postal" class="block text-sm font-medium text-gray-700">Código Postal*</label>
                            <input type="text" name="codigo_postal" id="codigo_postal" required class="mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="pais" class="block text-sm font-medium text-gray-700">País*</label>
                            <input type="text" name="pais" id="pais" value="México" required class="mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                    <hr>
                    <div>
                        <h3 class="text-lg font-medium mb-2">Datos de Pago</h3>
                        <p class="text-sm text-gray-500 mb-2"><i>(Funcionalidad de pago real no implementada - esto es solo una demostración de formulario)</i></p>
                        <div>
                            <label for="nombre_tarjeta" class="block text-sm font-medium text-gray-700">Nombre en la Tarjeta*</label>
                            <input type="text" name="nombre_tarjeta" id="nombre_tarjeta" required class="mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="numero_tarjeta" class="block text-sm font-medium text-gray-700">Número de Tarjeta*</label>
                            <input type="text" name="numero_tarjeta" id="numero_tarjeta" required placeholder="XXXX XXXX XXXX XXXX" class="mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="fecha_exp" class="block text-sm font-medium text-gray-700">Fecha de Expiración*</label>
                                <input type="text" name="fecha_exp" id="fecha_exp" required placeholder="MM/AA" class="mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="cvv" class="block text-sm font-medium text-gray-700">CVV*</label>
                                <input type="text" name="cvv" id="cvv" required placeholder="XXX" class="mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="confirmar_compra" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-md">
                        Confirmar Compra y Pagar
                    </button>
                </form>
            <?php endif; ?>
        <?php elseif ($mensaje_tipo === 'success'): ?>
             <div class="text-center">
                 <p>Serás redirigido a la página de inicio en unos segundos...</p>
                 <a href="landing.php" class="text-blue-500 hover:underline">O haz clic aquí para ir ahora.</a>
             </div>
            <script>
                setTimeout(function() {
                    if (window.opener && !window.opener.closed) {
                         // Refresh the opener window (carrito.php or landing.php)
                        window.opener.location.reload();
                    }
                    window.close(); // Close this popup
                }, 5000); // 5 seconds
            </script>
        <?php endif; ?>
    </div>
</body>
</html>