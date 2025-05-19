<?php
session_start();
require_once '../modelos/conexion.php';
$db_global_profile = new Database();
$conexion_global = $db_global_profile->getConexion(); // Usar esta conexión para consultas directas en este script

$logged_in_user_id = $_SESSION['id_usuario'] ?? null;
$profile_owner_id = null;
$profile_owner_data = null;
$is_own_profile = false;

// 1. Determinar el ID del propietario del perfil
if (isset($_GET['id_usuario_perfil']) && !empty($_GET['id_usuario_perfil'])) {
    $profile_owner_id = (int)$_GET['id_usuario_perfil'];
    if ($logged_in_user_id && $logged_in_user_id === $profile_owner_id) {
        $is_own_profile = true;
    }
} elseif ($logged_in_user_id) {
    $profile_owner_id = $logged_in_user_id;
    $is_own_profile = true;
}

// 2. Obtener datos del propietario del perfil
if ($profile_owner_id) {
    $stmt_owner = $conexion_global->prepare("SELECT id_usuario, nombre, apellido_P, apellido_M, nombre_usuario, tipo, avatar, fecha_Registro FROM Usuario WHERE id_usuario = ?");
    if ($stmt_owner) {
        $stmt_owner->bind_param("i", $profile_owner_id);
        $stmt_owner->execute();
        $result_owner = $stmt_owner->get_result();
        if ($result_owner->num_rows > 0) {
            $profile_owner_data = $result_owner->fetch_assoc();
        } else {
            // Perfil no encontrado, es crucial manejar esto antes de continuar.
            include('navbar.php'); // Muestra navbar
            echo "<div class='text-center p-10 text-red-600'>Perfil no encontrado. Es posible que el usuario no exista o el enlace sea incorrecto.</div>";
            $conexion_global->close(); // Cerrar conexión global
            exit;
        }
        $stmt_owner->close();
    } else {
        // Error grave al preparar la consulta.
        // Considera loguear este error.
        die("Error crítico al preparar la consulta del perfil: " . $conexion_global->error);
    }
} else {
    // No se pudo determinar un perfil para mostrar (ej. visitante no logueado y sin ?id_usuario_perfil=)
    include('navbar.php');
    echo "<div class='text-center p-10 text-red-600'>No se puede determinar el perfil a mostrar. <a href='landing.php' class='text-blue-500 hover:underline'>Volver al inicio</a> o intenta iniciar sesión.</div>";
    $conexion_global->close(); // Cerrar conexión global
    exit;
}


// 3. Lógica de Navegación de Secciones y Sección Activa
$profile_nav_items = []; // Contendrá ['clave_seccion' => 'Nombre para mostrar']
$default_section = 'default_fallback_section'; // Un valor inicial que no coincidirá con secciones válidas

if ($profile_owner_data) { // Solo si tenemos datos del perfil
    $owner_type = $profile_owner_data['tipo'];

    // Definir las secciones disponibles y su nombre para mostrar
    if ($owner_type === 'Vendedor') {
        $default_section = 'productos_publicados_vendedor'; // La vista pública por defecto de un Vendedor
        $profile_nav_items['productos_publicados_vendedor'] = 'Productos';
        $profile_nav_items['listas_personales_vendedor'] = 'Listas Personales'; // Los vendedores también pueden tener listas

        if ($is_own_profile) { // Secciones privadas del Vendedor
            $profile_nav_items['productos_pendientes_vendedor'] = 'Pendientes/Rechazados';
            $profile_nav_items['ventas_vendedor'] = 'Mis Ventas';
        }
    } elseif ($owner_type === 'Cliente') {
        $default_section = 'listas_personales_cliente'; // La vista principal de un Cliente
        $profile_nav_items['listas_personales_cliente'] = 'Listas';

        if ($is_own_profile) { // Secciones privadas del Cliente
            $profile_nav_items['historial_pedidos_cliente'] = 'Historial de Pedidos';
        }
    }
    // Administradores y Superadministradores son redirigidos por LoginController,
    // por lo que no deberían llegar aquí de forma normal para ver un "perfil" de este tipo.
}

$activo = $_GET['seccion'] ?? $default_section;

// Validar $activo:
// Si la sección solicitada ($activo) no está definida en $profile_nav_items para el rol actual Y el contexto de visualización,
// O si es una sección privada y no es el perfil propio, entonces se resetea a $default_section.
$is_active_section_valid_for_display = array_key_exists($activo, $profile_nav_items);

if ($is_active_section_valid_for_display) {
    // Chequeo adicional para secciones que requieren ser el propietario, incluso si la clave existe en $profile_nav_items
    $private_sections_requiring_ownership = [
        'productos_pendientes_vendedor',
        'ventas_vendedor',
        'historial_pedidos_cliente'
        // 'listas_personales_vendedor' y 'listas_personales_cliente' NO están aquí porque mislistas.php maneja su propia privacidad interna.
    ];
    if (in_array($activo, $private_sections_requiring_ownership) && !$is_own_profile) {
        $is_active_section_valid_for_display = false; // No tiene permiso para esta sección privada específica
    }
}

if (!$is_active_section_valid_for_display) {
    $activo = $default_section; // Resetear a la sección por defecto del rol
    // Si $default_section también resulta no ser una clave válida (ej. $profile_owner_data fue null,
    // o un tipo de usuario inesperado), el switch de inclusión de archivos manejará el caso 'default'.
}

// Función auxiliar para clases de botones
function getButtonClass($buttonName, $activeSection) {
    return ($buttonName === $activeSection) ? "bg-white text-orange-500 px-4 md:px-6 py-2 md:py-4 rounded-full transition shadow-md" : "bg-blue-950 text-white px-4 md:px-6 py-2 md:py-4 rounded-full border-2 md:border-4 border-orange-500 hover:bg-orange-500 hover:text-white transition";
}

// Datos para la vista del encabezado
$fecha_registro_formateada = isset($profile_owner_data['fecha_Registro']) ? (new DateTime($profile_owner_data['fecha_Registro']))->format('d \d\e F \d\e Y') : 'Fecha desconocida';
$profile_avatar = (isset($profile_owner_data['avatar']) && !empty($profile_owner_data['avatar'])) ? '../recursos/usuarios/' . htmlspecialchars($profile_owner_data['avatar']) : '../recursos/perfilvacio.jpg';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles_profile.css">
    <title>Perfil de <?php echo htmlspecialchars($profile_owner_data['nombre_usuario'] ?? 'Usuario'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-orange-50">
    <header>
        <?php include('navbar.php'); ?>
    </header>
    <div id="main-content" class="transition-all duration-300 p-3 md:p-6 min-h-screen">
        <div class="w-full max-w-6xl mx-auto"> 
            
            <div class="profile-card-header bg-gradient-to-r from-orange-100 via-gray-50 to-orange-100 mb-5 p-4 rounded-lg shadow-lg flex flex-col md:flex-row items-center md:items-start">
                <div class="section-left relative flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                    <img src="<?php echo $profile_avatar; ?>" alt="Foto de perfil" class="profile-img w-24 h-24 md:w-32 md:h-32 border-2 border-orange-300">
                    <?php if ($is_own_profile): ?>
                    <a href="editarperfil.php" title="Editar perfil">
                        <img src="../recursos/iconos/editar.png" class="absolute w-7 h-7 md:w-8 md:h-8 bg-orange-500 p-1 rounded-full bottom-0 right-0 md:top-2 md:right-2 transform cursor-pointer z-30 hover:bg-orange-600" alt="Editar perfil">
                    </a>
                    <?php endif; ?>
                </div>
                <div class="section-middle text-center md:text-left">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-800">
                        <?php echo htmlspecialchars($profile_owner_data['nombre'] . ' ' . $profile_owner_data['apellido_P'] ?? 'Usuario'); ?>
                        <span class="text-gray-600 text-lg">(@<?php echo htmlspecialchars($profile_owner_data['nombre_usuario'] ?? ''); ?>)</span>
                    </h2>
                    <p class="text-v1 text-sm text-gray-600">Se unió el: <?php echo $fecha_registro_formateada; ?></p>
                    <span class="role <?php echo strtolower(htmlspecialchars($profile_owner_data['tipo'] ?? 'cliente')); ?> mt-1 inline-block px-3 py-1 text-xs font-semibold rounded-full">
                        <?php echo htmlspecialchars($profile_owner_data['tipo'] ?? 'Cliente'); ?>
                    </span>
                    <?php if (!$is_own_profile && $logged_in_user_id && $profile_owner_id != $logged_in_user_id): // Botón de Mensaje ?>
                    <button onclick="window.location.href='chat.php?contact_id=<?php echo $profile_owner_id; ?>'" class="btn-v2 bg-blue-700 hover:bg-blue-800 text-white text-sm px-4 py-2 ml-0 md:ml-4 mt-2 md:mt-0 rounded-md inline-block">Mensaje</button>
                    <?php endif; ?>
                </div>
            </div>
               
            
            <div class="flex flex-wrap gap-2 mb-4 items-start justify-center md:justify-start">
                <?php
                    $base_profile_url = "profile.php?id_usuario_perfil=" . $profile_owner_id . "&seccion=";
                    // Iterar sobre las secciones que se determinaron aplicables
                    foreach ($profile_nav_items as $key_nav => $value_nav):
                ?>
                    <a href="<?php echo $base_profile_url . $key_nav; ?>" class="<?= getButtonClass($key_nav, $activo) ?> text-xs md:text-sm font-medium"><?php echo htmlspecialchars($value_nav); ?></a>
                <?php
                    endforeach;
                ?>
            </div>

         
            <div id="contenedor" class="bg-white p-4 md:p-6 rounded-lg shadow-md min-h-[300px]">
                <?php
                // Pasar contexto a los archivos de sección
                $_SESSION['profile_owner_id_for_section'] = $profile_owner_id;
                $_SESSION['is_own_profile_for_section'] = $is_own_profile;

                $seccionFile = '';
                switch ($activo) {
                    // Vendedor Sections
                    case 'productos_publicados_vendedor':
                        if ($profile_owner_data['tipo'] === 'Vendedor') $seccionFile = 'vendedor_productos.php';
                        break;
                    case 'productos_pendientes_vendedor':
                        if ($is_own_profile && $profile_owner_data['tipo'] === 'Vendedor') $seccionFile = 'vendedor_productos_pendientes.php';
                        break;
                    case 'ventas_vendedor':
                        if ($is_own_profile && $profile_owner_data['tipo'] === 'Vendedor') $seccionFile = 'vendedor_reporte_ventas.php';
                        break;

                    // Comprador (y Vendedor's personal lists) Sections
                    case 'listas_personales_vendedor': // La clave para Vendedor
                    case 'listas_personales_cliente': // La clave para Cliente
                        $seccionFile = 'mislistas.php'; // mislistas.php maneja la privacidad interna
                        break;
                    case 'historial_pedidos_cliente':
                        if ($is_own_profile && $profile_owner_data['tipo'] === 'Cliente') $seccionFile = 'orders.php';
                        break;

                    default:
                        // Este default se activa si $activo no es ninguna de las claves anteriores
                        // o si $default_section era 'default_fallback_section'
                        if (isset($profile_owner_data['tipo'])) { // Solo si tenemos tipo de propietario
                            if ($profile_owner_data['tipo'] === 'Vendedor') {
                                $seccionFile = 'vendedor_productos.php'; // Fallback seguro para Vendedor
                            } elseif ($profile_owner_data['tipo'] === 'Cliente') {
                                $seccionFile = 'mislistas.php'; // Fallback seguro para Cliente
                            } else {
                                echo "<p class='text-center text-gray-600'>Sección no configurada para este tipo de perfil.</p>";
                            }
                        } else {
                             echo "<p class='text-center text-gray-600'>No se pueden cargar las secciones del perfil.</p>";
                        }
                        break;
                }

                if (!empty($seccionFile) && file_exists($seccionFile)) {
                    include $seccionFile;
                } elseif (!empty($seccionFile) && !file_exists($seccionFile)) {
                     echo "<p class='text-center text-red-500'>Error: El archivo de la sección (<code>" . htmlspecialchars($seccionFile) . "</code>) no fue encontrado.</p>";
                } elseif (empty($seccionFile) && $activo !== 'default_fallback_section' ) {
                    // Si $seccionFile está vacío pero $activo era una clave válida, es un problema de lógica en el switch
                     echo "<p class='text-center text-gray-600'>La sección solicitada no está disponible o no tienes permiso.</p>";
                }


                // Limpiar variables de sesión temporales usadas para pasar contexto
                unset($_SESSION['profile_owner_id_for_section']);
                unset($_SESSION['is_own_profile_for_section']);
                ?>
            </div>
        </div>
     </div>
     <?php
        $conexion_global->close(); // Cerrar la conexión global al final del script si ya no se usa.
     ?>
</body>
</html>