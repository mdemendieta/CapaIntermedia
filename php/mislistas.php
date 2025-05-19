<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../modelos/conexion.php';
$db = new Database();
$conexion = $db->getConexion();

// Estas variables son para la visualización de la página (cuando se incluye desde profile.php)
$profile_owner_id_display = $_SESSION['profile_owner_id_for_section'] ?? null;
$is_own_profile_display = $_SESSION['is_own_profile_for_section'] ?? false;
$visitor_id = $_SESSION['id_usuario'] ?? null; // El usuario actualmente logueado

// --- Manejo de Solicitud POST para Crear o Eliminar Lista ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Para crear o eliminar una lista, la acción siempre es sobre el usuario logueado.
    // No necesitamos $is_own_profile aquí porque estas acciones solo deben ser iniciadas
    // desde la interfaz que se muestra cuando $is_own_profile_display es true.
    // La validación clave es que $visitor_id (el usuario logueado) sea el dueño de la lista.

    if (!$visitor_id) {
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para realizar esta acción.']);
        exit;
    }

    // Proceso para crear la lista
    if (isset($_POST['action']) && $_POST['action'] == 'crear_lista') {
        if (isset($_POST['nombreLista']) && isset($_POST['descripcion'])) {
            $nombreLista = mysqli_real_escape_string($conexion, $_POST['nombreLista']);
            $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
            $publica = isset($_POST['publica']) ? 1 : 0;
            // La lista se crea para el usuario actualmente logueado ($visitor_id)
            $query = "INSERT INTO ListaUsuario (NombreLista, Descripcion, Publica, id_usuario)
                      VALUES (?, ?, ?, ?)";
            $stmt_create = $conexion->prepare($query);
            if ($stmt_create) {
                $stmt_create->bind_param("ssii", $nombreLista, $descripcion, $publica, $visitor_id);
                if ($stmt_create->execute()) {
                    $id_lista = $stmt_create->insert_id;
                    // Obtener datos de la lista recién creada para la respuesta JSON
                    $consultaLista = $conexion->prepare("SELECT * FROM ListaUsuario WHERE id_lista = ?");
                    $consultaLista->bind_param("i", $id_lista);
                    $consultaLista->execute();
                    $lista = $consultaLista->get_result()->fetch_assoc();
                    $consultaLista->close();
                    echo json_encode(['success' => true, 'lista' => $lista, 'message' => 'Lista creada exitosamente.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al crear la lista: ' . $stmt_create->error]);
                }
                $stmt_create->close();
            } else {
                 echo json_encode(['success' => false, 'message' => 'Error al preparar la creación de lista.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios para crear la lista.']);
        }
        exit;
    }

    // Proceso para eliminar la lista
    if (isset($_POST['action']) && $_POST['action'] == 'eliminar_lista') {
        if (isset($_POST['id_lista'])) {
            $id_lista_a_eliminar = (int)$_POST['id_lista'];

            // Primero, eliminar productos asociados en ProductoEnLista
            $stmtProdLista = $conexion->prepare("DELETE FROM ProductoEnLista WHERE id_lista = ?");
            $stmtProdLista->bind_param("i", $id_lista_a_eliminar);
            $stmtProdLista->execute(); // No es crítico si falla, pero bueno hacerlo.
            $stmtProdLista->close();

            // Luego, eliminar la lista, asegurándose que pertenece al usuario logueado ($visitor_id)
            $stmtLista = $conexion->prepare("DELETE FROM ListaUsuario WHERE id_lista = ? AND id_usuario = ?");
            $stmtLista->bind_param("ii", $id_lista_a_eliminar, $visitor_id);

            if ($stmtLista->execute()) {
                if ($stmtLista->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Lista eliminada correctamente.']);
                } else {
                    // Esto puede pasar si la lista no existe o no pertenece al usuario.
                    echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la lista o no tienes permiso sobre ella.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al ejecutar la eliminación de la lista: ' . $stmtLista->error]);
            }
            $stmtLista->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de lista no proporcionado para eliminar.']);
        }
        exit;
    }

    // Si ninguna acción POST coincide
    echo json_encode(['success' => false, 'message' => 'Acción POST no reconocida.']);
    exit;
}


// El resto del archivo (HTML para mostrar las listas) usa $profile_owner_id_display y $is_own_profile_display
if (!$profile_owner_id_display) {
    // Esto no debería pasar si profile.php establece el contexto correctamente.
    // Pero como fallback:
    if ($visitor_id) { // Si está logueado, muestra sus propias listas por defecto.
        $profile_owner_id_display = $visitor_id;
        $is_own_profile_display = true;
    } else {
        echo "<p>No se pudo determinar el propietario del perfil para mostrar las listas.</p>";
        // No mostrar el resto del HTML de mislistas.php si no hay contexto.
        // Opcionalmente, puedes incluir el cierre del body/html si este archivo fuera standalone.
        return;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Listas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php /* No necesitas Swiper aquí si no hay carruseles directamente en mislistas.php */ ?>
    <style>
        .lista-item { display: flex; flex-direction: column; justify-content: space-between; height: auto; min-height: 180px; /* Ajusta según contenido */ }
        .lista-item-content { flex-grow: 1; }
        .lista-item-actions { margin-top: auto; /* Empuja el botón hacia abajo */ }
    </style>
</head>
<body class="bg-gray-100">

    <div id="misListasContent" class="w-full">
        <?php if ($is_own_profile_display): // Mostrar controles de creación solo si es el perfil propio ?>
        <input type="text" id="searchUserLists" name="searchUserLists" placeholder="Buscar en mis listas..."
            class="w-full mb-6 p-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-orange-500">

        <button onclick="document.getElementById('crearListaModal').classList.remove('hidden')"
            class="bg-orange-500 text-white px-4 py-2 rounded-full mb-6 hover:bg-orange-600 transition">
            Crear Nueva Lista
        </button>

        <div id="crearListaModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white p-8 rounded-lg w-full max-w-md">
                <h2 class="text-2xl font-bold mb-4">Crear Nueva Lista</h2>
                <form id="crearListaForm">
                    <input type="hidden" name="action" value="crear_lista">
                    <input type="text" name="nombreLista" placeholder="Nombre de la lista"
                        class="w-full p-2 mb-4 border border-gray-300 rounded" required>
                    <textarea name="descripcion" placeholder="Descripción de la lista (opcional)"
                        class="w-full p-2 mb-4 border border-gray-300 rounded"></textarea>
                    <label class="inline-flex items-center mb-4">
                        <input type="checkbox" name="publica" class="form-checkbox h-5 w-5 text-orange-600">
                        <span class="ml-2 text-gray-700">Hacer esta lista pública</span>
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
        <?php endif; // Fin de $is_own_profile_display para controles de creación ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 mt-6" id="listasContainer">
            <?php
            if ($profile_owner_id_display) {
                $sql = "SELECT id_lista, NombreLista, Descripcion, Publica FROM ListaUsuario WHERE id_usuario = ?";
                if (!$is_own_profile_display) { // Si se está viendo el perfil de otro usuario
                    $sql .= " AND Publica = 1"; // Solo mostrar sus listas públicas
                }
                $sql .= " ORDER BY id_lista DESC";

                $stmtListas = $conexion->prepare($sql);
                $stmtListas->bind_param("i", $profile_owner_id_display);
                $stmtListas->execute();
                $resultadoListas = $stmtListas->get_result();

                if ($resultadoListas->num_rows > 0) {
                    while ($lista = $resultadoListas->fetch_assoc()) {
                        echo '<div class="lista-item bg-white rounded-[10px] p-4 shadow-md" data-id-lista="' . $lista['id_lista'] . '" data-nombre-lista="' . htmlspecialchars(strtolower($lista['NombreLista'])) . '">';
                        echo '  <div class="lista-item-content">';
                        echo '    <a href="listaproductos.php?id_lista=' . $lista['id_lista'] . '&id_usuario_perfil=' . $profile_owner_id_display . '&nombre_lista=' . urlencode(htmlspecialchars($lista['NombreLista'])) . '" class="text-xl font-bold mb-2 text-orange-600 hover:underline block truncate" title="'.htmlspecialchars($lista['NombreLista']).'">' . htmlspecialchars($lista['NombreLista']) . '</a>';
                        echo '    <p class="text-gray-600 text-sm mb-2 h-12 overflow-hidden">' . (empty($lista['Descripcion']) ? '<i>Sin descripción</i>' : htmlspecialchars($lista['Descripcion'])) . '</p>';
                        echo '    <p class="text-xs mt-1 text-gray-500">' . ($lista['Publica'] ? 'Pública' : 'Privada') . '</p>';
                        echo '  </div>';
                        if ($is_own_profile_display) { // Mostrar botón de eliminar solo si es el perfil propio
                            echo '  <div class="lista-item-actions mt-3">'; // mt-auto en la clase .lista-item-actions si es necesario
                            echo '    <button onclick="eliminarLista(' . $lista['id_lista'] . ')" class="bg-red-500 text-white text-xs px-3 py-1.5 rounded-full hover:bg-red-600 transition w-full">Eliminar lista</button>';
                            echo '  </div>';
                        }
                        echo '</div>';
                    }
                } else {
                    if ($is_own_profile_display) {
                        echo '<p class="text-gray-600 col-span-full text-center py-5">Aún no has creado ninguna lista.</p>';
                    } else {
                        echo '<p class="text-gray-600 col-span-full text-center py-5">Este usuario no tiene listas públicas.</p>';
                    }
                }
                $stmtListas->close();
            }
            ?>
        </div>
    </div>

    <?php if ($is_own_profile_display): // El JavaScript para manejo de formularios solo es necesario si es el perfil propio ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const crearListaForm = document.getElementById("crearListaForm");
        if (crearListaForm) {
            crearListaForm.addEventListener("submit", function (event) {
                event.preventDefault();
                const formData = new FormData(this);
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = 'Creando...';

                fetch('mislistas.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Crear';
                    if (data.success && data.lista) {
                        const nuevaListaDiv = document.createElement("div");
                        nuevaListaDiv.classList.add("lista-item", "bg-white", "rounded-[10px]", "p-4", "shadow-md");
                        nuevaListaDiv.setAttribute('data-id-lista', data.lista.id_lista);
                        nuevaListaDiv.setAttribute('data-nombre-lista', data.lista.NombreLista.toLowerCase());

                        const profileOwnerIdJS = <?php echo json_encode($profile_owner_id_display); ?>;
                        
                        nuevaListaDiv.innerHTML = `
                            <div class="lista-item-content">
                                <a href="listaproductos.php?id_lista=${data.lista.id_lista}&id_usuario_perfil=${profileOwnerIdJS}&nombre_lista=${encodeURIComponent(data.lista.NombreLista)}" class="text-xl font-bold mb-2 text-orange-600 hover:underline block truncate" title="${data.lista.NombreLista}">${data.lista.NombreLista}</a>
                                <p class="text-gray-600 text-sm mb-2 h-12 overflow-hidden">${data.lista.Descripcion || '<i>Sin descripción</i>'}</p>
                                <p class="text-xs mt-1 text-gray-500">${data.lista.Publica == 1 ? 'Pública' : 'Privada'}</p>
                            </div>
                            <div class="lista-item-actions mt-3">
                                <button onclick="eliminarLista(${data.lista.id_lista})" class="bg-red-500 text-white text-xs px-3 py-1.5 rounded-full hover:bg-red-600 transition w-full">Eliminar lista</button>
                            </div>
                        `;
                        const container = document.getElementById("listasContainer");
                        const noListasMsg = container.querySelector("p.text-gray-600.col-span-full");
                        if (noListasMsg) noListasMsg.remove();
                        
                        container.prepend(nuevaListaDiv);
                        alert(data.message);
                        document.getElementById('crearListaModal').classList.add('hidden');
                        this.reset();
                    } else {
                        alert("Error: " + (data.message || "No se pudo crear la lista."));
                    }
                })
                .catch(error => {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Crear';
                    console.error('Error:', error);
                    alert("Hubo un error de red al crear la lista.");
                });
            });
        }

        // Filtro de búsqueda para las listas del usuario
        const searchUserListsInput = document.getElementById('searchUserLists');
        if(searchUserListsInput) {
            searchUserListsInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const listasContainer = document.getElementById('listasContainer');
                const listas = listasContainer.querySelectorAll('.lista-item');
                listas.forEach(lista => {
                    const nombreLista = lista.dataset.nombreLista || '';
                    if (nombreLista.includes(searchTerm)) {
                        lista.style.display = 'flex'; // O el display original
                    } else {
                        lista.style.display = 'none';
                    }
                });
            });
        }
    });

    // Función global para eliminarLista ya que el botón se genera dinámicamente
    function eliminarLista(idLista) {
        if (!confirm("¿Estás seguro de que quieres eliminar esta lista? Esta acción no se puede deshacer.")) {
            return;
        }
        const formData = new FormData();
        formData.append('action', 'eliminar_lista');
        formData.append('id_lista', idLista);

        fetch('mislistas.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                const listaDiv = document.querySelector(`.lista-item[data-id-lista='${idLista}']`);
                if (listaDiv) listaDiv.remove();
                
                const container = document.getElementById("listasContainer");
                if (!container.querySelector(".lista-item")) {
                    const isOwnProfileJS = <?php echo json_encode($is_own_profile_display); ?>;
                    if (isOwnProfileJS) {
                        container.innerHTML = '<p class="text-gray-600 col-span-full text-center py-5">Aún no has creado ninguna lista.</p>';
                    } else {
                         // Esto no debería alcanzarse si el botón de eliminar no está visible para otros.
                         container.innerHTML = '<p class="text-gray-600 col-span-full text-center py-5">Este usuario no tiene listas públicas.</p>';
                    }
                }
            } else {
                alert("Error: " + (data.message || "No se pudo eliminar la lista."));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Hubo un error de red al eliminar la lista.");
        });
    }
    </script>
    <?php endif; // Fin de $is_own_profile_display para JavaScript ?>
</body>
</html>