<?php
session_start();
require_once '../modelos/conexion.php';
$db = new Database();
$conexion_global = $db->getConexion();

$logged_in_user_id = $_SESSION['id_usuario'] ?? null;
$profile_owner_id = null;
$profile_owner_data = null;
$is_own_profile = false;

// Determinar el ID del propietario del perfil
if (isset($_GET['id_usuario_perfil']) && !empty($_GET['id_usuario_perfil'])) {
    $profile_owner_id = (int)$_GET['id_usuario_perfil'];
    if ($logged_in_user_id && $logged_in_user_id === $profile_owner_id) {
        $is_own_profile = true;
    }
} elseif ($logged_in_user_id) { // Si no hay ID en URL, y está logueado, es su propio perfil
    $profile_owner_id = $logged_in_user_id;
    $is_own_profile = true;
}

// Obtener datos del propietario del perfil
if ($profile_owner_id) {
    $stmt_owner = $conexion_global->prepare("SELECT id_usuario, nombre, apellido_P, apellido_M, nombre_usuario, tipo, avatar, fecha_Registro FROM Usuario WHERE id_usuario = ?");
    if ($stmt_owner) {
        $stmt_owner->bind_param("i", $profile_owner_id);
        $stmt_owner->execute();
        $result_owner = $stmt_owner->get_result();
        if ($result_owner->num_rows > 0) {
            $profile_owner_data = $result_owner->fetch_assoc();
        } else {
            // Manejo si el perfil no se encuentra
            echo "<p>Perfil no encontrado.</p>"; 
            // Considera incluir navbar.php y luego exit si la página no debe continuar.
            include('navbar.php');
            exit;
        }
        $stmt_owner->close();
    } else {
        die("Error al preparar la consulta del perfil."); // Error crítico
    }
} else {
    // Si no se puede determinar un perfil (ej. no logueado y sin id_usuario_perfil)
    // Redirigir a landing o login, o mostrar un mensaje adecuado.
    // Este caso es importante si profile.php no debe ser accesible sin un contexto de perfil.
    echo "<p>No se puede determinar el perfil a mostrar. <a href='landing.php'>Volver al inicio</a>.</p>";
    include('navbar.php'); // Es buena idea mostrar el navbar incluso en este caso.
    exit;
}

// Lógica de Navegación de Secciones Dinámica
$profile_nav_items = [];
$default_section = '';

if ($profile_owner_data) {
    $owner_type = $profile_owner_data['tipo'];

    if ($owner_type === 'Vendedor') {
        $default_section = 'productos_publicados_vendedor';
        $profile_nav_items['productos_publicados_vendedor'] = 'Productos'; // Pública

        if ($is_own_profile) {
            $profile_nav_items['productos_pendientes_vendedor'] = 'Pendientes/Rechazados';
            $profile_nav_items['ventas_vendedor'] = 'Mis Ventas';
        }
        // Un vendedor también puede tener listas personales (como comprador)
        // mislistas.php manejará la privacidad de estas listas.
        $profile_nav_items['listas_personales_vendedor'] = 'Listas Personales';


    } elseif ($owner_type === 'Cliente') {
        $default_section = 'listas_personales_cliente';
        // mislistas.php manejará la privacidad
        $profile_nav_items['listas_personales_cliente'] = 'Listas';

        if ($is_own_profile) {
            $profile_nav_items['historial_pedidos_cliente'] = 'Historial de Pedidos';
        }
    }
    // Administrador y Superadministrador son redirigidos y no deberían usar profile.php de esta forma.
}

$activo = $_GET['seccion'] ?? $default_section;

// Validar que $activo sea una sección permitida para el tipo de perfil y el contexto de visualización
$allowed_to_view_section = false;
if (array_key_exists($activo, $profile_nav_items)) {
    // Secciones siempre visibles si existen en $profile_nav_items (como 'productos_publicados_vendedor' o 'listas_personales_vendedor/cliente')
    // La privacidad interna de la sección (ej. listas privadas) la maneja el script de la sección.
    $allowed_to_view_section = true;

    // Casos específicos que requieren ser el propietario
    if (($activo === 'productos_pendientes_vendedor' || $activo === 'ventas_vendedor' || $activo === 'historial_pedidos_cliente') && !$is_own_profile) {
        $allowed_to_view_section = false;
    }
}

if (!$allowed_to_view_section) {
    $activo = $default_section; // Si la sección no es válida o no permitida, volver a la default.
}


function getButtonClass($buttonName, $activeSection) {
    return ($buttonName === $activeSection) ? "bg-white text-orange-500 px-6 py-4 rounded-full transition" : "bg-blue-950 text-white px-6 py-4 rounded-full border-4 border-orange-500 hover:bg-orange-500 transition";
}

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
<body>
    <header>
        <?php include('navbar.php'); ?>
    </header>
    <div id="main-content" class="flex justify-center transition-all duration-300 p-6 min-h-screen bg-orange-100">
        <div class="w-full max-w-6xl mx-auto"> {/* Max width para el contenido del perfil */}
            <div class="card-header w-full bg-gradient-to-r from-orange-100 from-1% via-gray-50 via-20% to-orange-100 to-90% mb-5 p-4 rounded-lg shadow">
                <div class="section-left relative flex items-center justify-center">
                    <img src="<?php echo $profile_avatar; ?>" alt="Foto de perfil" class="profile-img">
                    <?php if ($is_own_profile): ?>
                    <a href="editarperfil.php">
                        <img src="../recursos/iconos/editar.png" class="absolute w-8 h-8 bg-orange-500 p-1 rounded-full top-2 right-2 md:top-4 md:right-16 transform cursor-pointer z-30" alt="Editar perfil">
                    </a>
                    <?php endif; ?>
                </div>
                <div class="section-middle md:pl-6">
                    <h2 class="text-xl md:text-2xl font-bold"><?php echo htmlspecialchars($profile_owner_data['nombre'] . ' ' . $profile_owner_data['apellido_P'] ?? 'Usuario'); ?> <span class="text-gray-600 text-lg">(@<?php echo htmlspecialchars($profile_owner_data['nombre_usuario'] ?? ''); ?>)</span></h2>
                    <p class="text-v1 text-sm">Se unió el: <?php echo $fecha_registro_formateada; ?></p>
                    <span class="role <?php echo strtolower(htmlspecialchars($profile_owner_data['tipo'] ?? 'cliente')); ?> mt-1 inline-block"><?php echo htmlspecialchars($profile_owner_data['tipo'] ?? 'Cliente'); ?></span>
                    <?php if (!$is_own_profile && $logged_in_user_id && $profile_owner_id != $logged_in_user_id): ?>
                    <button onclick="window.location.href='chat.php?contact_id=<?php echo $profile_owner_id; ?>'" class="btn-v2 pl-4 pr-4 ml-0 md:ml-20 mt-2 md:mt-0 inline-block">Mensaje</button>
                    <?php endif; ?>
                </div>
            </div>
               
            <div class="flex flex-wrap gap-2 mb-4 items-start">
                <?php
                    $base_profile_url = "profile.php?id_usuario_perfil=" . $profile_owner_id . "&seccion=";
                    foreach ($profile_nav_items as $key => $value):
                        // La condición de visualización ya se aplicó al construir $profile_nav_items
                        // o al validar $activo. Aquí solo mostramos los items que aplican.
                         if (
                            // Secciones públicas o que manejan su propia privacidad (como listas)
                            ($key === 'productos_publicados_vendedor' && $profile_owner_data['tipo'] === 'Vendedor') ||
                            (strpos($key, 'listas_personales') !== false) || // 'listas_personales_cliente' o 'listas_personales_vendedor'
                            // Secciones privadas que requieren ser el propietario
                            ($is_own_profile && in_array($key, ['productos_pendientes_vendedor', 'ventas_vendedor', 'historial_pedidos_cliente']))
                        ) :
                ?>
                    <a href="<?php echo $base_profile_url . $key; ?>" class="<?= getButtonClass($key, $activo) ?> text-sm md:text-base"><?php echo htmlspecialchars($value); ?></a>
                <?php
                        endif;
                    endforeach;
                ?>
            </div>

            <div id="contenedor" class="flex justify-center mt-4">
                <?php
                $_SESSION['profile_owner_id_for_section'] = $profile_owner_id;
                $_SESSION['is_own_profile_for_section'] = $is_own_profile;
                // No es necesario pasar el tipo, ya que el script de la sección puede consultarlo si es necesario.

                $seccionFile = '';
                switch ($activo) {
                    case 'productos_publicados_vendedor':
                        if ($profile_owner_data['tipo'] === 'Vendedor') $seccionFile = 'vendedor_productos.php';
                        break;
                    case 'productos_pendientes_vendedor':
                        if ($is_own_profile && $profile_owner_data['tipo'] === 'Vendedor') $seccionFile = 'vendedor_productos_pendientes.php';
                        break;
                    case 'ventas_vendedor':
                        if ($is_own_profile && $profile_owner_data['tipo'] === 'Vendedor') $seccionFile = 'vendedor_reporte_ventas.php';
                        break;
                    case 'listas_personales_vendedor': // El vendedor puede tener sus propias listas de deseos
                    case 'listas_personales_cliente':
                        $seccionFile = 'mislistas.php'; // mislistas.php maneja la privacidad
                        break;
                    case 'historial_pedidos_cliente':
                        if ($is_own_profile && $profile_owner_data['tipo'] === 'Cliente') $seccionFile = 'orders.php';
                        break;
                    default:
                        // Si $activo no coincide con nada, y $default_section estaba vacío o era inválido,
                        // podríamos redirigir o mostrar un mensaje, o cargar un default real.
                        if ($profile_owner_data['tipo'] === 'Vendedor') $seccionFile = 'vendedor_productos.php';
                        elseif ($profile_owner_data['tipo'] === 'Cliente') $seccionFile = 'mislistas.php';
                        break;
                }

                if (!empty($seccionFile) && file_exists($seccionFile)) {
                    include $seccionFile;
                } else {
                     echo "<p class='text-center text-gray-600'>Contenido no disponible o no tienes permiso para verlo.</p>";
                }

                unset($_SESSION['profile_owner_id_for_section']);
                unset($_SESSION['is_own_profile_for_section']);
                ?>
            </div>
        </div>
     </div>
</body>
</html>