<?php
//session_start();
if (isset($_SESSION['nombre_usuario'])) {
    $idUsuarioActual = $_SESSION['email'];
    $nombreUsuarioActual = $_SESSION['nombre_usuario'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles_chat.css">
    <title>X titulo</title>
</head>

<header>
    <?php include '../vistas/navbar.php'; ?>
</header>

<body>

    <div id="main-content"
        class="flex justify-center transition-all duration-300 p-6 bg-orange-100 min-h-screen overflow-hidden">

        <div class="chat-container">
            <!-- Sidebar -->
            <div class="sidebar">
                <input type="text" placeholder="Buscar..." id="search-bar" class="search-bar">
                <hr>
                <ul id="contact-list" class="contact-list">
                    <!--Aquí se llenan los contactos-->
                </ul>
            </div>

            <!-- Zona de chat -->
            <div class="chat-box">
                <div class="chat-header">
                    <img id="chat-img" src="../recursos/productos/gato2.jpeg" alt="Vendedor">
                    <span id="chat-name">Selecciona un contacto</span>
                </div>

                <div class="chat-content" id="chat-content">
                    <!--modal para crear cotización-->
                    <div id="quote-modal"></div>
                    <div id="quote-container">
                        <span class="close" id="close-modal">&times;</span>
                        <h2>Crear Cotización</h2>
                        <form id="quote-form" method="post">
                            <div class="form-group">
                                <input type="hidden" name="id_comprador" id="id_comprador" value="">
                                <div class="section-1">
                                    <label for="product-name">Producto:</label>
                                    <select id="product-name" name="product-name" required>
                                        <option value="">-- Selecciona uno --</option>
                                        <!-- Aquí se llenan los productos dinámicamente -->
                                    </select><br>
                                    <label for="product-description">Descripción:</label>
                                    <textarea id="product-description" name="product-description" maxlength="200" required></textarea>
                                    <label for="product-quantity">Cantidad:</label>
                                    <input type="number" id="product-quantity" name="product-quantity" min="1" required>
                                    <label for="product-inventory">Inventario disponible:</label>
                                    <input type="text" id="product-inventory" name="product-inventory" value="#"readonly><br>
                                    <label for="product-price">Precio Total:</label>
                                    <input type="number" id="product-price" name="product-price" min="0" required><br>
                                    <button type="submit" style="margin-left:0px">Enviar Cotización</button>
                                </div>
                                <div class="section-2">
                                    <label for="product-image">Imagen del Producto:</label>
                                    <img id="product-image" src="../recursos/productos/gato1.jpg"><br>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- modal para ver detalles de la cotización-->
                    <div id="cotizacion-modal" class="modal hidden"></div>
                    <div id="modal-content" class="modal-content">
                        <h3 class="modal-header">Detalles de la Cotización</h3>
                        <span class="close-btn">&times;</span>
                        <div class="modal-body">
                            <img id="modal-img" src="" alt="Imagen producto" class="modal-img">
                            <div class="modal-info">
                                <p><strong>ID:</strong> <span id="id-cotizacion"></span></p>
                                <p><strong>Producto:</strong> <span id="modal-producto"></span></p>
                                <p><strong>Cantidad:</strong> <span id="modal-unidades"></span></p>
                                <p><strong>Precio total:</strong> $<span id="modal-precio"></span></p>
                                <p><strong>Descripción:</strong></p>
                                <p id="modal-detalles"></p>
                                <button id="modal-continuar" class="continue-btn">Continuar</button>
                            </div>
                        </div>
                    </div>
                    <div id="message-container" class="message-container">
                        <p>Selecciona un contacto para empezar a chatear.</p>
                    </div>
                </div>

                <div class="chat-input">
                    <?php if (!isset($_SESSION['nombre_usuario']))
                        return; ?>
                    <?php if (($_SESSION['tipo']) === 'Vendedor'): ?>
                        <button id="quote-btn" disabled>+</button>
                    <?php endif; ?>
                    <input type="text" id="message-input" placeholder="Escribe un mensaje..." disabled>
                    <button id="send-btn" disabled>Enviar</button>
                </div>

            </div>
        </div>



    </div>

    <script src="../js/script_chat.js"></script>

</body>

</html>