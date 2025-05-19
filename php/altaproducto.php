<?php
if (session_status() ===  PHP_SESSION_NONE) {
    session_start(); 
}

// Verifica si el usuario está en sesión
if (!isset($_SESSION['id_usuario'])) {
    die('Debes iniciar sesión para publicar un producto.');
}

require_once '../modelos/conexion.php'; // Tu conexión a MySQL
$db = new Database();
$conexion = $db->getConexion();
// Obtener categorías existentes
$queryCategorias = "SELECT id_categoria, NombreCategoria FROM Categoria";
$resultCategorias = mysqli_query($conexion, $queryCategorias);
$categorias = [];
while ($row = mysqli_fetch_assoc($resultCategorias)) {
    $categorias[] = $row;
}

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $precio = isset($_POST['precio']) && $_POST['precio'] !== '' ? floatval($_POST['precio']) : NULL;
    $inventario = intval($_POST['inventario']);
    $tipo = mysqli_real_escape_string($conexion, $_POST['tipo']);
    $fecha = date('Y-m-d H:i:s'); // Fecha actual
    $usuarioId = $_SESSION['id_usuario'];

    $categoriaSeleccionada = $_POST['categoria'];

   /* if ($categoriaSeleccionada == "nueva") {
        $nuevaCategoria = mysqli_real_escape_string($conexion, $_POST['nuevaCategoria']);
        $descripcionCategoria = mysqli_real_escape_string($conexion, $_POST['descripcionCategoria']);

       // Insertar nueva categoría
       // $stmt = $conexion->prepare("INSERT INTO Categoria (NombreCategoria, Descripcion, id_usuario) VALUES (?, ?, ?)");
      //  $stmt->bind_param("ssi", $nuevaCategoria, $descripcionCategoria, $usuarioId);
        //if (!$stmt->execute()) {
    // ANTES: die('Error al crear la nueva categoría: ' . $stmt->error);
    // DESPUÉS:
    //echo json_encode(['success' => false, 'mensaje' => 'Error al crear la nueva categoría: ' . $stmt->error]);
    //exit();
//}

        // Obtener el ID de la nueva categoría creada
        $categoriaSeleccionada = mysqli_insert_id($conexion);
    }*/

    // Insertar el producto
    $stmt = $conexion->prepare("INSERT INTO Producto 
    (Nombre, Descripcion, Precio, Inventario, Valoracion, id_categoria, id_vendedor, Estado, Tipo, FechaCreacion)
    VALUES (?, ?, ?, ?, 0.0, ?, ?, 'Pendiente', ?, ?)");

    $stmt->bind_param(
        "ssdiiiss", // Tipos de datos: s=string, d=double, i=integer
        $nombre,
        $descripcion,
        $precio,
        $inventario,
        $categoriaSeleccionada,
        $usuarioId,
        $tipo,
        $fecha
    );

    if ($stmt->execute()) {
        $idProducto = mysqli_insert_id($conexion); // ID del producto recién creado

        // Procesar las imágenes
        if (isset($_FILES['multimedia']) && is_array($_FILES['multimedia']['name']) && count($_FILES['multimedia']['name']) > 0) {
            $total = count($_FILES['multimedia']['name']);
            $uploadDirectory = "../recursos/productos/"; // Carpeta donde guardaremos las imágenes

            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0777, true); // Crear carpeta si no existe
            }

            for ($i = 0; $i < $total; $i++) {
                $fileName = basename($_FILES['multimedia']['name'][$i]);
                $fileTmpName = $_FILES['multimedia']['tmp_name'][$i];
                $fileType = $_FILES['multimedia']['type'][$i];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Validar que sea una imagen o video
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp','mp4', 'webm','ogv', 'avi', 'mov'];

                if (in_array($fileExtension, $allowedExtensions)) {


                    $newFileName = uniqid() . "." . $fileExtension;
                    $destination = $uploadDirectory . $newFileName;

                    if ($_FILES['multimedia']['error'][$i] === UPLOAD_ERR_OK) {
                        if (move_uploaded_file($fileTmpName, $destination)) {


                            $stmtMultimedia = $conexion->prepare("INSERT INTO MultimediaProducto (id_producto, URL) VALUES (?, ?)");
                            $stmtMultimedia->bind_param("is", $idProducto, $destination);
                            $ProductPath = "<div class='text-green-600'>Archivo guardado en: $destination</div>";

                            if (!$stmtMultimedia->execute()) {
                                $ProductPath = "<div class='text-red-500'>Error en MultimediaProducto: " . $stmtMultimedia->error . "</div>";
                            }
                        } else {
                            $ProductPath = "<div class='text-red-500'>Falló move_uploaded_file para $fileName</div>";
                            echo json_encode(['success' => false, 'mensaje' => $ProductPath]);
                            exit();

                        }
                    } else {
                        $ProductPath = "<div class='text-red-500'>Error al subir archivo " . $_FILES['multimedia']['name'][$i] . ": " . $_FILES['multimedia']['error'][$i] . "</div>";
                        echo json_encode(['success' => false, 'mensaje' => $ProductPath]);
                        exit();
                    }

                }
            }
        }
        $ProductUploadStatus = "<div class='bg-green-400 text-orange-100 text-center mt-2'>¡Producto publicado exitosamente!</div>";
        echo json_encode(['success' => true, 'mensaje' => $ProductUploadStatus]);
        exit();

    } else {
        $ProductUploadStatus = "<div class='bg-red-400  text-orange-100 text-center mt-2'>Error al publicar producto: " . mysqli_error($conexion) . "</div>";
        echo json_encode(['success' => false, 'mensaje' => $ProductUploadStatus]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Crear Publicación</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-orange-100">

    <?php include 'navbar.php'; ?>

    <!-- Asegúrate de tener Tailwind CSS cargado en tu proyecto -->
    <div id="from-status"></div>
    <div class="max-w-2xl mx-auto p-6 bg-white shadow-lg rounded-xl mt-10">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Crear Publicación de Producto</h2>
        <form action="altaproducto.php" method="POST" id="formPublicacion" enctype="multipart/form-data"
            class="space-y-4">
            <!-- Tipo: Vender o Cotizar -->
            <div>
                <span class="block text-sm font-medium text-gray-700 mb-2">Tipo</span>
                <div class="flex items-center space-x-6">
                    <label class="inline-flex items-center">
                        <input type="radio" name="tipo" value="vender" checked onchange="validarTipo()"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <span class="ml-2 text-gray-700">Vender</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="tipo" value="cotizar" onchange="validarTipo()"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <span class="ml-2 text-gray-700">Cotizar</span>
                    </label>
                </div>
            </div>

            <!-- Nombre -->
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" name="nombre" id="nombre-producto" required
                    class="p-1 mb-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Descripción -->
            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="3" required
                    class="p-1 mb-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    style="min-height: 34px; max-height: 200px;"></textarea>
            </div>

            <!-- Categoría -->
            <div>
                <label for="categoria" class="block text-sm font-medium text-gray-700 mt-4">Categoría</label>
                <select name="categoria" id="categoria" onchange="mostrarNuevaCategoria()" required
                    class="p-1 mb-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="" disabled selected>Selecciona una categoría</option>
                    <option value="nueva" class="font-bold">+ Agregar nueva categoría</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= $categoria['id_categoria'] ?>"><?= $categoria['NombreCategoria'] ?></option>
                    <?php endforeach; ?>
                    <!-- Opciones de categorías dinamicas-->
                </select>
                <!-- Campos para nueva categoría -->
                <div id="nuevaCategoriaCampos" class="col-span-2 hidden">
                    <input type="text" name="nuevaCategoria" placeholder="Nombre de la nueva categoría"
                        class=" p-1 mb-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        minlength="3">
                    <textarea name="descripcionCategoria" placeholder="Descripción de la nueva categoría"
                        class="p-1 mb-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        rows="3" style="min-height: 34px; max-height: 200px;"></textarea>
                </div>
            </div>

            <!-- Precio y Existencias en la misma fila -->
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Precio -->
                <div class="flex-1">
                    <label for="precio" class="block text-sm font-medium text-gray-700">Precio</label>
                    <input type="number" name="precio" id="precio" step="0.01" required
                        class="p-1 mb-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Existencias -->
                <div class="flex-1">
                    <label for="inventario" class="block text-sm font-medium text-gray-700">Existencias</label>
                    <input type="number" name="inventario" id="inventario" step="1" required
                        class="p-1 mb-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Imagen -->
            <div>
                <label for="multimedia[]" class="block text-sm font-medium text-gray-700">Imagenes/Videos</label>
                <input type="file" name="multimedia[]" multiple id="imagenesInput" accept="image/*,video/*" class="p-1 mb-2 mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                          file:rounded-full file:border-0
                          file:text-sm file:font-semibold
                          file:bg-blue-50 file:text-blue-700
                          hover:file:bg-blue-100" style="color: white;">
            </div>
            <!-- Previsualización de imágenes -->
            <div id="preview" class="flex flex-wrap gap-4 mt-4"></div>
            <small id="fileCountMsg" class="text-sm text-gray-500"></small>
            <!-- Botón -->
            <div class="text-center">
                <button type="submit"
                    class="inline-flex items-center px-6 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 transition">
                    Crear Publicación
                </button>
            </div>
    </div>
    </form>
    </div>


    <script>

        //------- Validaciones de formulario---------
        // Inhabilitar "precio" si el tipo es "cotizar" al cargar la página 
        addEventListener('DOMContentLoaded', function () {
            validarTipo();
        });

        function validarTipo() {
            const tipoInputs = document.querySelectorAll('input[name="tipo"]');
            const precioInput = document.getElementById('precio');
            tipoInputs.forEach(input => {
                if (input.checked && input.value === 'cotizar') {
                    precioInput.disabled = true;
                    precioInput.value = '';
                }else{
                    precioInput.disabled = false;
                }
            });
        }


        function mostrarNuevaCategoria() {
            const select = document.getElementById('categoria');
            const camposNuevaCategoria = document.getElementById('nuevaCategoriaCampos');

            if (select.value === "nueva") {
                camposNuevaCategoria.classList.remove('hidden');
                camposNuevaCategoria.querySelector('input[name="nuevaCategoria"]').required = true;
                camposNuevaCategoria.querySelector('textarea[name="descripcionCategoria"]').required = true;
            } else {
                camposNuevaCategoria.classList.add('hidden');
            }
        }


        // Imágenes y videos con opción de eliminar y portada para video
        let archivosSeleccionados = [];
        let idContador = 0;

        document.getElementById('imagenesInput').addEventListener('change', function (event) {
            const nuevosArchivos = Array.from(event.target.files);
            const preview = document.getElementById('preview');
            const totalPrevios = archivosSeleccionados.length;

            // Si la suma excede 8, cancela la operación
            if (totalPrevios + nuevosArchivos.length > 8) {
                alert("Máximo permitido: 8 archivos.");
                event.target.value = ''; // limpia la selección actual
                return;
            }

            nuevosArchivos.forEach(file => {
                const idArchivo = 'archivo_' + (idContador++);
                archivosSeleccionados.push({ id: idArchivo, file });

                const contenedor = document.createElement('div');
                contenedor.className = "relative inline-block mr-2 mb-2";
                contenedor.id = idArchivo;

                // Botón eliminar
                const btnEliminar = document.createElement('button');
                btnEliminar.innerText = '❌';
                btnEliminar.className = "absolute top-0 right-0 bg-red-200 text-red-600 rounded-full text-xs p-1";
                btnEliminar.onclick = function () {
                    archivosSeleccionados = archivosSeleccionados.filter(f => f.id !== idArchivo);
                    document.getElementById(idArchivo).remove();
                    actualizarContador();
                };

                // Si es imagen
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = "w-32 h-32 object-cover rounded border";
                        contenedor.appendChild(img);
                        contenedor.appendChild(btnEliminar);
                    };
                    reader.readAsDataURL(file);
                }

                // Si es video
                else if (file.type.startsWith('video/')) {
                    const video = document.createElement('video');
                    video.src = URL.createObjectURL(file);
                    video.className = "w-32 h-32 object-cover rounded border";
                    video.muted = true;
                    video.playsInline = true;
                    video.currentTime = 1;
                    video.onloadeddata = () => video.pause(); // capturar frame estático

                    const iconoPlay = document.createElement('div');
                    iconoPlay.className = "absolute inset-0 flex items-center justify-center";
                    iconoPlay.innerHTML = '<span class="text-white text-3xl bg-black bg-opacity-50 rounded-full p-2">▶️</span>';

                    contenedor.appendChild(video);
                    contenedor.appendChild(iconoPlay);
                    contenedor.appendChild(btnEliminar);
                }

                preview.appendChild(contenedor);
            });

            actualizarContador();
            event.target.value = ''; // Permitir volver a seleccionar los mismos archivos si se desea
        });

        function actualizarContador() {
            const imagenes = archivosSeleccionados.filter(f => f.file.type.startsWith('image/'));
            const videos = archivosSeleccionados.filter(f => f.file.type.startsWith('video/'));

            const msg = `Seleccionados: ${archivosSeleccionados.length} archivos (🖼️ ${imagenes.length} imagenes, 🎥 ${videos.length} video(s))`;
            document.getElementById('fileCountMsg').textContent = msg;
        }


        // Validar que al subir formulario, tenga al menos 3 imágenes y 1 video
        const formPublicacion = document.getElementById('formPublicacion');
        const formStatus = document.getElementById('from-status');
        formPublicacion.addEventListener('submit', function (e) {
            e.preventDefault(); // Detenemos envío normal

            const imagenes = archivosSeleccionados.filter(f => f.file.type.startsWith('image/'));
            const videos = archivosSeleccionados.filter(f => f.file.type.startsWith('video/'));


            if (imagenes.length < 3 || videos.length < 1) {
                alert("Debes subir al menos 3 imágenes y 1 video.");
                return;
            }

            const formData = new FormData(formPublicacion);

            // Agregar los archivos seleccionados al formData
            archivosSeleccionados.forEach((archivoObj) => {
                formData.append('multimedia[]', archivoObj.file); 
            });


            // Enviar con fetch()
            fetch(formPublicacion.action, {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        formStatus.innerHTML = data.mensaje;
                        formPublicacion.reset(); // Limpiar el formulario
                        archivosSeleccionados = []; // Limpiar la lista de archivos seleccionados
                        document.getElementById('preview').innerHTML = ''; // Limpiar la previsualización

                    } else {
                        alert("Error: " + data.mensaje);
                    }
                })
                .catch(err => console.error('Error al enviar:', err));
        });

    </script>


</body>

</html>