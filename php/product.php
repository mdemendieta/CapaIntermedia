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
    <title>Detalle del Producto - <?php echo $producto ? htmlspecialchars($producto['ProductoNombre']) : 'Producto'; ?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/styles_producto.css">
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
                                        data-type="image" data-src="<?php echo $media_url_processed; ?>">
                                <?php elseif (in_array($file_extension, $video_extensions)): ?>
                                    <div class="thumbnail-item video-thumbnail-container w-16 h-16 object-cover rounded cursor-pointer border hover:border-orange-500 relative bg-black flex items-center justify-center"
                                        data-type="video" data-src="<?php echo $media_url_processed; ?>">
                                        <video src="<?php echo $media_url_processed; ?>#t=0.5"
                                            class="w-full h-full object-cover rounded" preload="metadata" muted></video>
                                        <span class="play-icon absolute text-white text-3xl opacity-70 pointer-events-none">▶</span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <img src="../recursos/placeholder.png" alt="Sin multimedia disponible"
                                class="w-16 h-16 object-cover rounded border">
                        <?php endif; ?>
                    </div>
                    <div id="main-preview-container"
                        class="flex-shrink-0 w-96 h-96 bg-gray-200 rounded flex items-center justify-center overflow-hidden">
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
                    <h1 class="text-3xl font-bold text-gray-800">
                        <?php echo htmlspecialchars($producto['ProductoNombre']); ?>
                    </h1>
                    <p class="text-lg text-gray-700">Publicado por <strong
                            class="text-indigo-600"><?php echo htmlspecialchars($vendedor_nombre); ?></strong></p>
                    <p class="text-blue-500 text-sm ">Categoría:<span
                            class="bg-blue-200 font-bold rounded-[15px] py-1 px-2 m-1"><?php echo htmlspecialchars($categoria_nombre); ?></span>
                    </p>
                    <div class="text-yellow-500 text-xl">
                        <?php
                        $valoracion = round($producto['ProductoValoracion'] ?? 0);
                        for ($i = 1; $i <= 5; $i++): ?>
                            <?php echo ($i <= $valoracion) ? '★' : '☆'; ?>
                        <?php endfor; ?>
                        <span
                            class="text-gray-600 text-sm ml-1">(<?php echo number_format($producto['ProductoValoracion'] ?? 0, 1); ?>)</span>
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
                        <p class="text-2xl font-bold text-green-600">
                            $<?php echo number_format($producto['ProductoPrecio'], 2); ?></p>
                    <?php endif; ?>


                    <div class="flex space-x-3 items-center pt-2">
                        <button id="btnAnadirLista"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded">
                            Añadir a una lista
                        </button>
                        <?php if (!isset($producto['ProductoTipo']) || $producto['ProductoTipo'] !== 'Cotizar'): // Mostrar solo si no es para cotizar ?>
                            <button id="btnAnadirCarrito"
                                class="bg-[#ffae00] hover:bg-[#ff9d00] text-white font-semibold py-2 px-4 rounded">
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
                    <button class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">Enviar
                        Comentario</button>
                </div>

                <div class="space-y-4">
                    <?php if (!empty($comentarios)): ?>
                        <?php foreach ($comentarios as $comentario):
                            $nombre_autor = !empty($comentario['AutorNombre']) ? $comentario['AutorNombre'] . ' ' . $comentario['AutorApellidoP'] : $comentario['AutorNombreUsuario'];
                            ?>
                            <div class="border-b pb-4">
                                <p class="font-semibold text-indigo-700"><?php echo htmlspecialchars($nombre_autor); ?></p>
                                <?php if (isset($comentario['FechaComentario'])): // Asumiendo que tienes una columna de fecha ?>
                                    <p class="text-xs text-gray-500 mb-1">
                                        <?php echo date("d/m/Y H:i", strtotime($comentario['FechaComentario'])); ?>
                                    </p>
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
                        <img src="../recursos/productos/gato1.jpg" alt="Otro producto"
                            class="w-full h-[300px] object-cover rounded mb-2">
                        <p class="text-lg text-white font-semibold">Producto Ejemplo 1</p>
                        <span class="text-green-600 font-bold">$100</span>
                    </div>
                    <div class="h-[400px] bg-gray-800 rounded-[10px] p-2 flex-col">
                        <img src="../recursos/productos/huron.jpg" alt="Otro producto más"
                            class="w-full h-[300px] object-cover rounded mb-2">
                        <p class="text-lg text-white font-semibold">Producto Ejemplo 2</p>
                        <span class="text-green-600 font-bold">$150</span>
                    </div>
                </div>
            </section>

        <?php endif; ?>

        <div id="modalAnadirLista" class="modal">
            <div class="modal-content">
                <span class="modal-close-btn" id="modalCloseBtnLista">&times;</span>
                <h3 class="text-xl font-semibold mb-4">Selecciona una lista</h3>
                <div id="contenedorListasModal" class="mb-4 max-h-60 overflow-y-auto">
                    <p>Cargando listas...</p>
                </div>
                <p id="mensajeErrorListaModal" class="text-red-500 text-sm mb-2"></p>
                <button id="crear-lista" onclick="window.location.href='mislistas.php'"
                    class="text-sm text-blue-500 hover:underline mb-4">Crear nueva lista</button>
                <div>
                    <button id="btnConfirmarAnadirLista"
                        class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">Añadir a
                        seleccionada</button>
                </div>
            </div>
        </div>

        <div id="notificationToast" class="notification-toast">Mensaje de notificación</div>


        <script src="../js/script_product.js"></script>
        <script>
            // Asegúrate que estas variables globales se definan correctamente
            let isUserLoggedInGlobal = false; // Se determinará al cargar
            const currentProductIdGlobal = new URLSearchParams(window.location.search).get('id');

            const btnAnadirLista = document.getElementById('btnAnadirLista');
            const modalAnadirLista = document.getElementById('modalAnadirLista');
            const modalCloseBtnLista = document.getElementById('modalCloseBtnLista');
            const contenedorListasModal = document.getElementById('contenedorListasModal');
            const btnConfirmarAnadirLista = document.getElementById('btnConfirmarAnadirLista');
            const mensajeErrorListaModal = document.getElementById('mensajeErrorListaModal');

            const btnAnadirCarrito = document.getElementById('btnAnadirCarrito'); // Asegúrate que este ID es correcto
            const notificationToast = document.getElementById('notificationToast');
            let toastTimeout;

            function showToast(message, type = 'info') {
                if (!notificationToast) return;
                notificationToast.textContent = message;
                notificationToast.className = 'notification-toast show'; // Base classes
                if (type === 'success') {
                    notificationToast.classList.add('success');
                } else if (type === 'error') {
                    notificationToast.classList.add('error');
                }

                clearTimeout(toastTimeout);
                toastTimeout = setTimeout(() => {
                    notificationToast.classList.remove('show');
                }, 3000);
            }

            // Función para actualizar el estado visual y la acción del botón del carrito
            function actualizarBotonCarrito(enCarrito) {
                if (!btnAnadirCarrito) return;

                if (enCarrito) {
                    btnAnadirCarrito.textContent = 'Eliminar del carrito';
                    btnAnadirCarrito.classList.remove('bg-[#ffae00]', 'hover:bg-[#ff9d00]');
                    btnAnadirCarrito.classList.add('bg-red-500', 'hover:bg-red-700');
                    btnAnadirCarrito.dataset.action = 'remove_from_cart';
                } else {
                    btnAnadirCarrito.textContent = 'Añadir al carrito';
                    btnAnadirCarrito.classList.remove('bg-red-500', 'hover:bg-red-700');
                    btnAnadirCarrito.classList.add('bg-[#ffae00]', 'hover:bg-[#ff9d00]');
                    btnAnadirCarrito.dataset.action = 'add_to_cart';
                }
            }

            // Función para verificar el estado del producto en el carrito (y el login)
            async function inicializarPaginaProducto() {
                try {
                    // Paso 1: Siempre determinar el estado de login primero
                    const loginCheckResponse = await fetch('../modelos/obtenerlistas.php');
                    const loginData = await loginCheckResponse.json();
                    isUserLoggedInGlobal = loginData.is_logged_in || false;

                    // Paso 2: Si el botón del carrito existe (producto no es solo de cotizar),
                    // y el usuario está logueado, entonces verificar su estado.
                    if (btnAnadirCarrito) { // btnAnadirCarrito es null si el producto es de cotizar
                        if (isUserLoggedInGlobal && currentProductIdGlobal) {
                            btnAnadirCarrito.style.display = 'inline-block'; // Asegurar que sea visible

                            const cartStatusResponse = await fetch(`../controladores/CarritoController.php?action=check_status&id_producto=${currentProductIdGlobal}`);
                            const cartData = await cartStatusResponse.json();

                            if (cartData.success) {
                                actualizarBotonCarrito(cartData.in_cart);
                            } else {
                                console.warn("Advertencia al verificar estado del carrito:", cartData.message);
                                actualizarBotonCarrito(false);
                            }
                        } else if (!isUserLoggedInGlobal) {
                            btnAnadirCarrito.style.display = 'inline-block'; // Mostrarlo para que el usuario vea "Añadir"
                            actualizarBotonCarrito(false); // Y al hacer clic, el backend pedirá login
                        } else {
                            btnAnadirCarrito.style.display = 'none'; // Ocultar si no hay ID de producto
                        }
                    }

                } catch (error) {
                    console.error('Error crítico al inicializar la página del producto:', error);
                    isUserLoggedInGlobal = false; // Asumir no logueado por seguridad
                    if (btnAnadirCarrito) actualizarBotonCarrito(false);
                }
            }

            // Event Listener para "Añadir a Lista" (revisar la parte del login)
            if (btnAnadirLista) {
                btnAnadirLista.addEventListener('click', async () => {
                    if (!currentProductIdGlobal) {
                        showToast("Error: No se pudo identificar el producto.", "error");
                        return;
                    }

                    // isUserLoggedInGlobal ya debería estar seteado por inicializarPaginaProducto()
                    if (!isUserLoggedInGlobal) {
                        showToast("Debes iniciar sesión para añadir a una lista.", "error");
                        // showLoginModal(); // Si tienes un modal de login global
                        return;
                    }

                    mensajeErrorListaModal.textContent = '';
                    contenedorListasModal.innerHTML = '<p>Cargando listas...</p>';
                    modalAnadirLista.style.display = 'block';

                    try {
                        const response = await fetch('../modelos/obtenerlistas.php'); // Esta llamada es para obtener las listas
                        const data = await response.json();
                        console.log(data);
                        // No es necesario volver a setear isUserLoggedInGlobal aquí si ya se hizo en inicializarPaginaProducto
                        // a menos que quieras una re-verificación, pero no debería ser el problema principal.

                        if (!data.is_logged_in) { // Chequeo por si acaso, pero el global debería ser la fuente de verdad
                            contenedorListasModal.innerHTML = `<p class="text-red-500">${data.message || 'Debes iniciar sesión.'}</p>`;
                            btnConfirmarAnadirLista.style.display = 'none';
                            return;
                        }

                        // ... (resto de la lógica para poblar el modal de listas como la tenías) ...
                        if (data.success && data.listas) {
                            contenedorListasModal.innerHTML = '';
                            if (data.listas.length > 0) {
                                console.log("Entrando al bucle para renderizar listas. Cantidad:", data.listas.length); // LOG ADICIONAL
                                data.listas.forEach((lista, index) => {
                                    console.log(`Renderizando lista ${index}: ID=${lista.id_lista}, Nombre=${lista.nombre_lista}`); // LOG ADICIONAL

                                    const div = document.createElement('div');
                                    div.classList.add('mb-2'); // Clase de Tailwind para margen inferior

                                    // Crear el label
                                    const label = document.createElement('label');
                                    label.classList.add('flex', 'items-center', 'cursor-pointer'); // Clases para el label

                                    // Crear el input radio
                                    const inputRadio = document.createElement('input');
                                    inputRadio.type = 'radio';
                                    inputRadio.name = 'lista_seleccionada'; // Nombre común para que solo uno pueda ser seleccionado
                                    inputRadio.value = lista.id_lista;
                                    inputRadio.classList.add('mr-2'); // Margen a la derecha

                                    // Crear el texto del nombre de la lista
                                    const nombreListaTexto = document.createTextNode(lista.nombre_lista);

                                    // Ensamblar: input y texto dentro del label, label dentro del div
                                    label.appendChild(inputRadio);
                                    label.appendChild(nombreListaTexto);
                                    div.appendChild(label);

                                    contenedorListasModal.appendChild(div); // Añadir el div completo al contenedor
                                    console.log("Elemento de lista añadido al DOM:", div); // LOG ADICIONAL
                                });
                                btnConfirmarAnadirLista.style.display = 'inline-block';
                            } else {
                                // Si data.listas está vacío pero success es true
                                contenedorListasModal.innerHTML = `<p>${data.message || 'No tienes listas creadas. Puedes crear una desde tu perfil.'}</p>`;
                                btnConfirmarAnadirLista.style.display = 'none';
                            }
                        } else {
                            contenedorListasModal.innerHTML = `<p class="text-red-500">${data.message || 'Error al cargar listas.'}</p>`;
                            btnConfirmarAnadirLista.style.display = 'none';
                        }
                    } catch (error) { /* ... manejo de error ... */ }
                });
            }

            // ... (código para cerrar modal de listas y confirmar añadir a lista, usa currentProductIdGlobal)
            if (modalCloseBtnLista) {
                modalCloseBtnLista.addEventListener('click', () => modalAnadirLista.style.display = 'none');
            }
            window.addEventListener('click', (event) => {
                if (event.target == modalAnadirLista) modalAnadirLista.style.display = 'none';
            });
            if (btnConfirmarAnadirLista) {
                btnConfirmarAnadirLista.addEventListener('click', async () => {
                    // ... (código existente para confirmar añadir a lista, sin cambios grandes)
                    if (!isUserLoggedInGlobal) {
                        showToast("Debes iniciar sesión.", "error"); return;
                    }
                    const listaSeleccionada = document.querySelector('input[name="lista_seleccionada"]:checked');
                    if (!listaSeleccionada) {
                        mensajeErrorListaModal.textContent = 'Por favor, selecciona una lista.'; return;
                    }
                    mensajeErrorListaModal.textContent = '';
                    const idLista = listaSeleccionada.value;
                    const formData = new FormData();
                    formData.append('id_producto', currentProductIdGlobal);
                    formData.append('id_lista', idLista);
                    try {
                        const response = await fetch('../modelos/agregar_a_lista.php', { method: 'POST', body: formData });
                        const textResponse = await response.text();
                        if (response.ok && textResponse.toLowerCase().includes('exitosamente')) {
                            showToast(textResponse, "success");
                        } else {
                            showToast(textResponse || `Error del servidor: ${response.statusText}`, "error");
                        }
                        modalAnadirLista.style.display = 'none';
                    } catch (error) {
                        showToast("Error de conexión al añadir a la lista.", "error");
                        modalAnadirLista.style.display = 'none';
                    }
                });
            }


            // --- Lógica para el botón de "Añadir/Eliminar del Carrito" ---
            if (btnAnadirCarrito) {
                btnAnadirCarrito.addEventListener('click', async () => {
                    if (!isUserLoggedInGlobal) {
                        showToast("Debes iniciar sesión para modificar tu carrito.", "error");
                        showLoginModal();
                        return;
                    }
                    if (!currentProductIdGlobal) {
                        showToast("Error: No se pudo identificar el producto.", "error");
                        return;
                    }

                    const currentAction = btnAnadirCarrito.dataset.action; // 'add_to_cart' o 'remove_from_cart'

                    const formData = new FormData();
                    formData.append('id_producto', currentProductIdGlobal);
                    formData.append('action', currentAction);
                    // La cantidad (1) solo es relevante para 'add_to_cart' y el backend la asume si no se envía.

                    btnAnadirCarrito.disabled = true; // Prevenir doble clic

                    try {
                        const response = await fetch('../controladores/CarritoController.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();

                        if (data.success) {
                            showToast(data.message, "success");
                            actualizarBotonCarrito(data.in_cart);
                        } else {
                            showToast(data.message || `Error al ${currentAction === 'add_to_cart' ? 'añadir' : 'eliminar'} del carrito.`, "error");
                            // Si falla, es buena idea re-verificar el estado actual del carrito para asegurar que el botón esté correcto.
                            inicializarPaginaProducto(); // Llama a la función que ya hace esto.
                        }
                    } catch (error) {
                        console.error(`Error en la acción '${currentAction}' del carrito:`, error);
                        showToast("Error de conexión al modificar el carrito.", "error");
                        inicializarPaginaProducto(); // Re-sincronizar el botón en caso de error de red
                    } finally {
                        btnAnadirCarrito.disabled = false; // Reactivar el botón
                    }
                });
            }

            // Ejecutar la inicialización cuando el DOM esté completamente cargado
            document.addEventListener('DOMContentLoaded', inicializarPaginaProducto);

        </script>
    </div>
</body>

</html>