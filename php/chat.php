<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles_chat.css">
    <title>X titulo</title>
</head>

<body>
    <header>
        <!-- Agregar NavBar Aquí -->
    </header>
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <input type="text" placeholder="Buscar..." class="search-bar">
            <hr>
            <ul id="contact-list" class="contact-list">
                <li class="contact" data-contact="Vendedor1">
                    <img src="../recursos/gato1.jpg" alt="Vendedor1">
                    <span>Miguel Nuñez</span>
                </li>
                <li class="contact" data-contact="Vendedor2">
                    <img src="../recursos/huron.jpg" alt="Vendedor2">
                    <span>Juan Pérez</span>
                </li>
            </ul>
        </div>

        <!-- Zona de chat -->
        <div class="chat-box">
            <div class="chat-header">
                <img id="chat-img" src="../recursos/gato2.jpeg" alt="Vendedor">
                <span id="chat-name">Selecciona un vendedor</span>
            </div>
            <div class="chat-content" id="chat-content">
                <p>Selecciona un contacto para empezar a chatear.</p>
            </div>
            <div class="chat-input">
                <button id="quote-btn">+</button>
                <input type="text" id="message-input" placeholder="Escribe un mensaje...">
                <button id="send-btn">Enviar</button>
            </div>
        </div>

    </div>
    <script src="../js/script_chat.js"></script>
</body>

</html>