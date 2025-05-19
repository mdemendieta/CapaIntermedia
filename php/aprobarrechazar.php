<?php
session_start();
require_once '../modelos/conexion.php';

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'Administrador') {
    header("Location: ../php/landing.php");
    exit();
}

$db = new Database();
$conexion = $db->getConexion();

// Obtener productos pendientes
$queryProductos = "
    SELECT 
        p.id_producto, 
        p.Nombre AS nombre_producto, 
        p.Descripcion, 
        p.Inventario,
        p.Tipo AS tipo_publicacion, /* Vender o Cotizar */
        p.Precio,
        u.nombre_usuario AS nombre_vendedor,
        u.email AS email_vendedor, /* Para contacto o referencia */
        GROUP_CONCAT(mp.URL SEPARATOR ',') AS imagenes_urls, /* Concatenar URLs de imágenes */
        (SELECT c.NombreCategoria FROM Categoria c WHERE c.id_categoria = p.id_categoria) AS nombre_categoria
    FROM Producto p
    JOIN Usuario u ON p.id_vendedor = u.id_usuario
    LEFT JOIN MultimediaProducto mp ON p.id_producto = mp.id_producto
    WHERE p.Estado = 'Pendiente'
    GROUP BY p.id_producto  /* Agrupar para que GROUP_CONCAT funcione por producto */
    ORDER BY p.FechaCreacion ASC
";
$resultado = $conexion->query($queryProductos);
$productos_pendientes = [];
if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        if (!empty($fila['imagenes_urls'])) {
            $fila['imagenes_array'] = explode(',', $fila['imagenes_urls']);
        } else {
            $fila['imagenes_array'] = ['../recursos/placeholder.png']; // Placeholder si no hay imágenes
        }
        // Tomar solo la primera imagen para la vista principal de la tarjeta (o implementar carrusel)
        $fila['imagen_principal'] = $fila['imagenes_array'][0];
        $productos_pendientes[] = $fila;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobar/Rechazar Publicaciones</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/styles_myProducts.css"> <style>
        .product-item-admin {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff;
            display: flex;
            gap: 15px;
        }
        .product-item-admin img {
            width: 120px; /* Ajusta según necesidad */
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
        }
        .product-details-admin { flex-grow: 1; }
        .product-actions-admin button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 10px;
        }
        .btn-approve { background-color: #28a745; color: white; }
        .btn-approve:hover { background-color: #218838; }
        .btn-reject { background-color: #dc3545; color: white; }
        .btn-reject:hover { background-color: #c82333; }
        .product-status-updated {
            padding: 10px; margin-top:10px; border-radius: 5px; text-align: center;
        }
    </style>
</head>
<body class="bg-orange-100">
    <?php include 'navbar.php'; ?>

    <div id="main-content" class="flex-1 transition-all duration-300 p-6 min-h-screen">
        <div class="my-container mx-auto"> <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Solicitudes de Publicación Pendientes</h1>

            <div id="product-list-admin">
                <?php if (!empty($productos_pendientes)): ?>
                    <?php foreach ($productos_pendientes as $producto): ?>
                        <div class="product-item-admin" id="producto-<?php echo $producto['id_producto']; ?>">
                            <img src="<?php echo htmlspecialchars(str_replace('..', '.', $producto['imagen_principal'])); ?>" alt="<?php echo htmlspecialchars($producto['nombre_producto']); ?>">
                            <div class="product-details-admin">
                                <h3 class="text-xl font-semibold text-blue-700"><?php echo htmlspecialchars($producto['nombre_producto']); ?></h3>
                                <p class="text-sm text-gray-500">Vendedor: <span class="font-medium text-gray-700"><?php echo htmlspecialchars($producto['nombre_vendedor']); ?></span> (<?php echo htmlspecialchars($producto['email_vendedor']); ?>)</p>
                                <p class="text-sm text-gray-500">Categoría: <span class="font-medium text-gray-700"><?php echo htmlspecialchars($producto['nombre_categoria'] ?? 'N/A'); ?></span></p>
                                <p class="text-sm text-gray-500">Stock: <span class="font-medium text-gray-700"><?php echo htmlspecialchars($producto['Inventario']); ?></span></p>
                                <?php if($producto['tipo_publicacion'] == 'Vender' && isset($producto['Precio'])): ?>
                                <p class="text-sm text-gray-500">Precio: <span class="font-medium text-green-600">$<?php echo htmlspecialchars(number_format($producto['Precio'], 2)); ?></span></p>
                                <?php else: ?>
                                <p class="text-sm text-gray-500">Tipo: <span class="font-medium text-blue-600">Para Cotizar</span></p>
                                <?php endif; ?>
                                <p class="text-gray-700 mt-2 text-sm">Descripción: <?php echo nl2br(htmlspecialchars(substr($producto['Descripcion'], 0, 200) . (strlen($producto['Descripcion']) > 200 ? '...' : ''))); ?></p>
                                <div class="product-actions-admin mt-4">
                                    <button class="btn-approve" onclick="actualizarEstadoProducto(<?php echo $producto['id_producto']; ?>, 'Aprobado')">Aprobar</button>
                                    <button class="btn-reject" onclick="actualizarEstadoProducto(<?php echo $producto['id_producto']; ?>, 'Rechazado')">Rechazar</button>
                                </div>
                                <div id="status-producto-<?php echo $producto['id_producto']; ?>" class="product-status-updated" style="display:none;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-600 text-center py-10">No hay productos pendientes de aprobación en este momento.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function actualizarEstadoProducto(idProducto, nuevoEstado) {
            const confirmacionTexto = nuevoEstado === 'Aprobado' 
                ? "¿Estás seguro de que quieres aprobar esta publicación?"
                : "¿Estás seguro de que quieres rechazar esta publicación?";
            
            if (!confirm(confirmacionTexto)) {
                return;
            }

            const formData = new FormData();
            formData.append('id_producto', idProducto);
            formData.append('nuevo_estado', nuevoEstado);

            fetch('../controladores/actualizar_estado_producto.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById(`status-producto-${idProducto}`);
                const productCardDiv = document.getElementById(`producto-${idProducto}`);
                
                if (data.success) {
                    statusDiv.textContent = `Producto ${nuevoEstado.toLowerCase()} exitosamente.`;
                    statusDiv.style.color = nuevoEstado === 'Aprobado' ? 'green' : 'red';
                    statusDiv.style.display = 'block';
                    
                    // Ocultar botones de acción después de la actualización
                    const actionsDiv = productCardDiv.querySelector('.product-actions-admin');
                    if(actionsDiv) actionsDiv.style.display = 'none';

                    // Opcional: remover la tarjeta de la lista después de un tiempo o dejarla con el estado
                    // setTimeout(() => {
                    //    productCardDiv.style.opacity = '0';
                    //    setTimeout(() => productCardDiv.remove(), 500);
                    // }, 2000);

                } else {
                    statusDiv.textContent = 'Error: ' + data.message;
                    statusDiv.style.color = 'red';
                    statusDiv.style.display = 'block';
                    alert('Error al actualizar el estado: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const statusDiv = document.getElementById(`status-producto-${idProducto}`);
                statusDiv.textContent = 'Error de conexión al actualizar el estado.';
                statusDiv.style.color = 'red';
                statusDiv.style.display = 'block';
                alert('Ocurrió un error de conexión.');
            });
        }
    </script>
</body>
</html>