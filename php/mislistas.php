<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'conexion.php'; // Importante que esté antes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Proceso para crear la lista (cuando se envía el formulario vía fetch)
    if (isset($_POST['nombreLista']) && isset($_POST['descripcion'])) {
        $nombreLista = mysqli_real_escape_string($conexion, $_POST['nombreLista']);
        $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
        $publica = isset($_POST['publica']) ? 1 : 0;
        $id_usuario = $_SESSION['id_usuario'];

        $query = "INSERT INTO ListaUsuario (NombreLista, Descripcion, Publica, id_usuario) 
                  VALUES ('$nombreLista', '$descripcion', $publica, $id_usuario)";

        if (mysqli_query($conexion, $query)) {
            // Obtener el último ID insertado (la nueva lista)
            $id_lista = mysqli_insert_id($conexion);

            // Obtener los datos de la lista recién creada
            $consultaLista = "SELECT * FROM ListaUsuario WHERE id_lista = $id_lista";
            $resultadoLista = mysqli_query($conexion, $consultaLista);
            $lista = mysqli_fetch_assoc($resultadoLista);

            // Enviar la respuesta con los datos de la nueva lista en formato JSON
            echo json_encode($lista);
        } else {
            echo "Error al crear la lista: " . mysqli_error($conexion);
        }
    } else {
        echo "Faltan campos obligatorios.";
    }
    exit; // IMPORTANTE: parar aquí para no seguir cargando todo el HTML
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SwiperJS CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css">
</head>

<body class="bg-gray-100">

    <header>
        <?php include('navbar.php'); ?>
    </header>

    <div id="main-content" class="flex-1 transition-all duration-300 p-6 bg-orange-100">

        <!-- Barra de búsqueda -->
        <input type="text" id="search" name="search" placeholder="Buscar una lista por su nombre..."
            class="w-full mb-10 p-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-orange-500">

        <!-- Botón para abrir modal -->
        <button onclick="document.getElementById('crearListaModal').classList.remove('hidden')"
            class="bg-orange-500 text-white px-4 py-2 rounded-full mb-6 hover:bg-orange-600 transition">
            Crear Nueva Lista
        </button>

        <!-- Modal para crear lista -->
        <div id="crearListaModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white p-8 rounded-lg w-full max-w-md">
                <h2 class="text-2xl font-bold mb-4">Crear Nueva Lista</h2>
                <form id="crearListaForm">
                    <input type="text" name="nombreLista" placeholder="Nombre de la lista"
                        class="w-full p-2 mb-4 border border-gray-300 rounded" required>
                    <textarea name="descripcion" placeholder="Descripción de la lista"
                        class="w-full p-2 mb-4 border border-gray-300 rounded"></textarea>
                    <label class="inline-flex items-center mb-4">
                        <input type="checkbox" name="publica" class="form-checkbox">
                        <span class="ml-2">¿Lista pública?</span>
                    </label>
                    <div class="flex justify-end gap-2">
                        <button type="button"
                            onclick="document.getElementById('crearListaModal').classList.add('hidden')"
                            class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Cancelar</button>
                        <button type="submit"
                            class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">Crear</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Listas del usuario -->
        <div class="grid grid-cols-4 gap-5 mt-6" id="listasContainer">
            <?php
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            include 'conexion.php'; // asegúrate de incluir tu archivo de conexión a la BD
            
            if (isset($_SESSION['id_usuario'])) {
                $id_usuario = $_SESSION['id_usuario']; // ID del usuario en sesión
                $consultaListas = "SELECT * FROM ListaUsuario WHERE id_usuario = $id_usuario";
                $resultadoListas = mysqli_query($conexion, $consultaListas);

                if (mysqli_num_rows($resultadoListas) > 0) {
                    while ($lista = mysqli_fetch_assoc($resultadoListas)) {
                        echo '<div class="h-[200px] bg-white rounded-[10px] p-4 shadow-md">';
                        echo '<h2 class="text-xl font-bold mb-2 text-orange-600">' . htmlspecialchars($lista['NombreLista']) . '</h2>';
                        echo '<p class="text-gray-600">' . htmlspecialchars($lista['Descripcion']) . '</p>';
                        echo '<p class="text-sm mt-2 text-gray-500">' . ($lista['Publica'] ? 'Pública' : 'Privada') . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p class="text-gray-600">No tienes listas creadas aún.</p>';
                }
            }


            ?>
        </div>
    </div>

    <!-- JavaScript para manejar el submit -->
    <script>
        document.getElementById("crearListaForm").addEventListener("submit", function (event) {
            event.preventDefault(); // evitar que se envíe normal

            const formData = new FormData(this);
            fetch('mislistas.php', {  // apunta a sí mismo
                method: 'POST',
                body: formData
            })
                .then(response => response.json()) // Respuesta como JSON
                .then(data => {
                    if (data) {
                        // Crear un nuevo elemento en el DOM
                        const nuevaLista = document.createElement("div");
                        nuevaLista.classList.add("h-[200px]", "bg-white", "rounded-[10px]", "p-4", "shadow-md");
                        nuevaLista.innerHTML = `
                        <h2 class="text-xl font-bold mb-2 text-orange-600">${data.NombreLista}</h2>
                        <p class="text-gray-600">${data.Descripcion}</p>
                        <p class="text-sm mt-2 text-gray-500">${data.Publica ? 'Pública' : 'Privada'}</p>
                    `;
                        document.getElementById("listasContainer").prepend(nuevaLista); // Insertar al inicio
                        alert("Lista creada exitosamente");
                        document.getElementById('crearListaModal').classList.add('hidden'); // Cerrar modal
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Hubo un error al crear la lista.");
                });
        });
    </script>

</body>

</html>