<?php
if (session_status() ===  PHP_SESSION_NONE) {
    session_start();
}

// Autenticación y obtención de categorías (como en tu código original)
// ... (código para verificar $_SESSION['id_usuario'] y obtener $categorias) ...
// Si el usuario no está logueado y es una petición POST (ajax)
if (!isset($_SESSION['id_usuario'])) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'mensaje' => 'Debes iniciar sesión para publicar un producto.']);
        exit(); // Salir para peticiones POST no autenticadas
    }
    // Para peticiones GET no autenticadas, podrías redirigir o mostrar un mensaje.
    die('Debes iniciar sesión para publicar un producto.');
}


require_once '../modelos/conexion.php';
$db = new Database();
$conexion = $db->getConexion();


// Obtener categorías existentes para el formulario (solo para GET, el POST no lo necesita directamente)
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $queryCategorias = "SELECT id_categoria, NombreCategoria FROM Categoria";
    $resultCategorias = mysqli_query($conexion, $queryCategorias);
    $categorias = [];
    if ($resultCategorias) {
        while ($row = mysqli_fetch_assoc($resultCategorias)) {
            $categorias[] = $row;
        }
    }
}


// --- Inicio del procesamiento de la solicitud POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json'); // Muy importante para la respuesta AJAX
    
    // Validaciones básicas de campos
    if (empty($_POST['nombre']) || empty($_POST['descripcion']) || empty($_POST['tipo']) || !isset($_POST['inventario']) || empty($_POST['categoria'])) {
        echo json_encode(['success' => false, 'mensaje' => 'Faltan campos obligatorios.']);
        if ($conexion) mysqli_close($conexion);
        exit();
    }

    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $precio = null; // Por defecto NULL

    if ($_POST['tipo'] === 'vender') {
        if (!isset($_POST['precio']) || $_POST['precio'] === '' || !is_numeric($_POST['precio']) || floatval($_POST['precio']) < 0) {
            echo json_encode(['success' => false, 'mensaje' => 'El precio es inválido o no fue proporcionado para el tipo "Vender".']);
            if ($conexion) mysqli_close($conexion);
            exit();
        }
        $precio = floatval($_POST['precio']);
    }
    
    if (!is_numeric($_POST['inventario']) || intval($_POST['inventario']) < 0) {
        echo json_encode(['success' => false, 'mensaje' => 'El inventario debe ser un número válido y no negativo.']);
        if ($conexion) mysqli_close($conexion);
        exit();
    }
    $inventario = intval($_POST['inventario']);
    $tipo = mysqli_real_escape_string($conexion, $_POST['tipo']);
    $fecha = date('Y-m-d H:i:s');
    $usuarioId = $_SESSION['id_usuario'];
    $categoriaSeleccionadaInput = $_POST['categoria'];
    $id_categoria_final = null;

    // Manejo de Categoría
    if ($categoriaSeleccionadaInput == "nueva") {
        if (empty($_POST['nuevaCategoria'])) {
            echo json_encode(['success' => false, 'mensaje' => 'El nombre de la nueva categoría no puede estar vacío.']);
            if ($conexion) mysqli_close($conexion);
            exit();
        }
        $nuevaCategoriaNombre = mysqli_real_escape_string($conexion, $_POST['nuevaCategoria']);
        $descripcionCategoria = isset($_POST['descripcionCategoria']) ? mysqli_real_escape_string($conexion, $_POST['descripcionCategoria']) : '';

        $stmtNuevaCat = $conexion->prepare("INSERT INTO Categoria (NombreCategoria, Descripcion, id_usuario) VALUES (?, ?, ?)");
        if (!$stmtNuevaCat) {
            echo json_encode(['success' => false, 'mensaje' => 'Error DB (prep nueva cat): ' . $conexion->error]);
            if ($conexion) mysqli_close($conexion);
            exit();
        }
        $stmtNuevaCat->bind_param("ssi", $nuevaCategoriaNombre, $descripcionCategoria, $usuarioId);
        
        if ($stmtNuevaCat->execute()) {
            $id_categoria_final = $stmtNuevaCat->insert_id;
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error DB (exec nueva cat): ' . $stmtNuevaCat->error]);
            $stmtNuevaCat->close();
            if ($conexion) mysqli_close($conexion);
            exit();
        }
        $stmtNuevaCat->close();
    } else {
        $id_categoria_final = (int)$categoriaSeleccionadaInput;
        if ($id_categoria_final <= 0) {
             echo json_encode(['success' => false, 'mensaje' => 'Categoría seleccionada inválida.']);
             if ($conexion) mysqli_close($conexion);
             exit();
        }
    }

    // Inserción del Producto
    $stmt = $conexion->prepare("INSERT INTO Producto (Nombre, Descripcion, Precio, Inventario, Valoracion, id_categoria, id_vendedor, Estado, Tipo, FechaCreacion) VALUES (?, ?, ?, ?, 0.0, ?, ?, 'Pendiente', ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'mensaje' => 'Error DB (prep producto): ' . $conexion->error]);
        if ($conexion) mysqli_close($conexion);
        exit();
    }
    $stmt->bind_param("ssdiiiss", $nombre, $descripcion, $precio, $inventario, $id_categoria_final, $usuarioId, $tipo, $fecha);

    if ($stmt->execute()) {
        $idProducto = $stmt->insert_id;
        $stmt->close();

        $multimediaSubidaExitosamente = true;
        $mensajeErrorMultimedia = '';
        $uploadDirectory = "../recursos/productos/";
        
        if (!is_dir($uploadDirectory)) {
            if (!@mkdir($uploadDirectory, 0775, true)) {
                $multimediaSubidaExitosamente = false;
                $mensajeErrorMultimedia = "Error: No se pudo crear el directorio de subida: $uploadDirectory";
            }
        }

        if ($multimediaSubidaExitosamente) { // Solo proceder si el directorio está OK
            if (isset($_FILES['multimedia']) && is_array($_FILES['multimedia']['name']) && count(array_filter($_FILES['multimedia']['name'])) > 0 ) {
                $imageCount = 0;
                $videoCount = 0;
                $validFileIndices = []; // Para almacenar los índices de los archivos válidos

                $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $allowedVideoExtensions = ['mp4', 'webm', 'ogv', 'avi', 'mov'];

                // --- Primera pasada: Validación de errores individuales, tipos y conteo inicial ---
                for ($i = 0; $i < count($_FILES['multimedia']['name']); $i++) {
                    if ($_FILES['multimedia']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                        continue; // No se subió archivo en este slot
                    }
                    
                    if ($_FILES['multimedia']['error'][$i] !== UPLOAD_ERR_OK) {
                        $multimediaSubidaExitosamente = false;
                        $mensajeErrorMultimedia = "Error al subir el archivo " . htmlspecialchars($_FILES['multimedia']['name'][$i]) . ": Código de error " . $_FILES['multimedia']['error'][$i];
                        break; // Salir del bucle si un archivo tiene error
                    }

                    $fileName = basename($_FILES['multimedia']['name'][$i]);
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    if (in_array($fileExtension, $allowedImageExtensions)) {
                        $imageCount++;
                        $validFileIndices[] = $i;
                    } elseif (in_array($fileExtension, $allowedVideoExtensions)) {
                        $videoCount++;
                        $validFileIndices[] = $i;
                    } else {
                        $multimediaSubidaExitosamente = false;
                        $mensajeErrorMultimedia = "Extensión de archivo no permitida: " . htmlspecialchars($fileName);
                        break; // Salir si una extensión no es válida
                    }
                }

                // --- Chequeo de conteos mínimos (solo si no hubo errores previos en la primera pasada) ---
                if ($multimediaSubidaExitosamente) {
                    if ($imageCount < 3 || $videoCount < 1) {
                        $multimediaSubidaExitosamente = false;
                        $errorParts = [];
                        if ($imageCount < 3) $errorParts[] = "se requieren al menos 3 imágenes (subidas: $imageCount)";
                        if ($videoCount < 1) $errorParts[] = "se requiere al menos 1 video (subido: $videoCount)";
                        $mensajeErrorMultimedia = "No se cumplen los requisitos de archivos: " . implode(" y ", $errorParts) . ".";
                    }
                }
                
                // --- Segunda pasada: Procesamiento de archivos (solo si todo OK hasta ahora) ---
                if ($multimediaSubidaExitosamente) {
                    foreach ($validFileIndices as $i) {
                        $fileName = basename($_FILES['multimedia']['name'][$i]);
                        $fileTmpName = $_FILES['multimedia']['tmp_name'][$i];
                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        
                        $newFileName = "producto_" . $idProducto . "_" . uniqid() . "." . $fileExtension;
                        $destinationPath = $uploadDirectory . $newFileName;
                        $urlParaBd = "recursos/productos/" . $newFileName;

                        if (move_uploaded_file($fileTmpName, $destinationPath)) {
                            $stmtMultimedia = $conexion->prepare("INSERT INTO MultimediaProducto (id_producto, URL) VALUES (?, ?)");
                            if(!$stmtMultimedia) {
                                $multimediaSubidaExitosamente = false;
                                $mensajeErrorMultimedia = "Error DB (prep multimedia): " . $conexion->error;
                                break;
                            }
                            $stmtMultimedia->bind_param("is", $idProducto, $urlParaBd);
                            if (!$stmtMultimedia->execute()) {
                                $multimediaSubidaExitosamente = false;
                                $mensajeErrorMultimedia = "Error DB (exec multimedia): " . $stmtMultimedia->error;
                                $stmtMultimedia->close();
                                break; 
                            }
                            $stmtMultimedia->close();
                        } else {
                            $multimediaSubidaExitosamente = false;
                            $mensajeErrorMultimedia = "Falló al mover el archivo subido: " . htmlspecialchars($fileName);
                            break;
                        }
                    }
                }
            } else { // No se seleccionaron archivos o el campo 'multimedia' no vino como se esperaba
                $multimediaSubidaExitosamente = false;
                $mensajeErrorMultimedia = "Se requieren al menos 3 imágenes y 1 video. No se seleccionó ningún archivo o el formato es incorrecto.";
            }
        } // Fin if ($multimediaSubidaExitosamente) después de mkdir

        if ($multimediaSubidaExitosamente) {
            echo json_encode(['success' => true, 'mensaje' => '¡Producto publicado exitosamente! Está pendiente de aprobación por un administrador.']);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Producto base creado, pero hubo un error con la subida/guardado de la multimedia: ' . $mensajeErrorMultimedia]);
        }
    } else { // Error al ejecutar $stmt (inserción de producto)
        $stmtError = $stmt->error;
        $stmt->close();
        echo json_encode(['success' => false, 'mensaje' => 'Error al publicar producto (DB): ' . $stmtError]);
    }
    
    if ($conexion) mysqli_close($conexion);
    exit(); // Terminar el script después de enviar la respuesta JSON
}
// --- Fin del procesamiento de la solicitud POST ---


// Si la solicitud no es POST, se muestra el HTML del formulario.
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Crear Publicación</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #preview .preview-item { display: inline-block; margin: 5px; position: relative; }
        #preview .preview-item img, #preview .preview-item video { width: 100px; height: 100px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; }
        #preview .remove-file { position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; cursor: pointer; width: 20px; height: 20px; text-align: center; line-height: 18px; font-size: 12px; z-index:10; }
        #preview .video-thumbnail-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; pointer-events: none;}
        #preview .play-icon-preview { color: white; font-size: 24px; }
    </style>
</head>

<body class="bg-orange-100">

    <?php include 'navbar.php'; ?>

    <div id="main-content" class="flex-1 transition-all duration-300 p-6 bg-orange-100 min-h-screen">
        <div id="form-status-message" class="mb-4 max-w-2xl mx-auto"></div> 
        <div class="max-w-2xl mx-auto p-6 bg-white shadow-lg rounded-xl">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Crear Publicación de Producto</h2>
            
            <form action="altaproducto.php" method="POST" id="formPublicacion" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <span class="block text-sm font-medium text-gray-700 mb-2">Tipo de Publicación</span>
                    <div class="flex items-center space-x-6">
                        <label class="inline-flex items-center">
                            <input type="radio" name="tipo" value="vender" checked onchange="togglePrecioRequerido()"
                                class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded">
                            <span class="ml-2 text-gray-700">Vender Directamente</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="tipo" value="cotizar" onchange="togglePrecioRequerido()"
                                class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded">
                            <span class="ml-2 text-gray-700">Solo para Cotizar</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="nombre-producto" class="block text-sm font-medium text-gray-700">Nombre del Producto*</label>
                    <input type="text" name="nombre" id="nombre-producto" required maxlength="100"
                        class="p-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción*</label>
                    <textarea name="descripcion" id="descripcion" rows="4" required
                        class="p-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-orange-500 focus:border-orange-500"
                        style="min-height: 60px; max-height: 250px;"></textarea>
                </div>

                <div>
                    <label for="categoria" class="block text-sm font-medium text-gray-700 mt-4">Categoría*</label>
                    <select name="categoria" id="categoria" onchange="toggleNuevaCategoria()" required
                        class="p-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-orange-500 focus:border-orange-500">
                        <option value="" disabled selected>Selecciona una categoría</option>
                        <option value="nueva" class="font-bold text-orange-600">+ Agregar nueva categoría</option>
                        <?php if(isset($categorias) && !empty($categorias)): ?>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>"><?= htmlspecialchars($categoria['NombreCategoria']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    
                    <div id="nuevaCategoriaCampos" class="mt-2 space-y-2 hidden">
                        <input type="text" name="nuevaCategoria" placeholder="Nombre de la nueva categoría"
                            class="p-2 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-orange-500 focus:border-orange-500"
                            minlength="3" maxlength="30">
                        <textarea name="descripcionCategoria" placeholder="Descripción de la nueva categoría (opcional)"
                            class="p-2 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-orange-500 focus:border-orange-500"
                            rows="2" style="min-height: 40px; max-height: 150px;"></textarea>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-6">
                    <div class="flex-1">
                        <label for="precio" class="block text-sm font-medium text-gray-700">Precio (MXN)*</label>
                        <input type="number" name="precio" id="precio" step="0.01" min="0" 
                            class="p-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-orange-500 focus:border-orange-500">
                        <small id="precioHelp" class="text-xs text-gray-500">Obligatorio si es para "Vender".</small>
                    </div>

                    <div class="flex-1">
                        <label for="inventario" class="block text-sm font-medium text-gray-700">Existencias (Inventario)*</label>
                        <input type="number" name="inventario" id="inventario" step="1" min="0" required
                            class="p-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>

                <div>
                    <label for="multimediaInput" class="block text-sm font-medium text-gray-700">Imágenes/Videos (Mín. 3 imágenes y 1 video, Máx. 8 archivos)</label>
                    <input type="file" name="multimedia[]" multiple id="multimediaInput" accept="image/*,video/*" 
                            class="p-1 mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4
                                   file:rounded-full file:border-0 file:text-sm file:font-semibold
                                   file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100
                                   focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <small class="text-xs text-gray-500">Formatos permitidos: JPG, PNG, GIF, WEBP, MP4, WEBM, MOV, AVI. Máx 10MB por archivo.</small>
                </div>
                
                <div id="preview" class="flex flex-wrap gap-4 mt-2 border p-2 rounded-md bg-gray-50 min-h-[110px]">
                </div>
                <small id="fileCountMsg" class="text-sm text-gray-500"></small>

                <div class="text-center pt-4">
                    <button type="submit"
                        class="inline-flex items-center px-8 py-3 rounded-lg bg-orange-500 text-white font-semibold hover:bg-orange-600 transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                        Publicar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const MAX_FILES = 8;
        const MAX_FILE_SIZE_MB = 100; 
        const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;
        let selectedFilesData = []; 

        function togglePrecioRequerido() {
            const tipoVender = document.querySelector('input[name="tipo"][value="vender"]');
            const precioInput = document.getElementById('precio');
            const precioHelp = document.getElementById('precioHelp');
            if (tipoVender.checked) {
                precioInput.required = true;
                precioInput.disabled = false;
                precioHelp.textContent = "Obligatorio si es para Vender.";
            } else { 
                precioInput.required = false;
                precioInput.disabled = true;
                precioInput.value = ''; 
                precioHelp.textContent = "El precio no es necesario para cotizaciones.";
            }
        }

        function toggleNuevaCategoria() {
            const selectCategoria = document.getElementById('categoria');
            const camposNuevaCategoria = document.getElementById('nuevaCategoriaCampos');
            const inputNombreNuevaCat = camposNuevaCategoria.querySelector('input[name="nuevaCategoria"]');
            
            if (selectCategoria.value === "nueva") {
                camposNuevaCategoria.classList.remove('hidden');
                inputNombreNuevaCat.required = true; 
            } else {
                camposNuevaCategoria.classList.add('hidden');
                inputNombreNuevaCat.required = false;
                inputNombreNuevaCat.value = ''; 
                camposNuevaCategoria.querySelector('textarea[name="descripcionCategoria"]').value = '';
            }
        }

        document.getElementById('multimediaInput').addEventListener('change', function(event) {
            const newFiles = Array.from(event.target.files);
            const previewContainer = document.getElementById('preview');
            
            let currentTotalFiles = selectedFilesData.length;

            newFiles.forEach(file => {
                if (currentTotalFiles >= MAX_FILES) {
                    alert(`Solo puedes subir un máximo de ${MAX_FILES} archivos.`);
                    return; 
                }
                if (file.size > MAX_FILE_SIZE_BYTES) {
                    alert(`El archivo "${file.name}" excede el tamaño máximo de ${MAX_FILE_SIZE_MB}MB.`);
                    return; 
                }

                selectedFilesData.push(file); 
                currentTotalFiles++;

                const reader = new FileReader();
                const filePreviewItem = document.createElement('div');
                filePreviewItem.className = 'preview-item w-24 h-24'; 
                
                const removeBtn = document.createElement('span');
                removeBtn.className = 'remove-file';
                removeBtn.innerHTML = '&times;';
                removeBtn.onclick = function() {
                    const indexToRemove = selectedFilesData.findIndex(f => f === file);
                    if (indexToRemove > -1) {
                        selectedFilesData.splice(indexToRemove, 1);
                    }
                    filePreviewItem.remove();
                    updateFileCountMsg();
                };
                filePreviewItem.appendChild(removeBtn);

                if (file.type.startsWith('image/')) {
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        filePreviewItem.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                } else if (file.type.startsWith('video/')) {
                    const video = document.createElement('video');
                    video.src = URL.createObjectURL(file); 
                    video.muted = true; 
                    filePreviewItem.appendChild(video);
                    const playOverlay = document.createElement('div');
                    playOverlay.className = 'video-thumbnail-overlay';
                    playOverlay.innerHTML = '<span class="play-icon-preview">&#9658;</span>'; 
                    filePreviewItem.appendChild(playOverlay);
                    video.onloadedmetadata = () => { 
                       video.currentTime = 0.1; 
                    };
                }
                previewContainer.appendChild(filePreviewItem);
            });
            updateFileCountMsg();
            event.target.value = ''; 
        });
        
        function updateFileCountMsg() {
            const count = selectedFilesData.length;
            let imageCount = 0;
            let videoCount = 0;
            selectedFilesData.forEach(file => {
                if (file.type.startsWith('image/')) imageCount++;
                else if (file.type.startsWith('video/')) videoCount++;
            });
            document.getElementById('fileCountMsg').textContent = 
                `${count} de ${MAX_FILES} archivos seleccionados. (Imágenes: ${imageCount}, Videos: ${videoCount})`;
        }

        document.getElementById('formPublicacion').addEventListener('submit', function(e) {
            e.preventDefault(); 
            const formStatusDiv = document.getElementById('form-status-message');
            formStatusDiv.innerHTML = ''; 
            const formData = new FormData(this); 
            
            formData.delete('multimedia[]'); 
            let imageCount = 0;
            let videoCount = 0;
            if (selectedFilesData.length > 0) {
                selectedFilesData.forEach(file => {
                    formData.append('multimedia[]', file, file.name);
                    if (file.type.startsWith('image/')) {
                        imageCount++;
                    } else if (file.type.startsWith('video/')) {
                        videoCount++;
                    }
                });
            }
            
            let isValid = true;
            let errorMessages = [];
            if (!formData.get('nombre').trim()) { errorMessages.push("El nombre del producto es obligatorio."); isValid = false; }
            if (!formData.get('descripcion').trim()) { errorMessages.push("La descripción es obligatoria."); isValid = false; }
            if (!formData.get('categoria')) { errorMessages.push("Debes seleccionar una categoría."); isValid = false; }
            if (formData.get('categoria') === 'nueva' && !formData.get('nuevaCategoria').trim()) {
                errorMessages.push("El nombre de la nueva categoría es obligatorio."); isValid = false;
            }
            if (formData.get('tipo') === 'vender' && (!formData.get('precio') || parseFloat(formData.get('precio')) <= 0)) {
                errorMessages.push("El precio es obligatorio y debe ser mayor a 0 para vender."); isValid = false;
            }
            if (!formData.get('inventario') || parseInt(formData.get('inventario')) < 0) {
                errorMessages.push("El inventario es obligatorio y no puede ser negativo."); isValid = false;
            }

            // Nueva validación de conteo de archivos multimedia
            if (imageCount < 3) {
                errorMessages.push("Debes subir al menos 3 imágenes.");
                isValid = false;
            }
            if (videoCount < 1) {
                errorMessages.push("Debes subir al menos 1 video.");
                isValid = false;
            }
            // (Opcional) Si no se subió ningún archivo y las condiciones de arriba no se cumplen:
            // if (selectedFilesData.length === 0 && (imageCount < 3 || videoCount < 1)) {
            //    errorMessages.push("Debes subir al menos 3 imágenes y 1 video.");
            //    isValid = false;
            // }


            if (!isValid) {
                formStatusDiv.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                               <strong class="font-bold">Por favor corrige los errores:</strong>
                                               <ul class="list-disc ml-5 mt-2">${errorMessages.map(msg => `<li>${msg}</li>`).join('')}</ul>
                                           </div>`;
                return;
            }

            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="animate-pulse">Publicando...</span>';

            fetch(this.action, {
                method: 'POST',
                body: formData 
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error del servidor: ${response.status} ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    formStatusDiv.innerHTML = `<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                                                   <strong class="font-bold">¡Éxito!</strong>
                                                   <span class="block sm:inline">${data.mensaje}</span>
                                               </div>`;
                    this.reset(); 
                    selectedFilesData = []; 
                    document.getElementById('preview').innerHTML = ''; 
                    updateFileCountMsg();
                    togglePrecioRequerido(); 
                    toggleNuevaCategoria(); 
                } else {
                    formStatusDiv.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                                   <strong class="font-bold">Error:</strong>
                                                   <span class="block sm:inline">${data.mensaje || 'Ocurrió un error desconocido.'}</span>
                                               </div>`;
                }
            })
            .catch(error => {
                console.error('Error en el envío del formulario:', error);
                formStatusDiv.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                               <strong class="font-bold">Error de Conexión:</strong>
                                               <span class="block sm:inline">No se pudo conectar con el servidor. Intenta de nuevo. Detalles: ${error.message}</span>
                                           </div>`;
            })
            .finally(() => {
                 submitButton.disabled = false;
                 submitButton.innerHTML = 'Publicar Producto';
            });
        });

        togglePrecioRequerido();
        toggleNuevaCategoria();
        updateFileCountMsg();
    </script>

</body>
</html>

<?php
// Cerrar conexión si se abrió y no es POST (para la carga inicial del formulario con categorías)
if ($_SERVER["REQUEST_METHOD"] != "POST" && isset($conexion)) {
    mysqli_close($conexion);
}
?>