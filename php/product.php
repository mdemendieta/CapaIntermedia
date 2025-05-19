<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../modelos/conexion.php'; // Conexión a la base de datos

$product_id = null;
$producto = null;
$vendedor_nombre = "Vendedor Desconocido";
$categoria_nombre = "Categoría Desconocida";
$multimedia = [];
$comentarios = [];
$error_message = '';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($product_id) {
        $db = new Database();
        $conexion = $db->getConexion();

        // 1. Obtener detalles del Producto, Vendedor y Categoría
        $stmt_producto = $conexion->prepare("
            SELECT 
                p.Nombre AS ProductoNombre, 
                p.Descripcion AS ProductoDescripcion, 
                p.Precio AS ProductoPrecio, 
                p.Valoracion AS ProductoValoracion,
                p.Tipo AS ProductoTipo,
                p.id_vendedor AS ProductoVendedorID,
                u.nombre_usuario AS VendedorNombreUsuario, 
                u.nombre AS VendedorNombre,
                u.apellido_P AS VendedorApellidoP,
                c.NombreCategoria AS CategoriaNombre
            FROM Producto p
            JOIN Usuario u ON p.id_vendedor = u.id_usuario
            JOIN Categoria c ON p.id_categoria = c.id_categoria
            WHERE p.id_producto = ? AND p.Estado = 'Aprobado'
        ");
        $stmt_producto->bind_param("i", $product_id);
        $stmt_producto->execute();
        $result_producto = $stmt_producto->get_result();

        if ($result_producto->num_rows > 0) {
            $producto = $result_producto->fetch_assoc();
            $vendedor_nombre = !empty($producto['VendedorNombre']) ? $producto['VendedorNombre'] . ' ' . $producto['VendedorApellidoP'] : $producto['VendedorNombreUsuario'];
            $categoria_nombre = $producto['CategoriaNombre'];
        } else {
            $error_message = "Producto no encontrado o no está disponible.";
        }
        $stmt_producto->close();

        if ($producto) {
            // 2. Obtener Multimedia del Producto
            $stmt_multimedia = $conexion->prepare("SELECT URL FROM MultimediaProducto WHERE id_producto = ? ORDER BY id_multimedia ASC");
            $stmt_multimedia->bind_param("i", $product_id);
            $stmt_multimedia->execute();
            $result_multimedia = $stmt_multimedia->get_result();
            while ($row_multimedia = $result_multimedia->fetch_assoc()) {
                $multimedia[] = $row_multimedia['URL'];
            }
            $stmt_multimedia->close();

            // 3. Obtener Comentarios del Producto
            $stmt_comentarios = $conexion->prepare("
                SELECT 
                    c.Texto, 
                    c.FechaHora AS FechaComentario,
                    u.nombre_usuario AS AutorNombreUsuario,
                    u.nombre AS AutorNombre,
                    u.apellido_P AS AutorApellidoP
                FROM Comentario c
                JOIN Usuario u ON c.id_autor = u.id_usuario
                WHERE c.id_producto = ?
                ORDER BY c.id_comentario DESC 
            ");
            $stmt_comentarios->bind_param("i", $product_id);
            $stmt_comentarios->execute();
            $result_comentarios = $stmt_comentarios->get_result();
            while ($row_comentarios = $result_comentarios->fetch_assoc()) {
                $comentarios[] = $row_comentarios;
            }
            $stmt_comentarios->close();
        }
        $conexion->close();
    } else {
        $error_message = "ID de producto inválido.";
    }
} else {
    $error_message = "No se especificó un ID de producto.";
}
$video_extensions = ['mp4', 'webm', 'ogv', 'mov', 'avi']; 
$image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp']; 

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Producto - <?php echo $producto ? htmlspecialchars($producto['ProductoNombre']) : 'Producto'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script> 
    <style>
        .video-thumbnail-container .play-icon {
            transition: opacity 0.3s ease;
        }
        .video-thumbnail-container:hover .play-icon {
            opacity: 1 !important;
        }
    </style>
</head>

<body class="bg-orange-100">
    <header>
        <?php include 'navbar.php'; ?>
    </header>

    <div id="main-content" class="flex-1 transition-all duration-300 p-6 bg-orange-100 min-h-screen">
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php elseif ($producto): ?>
            <div class="flex flex-col md:flex-row gap-6 bg-white p-6 rounded-lg shadow-md">
                <div class="flex">
                    <div id="thumbnail-gallery" class="flex flex-col gap-2 mr-4">
                        <?php if (!empty($multimedia)): ?>
                            <?php foreach ($multimedia as $index => $url_media):
                                $media_url_processed = htmlspecialchars(str_starts_with($url_media, '../') ? $url_media : '../' . $url_media);
                                $file_extension = strtolower(pathinfo($url_media, PATHINFO_EXTENSION));
                            ?>
                                <?php if (in_array($file_extension, $image_extensions)): ?>
                                    <img src="<?php echo $media_url_processed; ?>" 
                                         alt="Miniatura Imagen <?php echo $index + 1; ?> de <?php echo htmlspecialchars($producto['ProductoNombre']); ?>" 
                                         class="thumbnail-item w-16 h-16 object-cover rounded cursor-pointer border hover:border-orange-500"
                                         data-type="image"
                                         data-src="<?php echo $media_url_processed; ?>">
                                <?php elseif (in_array($file_extension, $video_extensions)): ?>
                                    <div class="thumbnail-item video-thumbnail-container w-16 h-16 object-cover rounded cursor-pointer border hover:border-orange-500 relative bg-black flex items-center justify-center"
                                         data-type="video"
                                         data-src="<?php echo $media_url_processed; ?>">
                                        <video src="<?php echo $media_url_processed; ?>#t=0.5" class="w-full h-full object-cover rounded" preload="metadata" muted></video>
                                        <span class="play-icon absolute text-white text-3xl opacity-70 pointer-events-none">▶</span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <img src="../recursos/placeholder.png" alt="Sin multimedia disponible" class="w-16 h-16 object-cover rounded border">
                        <?php endif; ?>
                    </div>
                    <div id="main-preview-container" class="flex-shrink-0 w-96 h-96 bg-gray-200 rounded flex items-center justify-center overflow-hidden">
                        <?php
                        if (!empty($multimedia)) {
                            $first_media_url = htmlspecialchars(str_starts_with($multimedia[0], '../') ? $multimedia[0] : '../' . $multimedia[0]);
                            $first_media_alt = "Principal " . htmlspecialchars($producto['ProductoNombre']);
                            $first_media_extension = strtolower(pathinfo($multimedia[0], PATHINFO_EXTENSION));

                            if (in_array($first_media_extension, $video_extensions)) {
                                echo '<video src="' . $first_media_url . '" class="max-w-full max-h-full object-contain rounded" controls autoplay muted></video>';
                            } else { // Es imagen o tipo desconocido, tratar como imagen
                                echo '<img src="' . $first_media_url . '" alt="' . $first_media_alt . '" class="max-w-full max-h-full object-contain rounded">';
                            }
                        } else {
                            echo '<img src="../recursos/placeholder.png" alt="Sin multimedia disponible" class="max-w-full max-h-full object-contain rounded">';
                        }
                        ?>
                    </div>
                </div>
                
              

                <div class="flex-1 space-y-3">
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($producto['ProductoNombre']); ?></h1>
                    <p class="text-lg text-gray-700">Publicado por <strong class="text-indigo-600"><?php echo htmlspecialchars($vendedor_nombre); ?></strong></p>
                    <p class="text-blue-500 text-sm ">Categoría:<span 
                    class="bg-blue-200 font-bold rounded-[15px] py-1 px-2 m-1"><?php echo htmlspecialchars($categoria_nombre); ?></span></p>
                    <div class="text-yellow-500 text-xl">
                        <?php 
                        $valoracion = round($producto['ProductoValoracion'] ?? 0);
                        for ($i = 1; $i <= 5; $i++): ?>
                            <?php echo ($i <= $valoracion) ? '★' : '☆'; ?>
                        <?php endfor; ?>
                         <span class="text-gray-600 text-sm ml-1">(<?php echo number_format($producto['ProductoValoracion'] ?? 0, 1); ?>)</span> 
                    </div>      
                    <?php if ($producto['ProductoDescripcion']): ?>
                        <p class="text-gray-600 mt-2 text-lg">
                            <strong>Descripción:</strong><br>
                            <?php echo nl2br(htmlspecialchars($producto['ProductoDescripcion'])); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (isset($producto['ProductoTipo']) && $producto['ProductoTipo'] === 'Cotizar'): ?>
                        <a href="chat.php?contactId=<?php echo htmlspecialchars($producto['ProductoVendedorID']); ?>" 
                        class="inline-block bg-green-500 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded text-lg">
                            Cotizar por Mensaje
                        </a>
                    <?php else: // Asumimos 'Vender' u otro tipo que muestre precio ?>
                        <p class="text-2xl font-bold text-green-600">$<?php echo number_format($producto['ProductoPrecio'], 2); ?></p>
                    <?php endif; ?>
                    

                    <div class="flex space-x-3 items-center pt-2">
                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded">
                            Añadir a una lista
                        </button>
                        <?php if (!isset($producto['ProductoTipo']) || $producto['ProductoTipo'] !== 'Cotizar'): // Mostrar solo si no es para cotizar ?>
                            <button class="bg-[#ffae00] hover:bg-[#ff9d00] text-white font-semibold py-2 px-4 rounded">
                                Añadir al carrito
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <section class="mt-10 bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4">Comentarios</h3>
                
                <div class="mb-6">
                    <textarea class="w-full p-3 border rounded resize-none mb-2" rows="3"
                        placeholder="Escribe un comentario..."></textarea>
                    <button class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">Enviar Comentario</button>
                </div>

                <div class="space-y-4">
                    <?php if (!empty($comentarios)): ?>
                        <?php foreach ($comentarios as $comentario): 
                            $nombre_autor = !empty($comentario['AutorNombre']) ? $comentario['AutorNombre'] . ' ' . $comentario['AutorApellidoP'] : $comentario['AutorNombreUsuario'];
                        ?>
                            <div class="border-b pb-4">
                                <p class="font-semibold text-indigo-700"><?php echo htmlspecialchars($nombre_autor); ?></p>
                                <?php if (isset($comentario['FechaComentario'])): // Asumiendo que tienes una columna de fecha ?>
                                    <p class="text-xs text-gray-500 mb-1"><?php echo date("d/m/Y H:i", strtotime($comentario['FechaComentario'])); ?></p>
                                <?php endif; ?>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($comentario['Texto'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500">Aún no hay comentarios para este producto. ¡Sé el primero!</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="mt-12">
                <h3 class="text-xl font-semibold mb-4">Más productos de este vendedor</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="h-[400px] bg-gray-800 rounded-[10px] p-2 flex-col">
                        <img src="../recursos/productos/gato1.jpg" alt="Otro producto" class="w-full h-[300px] object-cover rounded mb-2">
                        <p class="text-lg text-white font-semibold">Producto Ejemplo 1</p>
                        <span class="text-green-600 font-bold">$100</span>
                    </div>
                    <div class="h-[400px] bg-gray-800 rounded-[10px] p-2 flex-col">
                        <img src="../recursos/productos/huron.jpg" alt="Otro producto más" class="w-full h-[300px] object-cover rounded mb-2">
                        <p class="text-lg text-white font-semibold">Producto Ejemplo 2</p>
                        <span class="text-green-600 font-bold">$150</span>
                    </div>
                </div>
            </section>

        <?php endif; ?>

        <script src="../js/script_product.js"></script>
    </div>
</body>
</html>