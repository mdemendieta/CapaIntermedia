<?php
// controladores/ProfileController.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../modelos/conexion.php';
require_once '../modelos/ProfileModel.php';
require_once '../modelos/ListaModel.php';
require_once '../modelos/PedidoModel.php';
require_once '../modelos/ProductoModel.php';

class ProfileController {
    private $db;
    private $profileModel;
    private $listaModel;
    private $pedidoModel;
    private $productoModel;

    public function __construct() {
        $this->db = new Database(); // Instancia de conexión
        $conexion = $this->db->getConexion(); // Obtener el objeto mysqli

        $this->profileModel = new ProfileModel(); // ProfileModel crea su propia conexión
        $this->listaModel = new ListaModel($conexion);
        $this->pedidoModel = new PedidoModel($conexion);
        $this->productoModel = new ProductoModel($conexion);
    }

    public function index() {
        if (!isset($_SESSION['id_usuario'])) {
            header("Location: ../php/landing.php");
            exit;
        }
        $id_usuario = $_SESSION['id_usuario'];
        $userData = $this->profileModel->getUserProfileData($id_usuario);
        $seccionActiva = $_GET['seccion'] ?? 'listas';

        include '../vistas/profile_view.php';
    }

    public function loadSection() {
        if (!isset($_SESSION['id_usuario'])) {
            http_response_code(403);
            echo "Acceso denegado. Debes iniciar sesión.";
            exit;
        }
        $id_usuario = $_SESSION['id_usuario'];
        $seccion = $_GET['s'] ?? 'listas';

        // Manejo de POSTs específicos para cada sección
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $resultado = ['success' => false, 'mensaje' => 'Acción no válida.'];

            if ($seccion === 'listas' && isset($_POST['nombreLista'])) {
                $nombreLista = trim($_POST['nombreLista']);
                $descripcion = trim($_POST['descripcion'] ?? '');
                $publica = isset($_POST['publica']) ? 1 : 0;
                $resultado = $this->listaModel->crearLista($id_usuario, $nombreLista, $descripcion, $publica);
            } elseif ($seccion === 'productospubli' && isset($_POST['id_producto_a_lista']) && isset($_POST['id_lista_destino'])) {
                $id_producto = filter_var($_POST['id_producto_a_lista'], FILTER_VALIDATE_INT);
                $id_lista = filter_var($_POST['id_lista_destino'], FILTER_VALIDATE_INT);
                if ($id_producto && $id_lista) {
                    $resultado = $this->productoModel->agregarProductoALista($id_producto, $id_lista, $id_usuario);
                } else {
                    $resultado = ['success' => false, 'mensaje' => 'Datos inválidos para añadir a lista.'];
                }
            }
            echo json_encode($resultado);
            exit;
        }

        // Carga de secciones vía AJAX GET
        switch ($seccion) {
            case 'listas':
                $listas = $this->listaModel->getListasUsuario($id_usuario);
                include '../vistas/parciales/mislistas_partial_view.php';
                break;
            case 'historial':
                $filtros_pedidos = [
                    'termino_busqueda' => $_GET['q_pedido'] ?? null,
                    'categoria' => $_GET['cat_pedido'] ?? null,
                    'estado' => $_GET['est_pedido'] ?? null,
                    'fecha_inicio' => $_GET['fi_pedido'] ?? null,
                    'fecha_fin' => $_GET['ff_pedido'] ?? null,
                ];
                $pedidos = $this->pedidoModel->getHistorialPedidosUsuario($id_usuario, $filtros_pedidos);
                $categorias_filtro_pedidos = $this->pedidoModel->getCategoriasFiltro();
                include '../vistas/parciales/orders_partial_view.php';
                break;
            case 'productospubli':
                $filtros_prod_pub = [
                    'termino_busqueda' => $_GET['q_prodpub'] ?? null,
                    'categoria' => $_GET['cat_prodpub'] ?? null,
                    'rango_precio' => $_GET['precio_prodpub'] ?? null,
                ];
                $productosPublicados = $this->productoModel->getProductosPublicadosPorVendedor($id_usuario, $filtros_prod_pub);
                $listasDelUsuario = $this->productoModel->getListasParaProducto($id_usuario);
                $categorias_filtro_prod = $this->productoModel->getCategoriasActivas();
                include '../vistas/parciales/listaproductos_partial_view.php';
                break;
            case 'productospend':
                $filtros_prod_pend = [
                    'termino_busqueda' => $_GET['q_prodpend'] ?? null,
                    'estado_producto' => $_GET['est_prodpend'] ?? 'all',
                ];
                $productosPendientes = $this->productoModel->getProductosPorEstadoVendedor($id_usuario, $filtros_prod_pend);
                include '../vistas/parciales/myproducts_partial_view.php';
                break;
            case 'reportes':
                $filtros_reporte = [
                    'mes_anio' => $_GET['mes_reporte'] ?? date('Y-m'),
                ];
                $resumenVentas = $this->pedidoModel->getResumenVentasVendedor($id_usuario, $filtros_reporte);
                include '../vistas/parciales/reportes_partial_view.php';
                break;
            default:
                echo "<p>Sección no encontrada.</p>";
                break;
        }
    }
}

$action = $_GET['action'] ?? 'index';
$controller = new ProfileController();

if (method_exists($controller, $action)) {
    $controller->$action();
} else {
    http_response_code(404);
    echo "Acción no encontrada en ProfileController.";
}
?>