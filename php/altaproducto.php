<?php
if(!isset($_SESSION)){
    session_start(); // Asegúrate de tener la sesión iniciada
}
include('conexion.php'); // Tu conexión a MySQL

// Verifica si el usuario está en sesión
if (!isset($_SESSION['id_usuario'])) {
    die('Debes iniciar sesión para publicar un producto.');
}

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
    $precio = floatval($_POST['precio']);
    $inventario = intval($_POST['inventario']);
    $tipo = mysqli_real_escape_string($conexion, $_POST['tipo']);
    $fecha = date('Y-m-d H:i:s'); // Fecha actual
    $usuarioId = $_SESSION['id_usuario'];

    $categoriaSeleccionada = $_POST['categoria'];

    if ($categoriaSeleccionada == "nueva") {
        $nuevaCategoria = mysqli_real_escape_string($conexion, $_POST['nuevaCategoria']);
        $descripcionCategoria = mysqli_real_escape_string($conexion, $_POST['descripcionCategoria']);

        // Insertar nueva categoría
        $insertCategoria = "INSERT INTO Categoria (NombreCategoria, Descripcion, id_usuario) 
                            VALUES ('$nuevaCategoria', '$descripcionCategoria', $usuarioId)";
        mysqli_query($conexion, $insertCategoria);

        // Obtener el ID de la nueva categoría creada
        $categoriaSeleccionada = mysqli_insert_id($conexion);
    }

    // Insertar el producto
    $insertProducto = "INSERT INTO Producto 
        (Nombre, Descripcion, Precio, Inventario, Valoracion, id_categoria, id_vendedor, Estado, Tipo, FechaCreacion)
        VALUES 
        ('$nombre', '$descripcion', $precio, $inventario, 0.0, $categoriaSeleccionada, $usuarioId, 'Pendiente', '$tipo', '$fecha')";

    if (mysqli_query($conexion, $insertProducto)) {
        $idProducto = mysqli_insert_id($conexion); // ID del producto recién creado

        // Procesar las imágenes
        if (isset($_FILES['imagenes']) && count($_FILES['imagenes']['name']) > 0) {
            $total = count($_FILES['imagenes']['name']);
            $uploadDirectory = "uploads/"; // Carpeta donde guardaremos las imágenes

            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0777, true); // Crear carpeta si no existe
            }

            for ($i = 0; $i < $total; $i++) {
                $fileName = basename($_FILES['imagenes']['name'][$i]);
                $fileTmpName = $_FILES['imagenes']['tmp_name'][$i];
                $fileType = $_FILES['imagenes']['type'][$i];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Validar que sea una imagen
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($fileExtension, $allowedExtensions)) {
                    $newFileName = uniqid() . "." . $fileExtension;
                    $destination = $uploadDirectory . $newFileName;

                    if (move_uploaded_file($fileTmpName, $destination)) {
                        // Insertar en la tabla MultimediaProducto
                        $insertMultimedia = "INSERT INTO MultimediaProducto (id_producto, URL) 
                                             VALUES ($idProducto, '$destination')";
                        mysqli_query($conexion, $insertMultimedia);
                    }
                }
            }
        }

        echo "<div class='text-green-500 text-center mt-4'>¡Producto publicado exitosamente!</div>";
    } else {
        echo "<div class='text-red-500 text-center mt-4'>Error al publicar producto: " . mysqli_error($conexion) . "</div>";
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
<body class="bg-gray-100">

<?php include('navbar.php'); ?>

<div id="main-content" class="flex transition-all duration-300 p-6 bg-orange-100 min-h-screen justify-center">
    <form action="" method="POST" enctype="multipart/form-data" class="grid grid-cols-2 gap-4 items-center justify-center w-[70%]">

        <h2 class="col-span-2 text-lg text-center font-bold mb-4">Crear Nueva Publicación</h2>

        <!-- Nombre del producto -->
        <input type="text" name="nombre" placeholder="Nombre del Producto" class="w-full p-2 mb-2 border rounded" required>

        <!-- Descripción -->
        <textarea name="descripcion" placeholder="Descripción del Producto" class="w-full p-2 mb-2 border rounded" rows="4" required></textarea>

        <!-- Precio -->
        <input type="number" name="precio" placeholder="Precio" step="0.01" min="0" class="w-full p-2 mb-2 border rounded" required>

        <!-- Inventario -->
        <input type="number" name="inventario" placeholder="Inventario" step="1" min="0" class="w-full p-2 mb-2 border rounded" required>

        <!-- Categoría -->
        <div class="col-span-2">
            <label class="block mb-1">Selecciona una Categoría:</label>
            <select name="categoria" id="categoria" class="w-full p-2 border rounded" onchange="mostrarNuevaCategoria()" required>
                <?php foreach($categorias as $categoria): ?>
                    <option value="<?= $categoria['id_categoria'] ?>"><?= $categoria['NombreCategoria'] ?></option>
                <?php endforeach; ?>
                <option value="nueva">+ Agregar nueva categoría</option>
            </select>
        </div>

        <!-- Nueva categoría -->
        <div id="nuevaCategoriaCampos" class="col-span-2 hidden">
            <input type="text" name="nuevaCategoria" placeholder="Nombre de la nueva categoría" class="w-full p-2 mb-2 border rounded">
            <textarea name="descripcionCategoria" placeholder="Descripción de la nueva categoría" class="w-full p-2 mb-2 border rounded" rows="2"></textarea>
        </div>

        <!-- Tipo de publicación -->
        <div class="col-span-2">
            <label class="block mb-1">Tipo de publicación:</label>
            <div class="flex gap-4">
                <label><input type="radio" name="tipo" value="Cotizar" required> Cotizar</label>
                <label><input type="radio" name="tipo" value="Vender" required> Vender</label>
            </div>
        </div>

        <!-- Imágenes -->
<div class="col-span-2">
    <label class="block mb-1">Imágenes del producto:</label>
    <input type="file" name="imagenes[]" id="imagenesInput" multiple accept="image/*" class="w-full p-2 border rounded">

    <!-- Aquí se van a mostrar las miniaturas -->
    <div id="preview" class="flex flex-wrap gap-4 mt-4"></div>
</div>


        <!-- Botón de Publicar -->
        <button type="submit" class="col-span-2 bg-green-500 text-white py-2 rounded">Publicar Producto</button>

    </form>
</div>

<script>
function mostrarNuevaCategoria() {
    const select = document.getElementById('categoria');
    const camposNuevaCategoria = document.getElementById('nuevaCategoriaCampos');

    if (select.value === "nueva") {
        camposNuevaCategoria.classList.remove('hidden');
    } else {
        camposNuevaCategoria.classList.add('hidden');
    }
}

// 👉 Previsualizar imágenes seleccionadas
document.getElementById('imagenesInput').addEventListener('change', function(event) {
    const preview = document.getElementById('preview');
    preview.innerHTML = ""; // Limpiar preview anterior

    const files = event.target.files;
    if (files) {
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = "w-32 h-32 object-cover rounded border";
                    preview.appendChild(img);
                }
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>


</body>
</html>
