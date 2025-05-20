<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../modelos/conexion.php';
require_once '../modelos/CarritoModel.php';

$productosEnCarrito = [];
$totalCarrito = 0;
$usuarioLogueado = false;

if (isset($_SESSION['id_usuario'])) {
    $usuarioLogueado = true;
    $id_usuario_actual = $_SESSION['id_usuario'];

    $db = new Database();
    $conexion = $db->getConexion();
    $carritoModel = new CarritoModel($conexion); // Pasar la conexión aquí

    $productosEnCarrito = $carritoModel->obtenerProductosDelCarrito($id_usuario_actual);

    if (!empty($productosEnCarrito)) {
        foreach ($productosEnCarrito as $producto) {
            // Validar que Precio y CantidadEnCarrito sean numéricos antes de multiplicar
            $precio = is_numeric($producto['Precio']) ? (float)$producto['Precio'] : 0;
            $cantidad = is_numeric($producto['CantidadEnCarrito']) ? (int)$producto['CantidadEnCarrito'] : 0;
            $totalCarrito += $precio * $cantidad;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .quantity-input {
            width: 70px; /* Ajusta según necesidad */
            appearance: textfield; /* Oculta flechas en algunos navegadores */
        }
        .quantity-input::-webkit-outer-spin-button,
        .quantity-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>
<header>
    <?php include('navbar.php'); ?>
</header>
<body class="bg-gray-100">

    <div id="main-content" class="flex-1 transition-all duration-300 p-4 md:p-6 bg-orange-100 min-h-screen">

        <h1 class="text-2xl md:text-3xl font-bold mb-6 text-gray-800">Carrito de Compras</h1>

        <?php if (!$usuarioLogueado): ?>
            <p class="text-center text-gray-600 py-10">Debes <a href="#" onclick="showLoginModal()" class="text-orange-500 hover:underline font-semibold">iniciar sesión</a> para ver tu carrito.</p>
        <?php elseif (empty($productosEnCarrito)): ?>
            <p class="text-center text-gray-600 py-10">Tu carrito está vacío.</p>
            <div class="text-center mt-4">
                <a href="landing.php" class="bg-orange-500 text-white px-6 py-3 rounded-lg hover:bg-orange-600 transition-colors duration-300">Seguir comprando</a>
            </div>
        <?php else: ?>
            <div id="cart-items-container" class="space-y-4">
                <?php foreach ($productosEnCarrito as $producto):
                    $precioValido = is_numeric($producto['Precio']) ? (float)$producto['Precio'] : 0;
                    $cantidadValida = is_numeric($producto['CantidadEnCarrito']) ? (int)$producto['CantidadEnCarrito'] : 0;
                    $inventarioValido = is_numeric($producto['Inventario']) ? (int)$producto['Inventario'] : 0;
                ?>
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-center bg-white rounded-xl shadow-lg p-4" data-id-producto="<?php echo htmlspecialchars($producto['id_producto']); ?>">
                        <div class="md:col-span-1 flex justify-center md:justify-start">
                            <img src="<?php echo htmlspecialchars(str_replace('../', './', $producto['imagen_url'] ?? '../recursos/placeholder.png')); ?>"
                                 alt="<?php echo htmlspecialchars($producto['Nombre']); ?>"
                                 class="h-24 w-24 md:h-28 md:w-28 object-cover rounded-lg border border-gray-200">
                        </div>

                        <div class="md:col-span-2 text-center md:text-left">
                            <h2 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($producto['Nombre']); ?></h2>
                            <p class="text-sm text-gray-500">Precio: $<?php echo htmlspecialchars(number_format($precioValido, 2)); ?></p>
                        </div>

                        <div class="md:col-span-1 flex items-center justify-center space-x-2">
                            <input type="number"
                                   id="cantidad-<?php echo htmlspecialchars($producto['id_producto']); ?>"
                                   name="cantidades[<?php echo htmlspecialchars($producto['id_producto']); ?>]"
                                   value="<?php echo htmlspecialchars($cantidadValida); ?>"
                                   min="1"
                                   max="<?php echo htmlspecialchars($inventarioValido); ?>"
                                   class="quantity-input p-2 border border-gray-300 rounded-md text-center focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   onchange="actualizarSubtotalYTotal(this, <?php echo $precioValido; ?>, '<?php echo htmlspecialchars($producto['id_producto']); ?>')"
                                   aria-describedby="stock-info-<?php echo htmlspecialchars($producto['id_producto']); ?>">
                            <small id="stock-info-<?php echo htmlspecialchars($producto['id_producto']); ?>" class="text-xs text-gray-500">(Stock: <?php echo htmlspecialchars($inventarioValido); ?>)</small>
                        </div>

                        <div class="md:col-span-1 text-center md:text-right">
                            <p class="text-md font-semibold text-gray-800" id="subtotal-<?php echo htmlspecialchars($producto['id_producto']); ?>">
                                $<?php echo htmlspecialchars(number_format($precioValido * $cantidadValida, 2)); ?>
                            </p>
                        </div>

                        <div class="md:col-span-1 flex justify-center md:justify-end">
                            <button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors duration-300 text-sm"
                                    onclick="eliminarDelCarrito('<?php echo htmlspecialchars($producto['id_producto']); ?>', this)">
                                Eliminar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-8 pt-6 border-t-2 border-gray-200 text-right">
                <h2 class="text-xl md:text-2xl font-bold text-gray-800">Total: <span id="total-carrito">$<?php echo htmlspecialchars(number_format($totalCarrito, 2)); ?></span></h2>
            </div>

            <div class="mt-6 flex justify-center md:justify-end">
                    <button 
                    onclick="procederAlPago(<?php echo $totalCarrito; ?>)" 
                    class="bg-green-500 text-white px-8 py-3 rounded-lg hover:bg-green-600 transition-colors duration-300 font-semibold text-lg">
                    Proceder al Pago
                </button>            
            </div>
        <?php endif; ?>
    </div>

<script>
    function actualizarSubtotalYTotal(inputElement, precioUnitario, idProducto) {
        let cantidad = parseInt(inputElement.value);
        const stock = parseInt(inputElement.max);

        if (isNaN(cantidad) || cantidad < 1) {
            cantidad = 1;
            inputElement.value = 1;
        }
        if (cantidad > stock) {
            cantidad = stock;
            inputElement.value = stock;
            // Considera mostrar un mensaje más amigable que un alert
            // Por ejemplo, un pequeño texto cerca del input.
        }

        const subtotal = cantidad * precioUnitario;
        const subtotalElement = document.getElementById('subtotal-' + idProducto);
        if (subtotalElement) {
            subtotalElement.textContent = '$' + subtotal.toFixed(2);
        }

        calcularTotalGeneral();

        // Opcional: Actualizar cantidad en el servidor
        // Se recomienda hacer esto con un debounce o al salir del campo para no sobrecargar.
        // O solo al "proceder al pago".
        // actualizarCantidadEnServidor(idProducto, cantidad);
    }

    function calcularTotalGeneral() {
        let nuevoTotalGeneral = 0;
        const items = document.querySelectorAll('#cart-items-container > div[data-id-producto]');
        items.forEach(item => {
            const idProducto = item.dataset.idProducto;
            const subtotalElement = document.getElementById('subtotal-' + idProducto);
            if (subtotalElement) {
                nuevoTotalGeneral += parseFloat(subtotalElement.textContent.replace('$', '').replace(',', ''));
            }
        });
        const totalCarritoElement = document.getElementById('total-carrito');
        if (totalCarritoElement) {
            totalCarritoElement.textContent = '$' + nuevoTotalGeneral.toFixed(2);
        }
    }

    async function eliminarDelCarrito(idProducto, buttonElement) {
        if (!confirm('¿Estás seguro de que quieres eliminar este producto del carrito?')) {
            return;
        }

        const itemDiv = buttonElement.closest('div[data-id-producto]');
        if (itemDiv) {
            itemDiv.style.opacity = '0.5'; // Indicador visual
        }

        try {
            const formData = new FormData();
            formData.append('action', 'remove_from_cart');
            formData.append('id_producto', idProducto);

            const response = await fetch('../controladores/CarritoController.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                if (itemDiv) {
                    itemDiv.remove();
                }
                calcularTotalGeneral();
                
                if (document.querySelectorAll('#cart-items-container > div[data-id-producto]').length === 0) {
                    const mainContent = document.getElementById('main-content');
                    if(mainContent){
                        mainContent.innerHTML = `
                            <h1 class="text-2xl md:text-3xl font-bold mb-6 text-gray-800">Carrito de Compras</h1>
                            <p class="text-center text-gray-600 py-10">Tu carrito está vacío.</p>
                            <div class="text-center mt-4">
                                <a href="landing.php" class="bg-orange-500 text-white px-6 py-3 rounded-lg hover:bg-orange-600 transition-colors duration-300">Seguir comprando</a>
                            </div>`;
                    }
                }
            } else {
                alert('Error al eliminar el producto: ' + (data.message || 'Error desconocido'));
                if (itemDiv) itemDiv.style.opacity = '1';
            }
        } catch (error) {
            console.error('Error en eliminarDelCarrito:', error);
            alert('Error de conexión al intentar eliminar el producto.');
            if (itemDiv) itemDiv.style.opacity = '1';
        }
    }
    
    // Función para actualizar cantidad en servidor (llamaría a CarritoController.php)
    // async function actualizarCantidadEnServidor(idProducto, nuevaCantidad) {
    //     const formData = new FormData();
    //     formData.append('action', 'update_quantity_in_cart'); // Necesitarás crear este action
    //     formData.append('id_producto', idProducto);
    //     formData.append('cantidad', nuevaCantidad);
    //     try {
    //         const response = await fetch('../controladores/CarritoController.php', {
    //             method: 'POST',
    //             body: formData
    //         });
    //         const data = await response.json();
    //         if (!data.success) {
    //             alert('Error al actualizar la cantidad: ' + data.message);
    //             // Aquí podrías revertir el cambio en el frontend o recargar los datos del carrito.
    //         }
    //     } catch (error) {
    //         console.error('Error al actualizar cantidad en servidor:', error);
    //     }
    // }

    // Para el enlace "iniciar sesión" si el usuario no está logueado.
    // La función showLoginModal() se asume que está definida globalmente por navbar.js o similar.
function procederAlPago(total) {
        // Construct the URL for venta.php with the total as a query parameter
        const urlVenta = `../php/venta.php?total=${total}`;
        // Open venta.php in a new window titled "venta.php"
        // Note: Some browsers might block pop-ups if not initiated by a direct user action.
        // A simple click on a button is usually fine.
        window.open(urlVenta, 'venta.php', 'width=800,height=700,scrollbars=yes,resizable=yes');
    }

    </script>
</body>
</html>