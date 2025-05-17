<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Producto</title>
    <link rel="stylesheet" href="../css/styles_product.css">
</head>

<header>
<?php include('navbar.php'); ?>
</header>
<body>
<div id="main-content" class="flex-1 transition-all duration-300 p-6 bg-orange-100 ml-64 min-h-screen">
    <div class="product-container">
        <!-- Galería de imágenes -->
        <div class="image-gallery">
            <div class="thumbnails">
                <img src="../recursos/categorias/alimentoscat.jpg" class="thumb" onclick="changeImage(this)">
                <img src="../recursos/categorias/jaulas.jpg" class="thumb" onclick="changeImage(this)">
                <img src="../recursos/categorias/correas.jpg" class="thumb" onclick="changeImage(this)">
            </div>
            <div class="main-image">
                <img id="main-img" src="../recursos/categorias/alimentoscat.jpg">
            </div>
        </div>

        <!-- Información del producto -->
        <div class="product-info">
            <h1 id="product-title">Accesorios para mascotas</h1>
            <p>publicado por <span id="product-seller">PetCo</span></p>
            <p class="price">$<span id="product-price">200</span></p>
            <button id="add-to-cart">Añadir al carrito</button>

            <!-- Valoración -->
            <div class="rating">
                <span class="star" onclick="rateProduct(1)">★</span>
                <span class="star" onclick="rateProduct(2)">★</span>
                <span class="star" onclick="rateProduct(3)">★</span>
                <span class="star" onclick="rateProduct(4)">★</span>
                <span class="star" onclick="rateProduct(5)">★</span>
                <span id="rating-text">0.0</span>
            </div>
        </div>
    </div>

    <!-- Sección de comentarios -->
    <div class="comments-section">
        <h2>Comentarios</h2>
        <div id="comments-list">
            <!-- Comentarios dinámicos -->
        </div>
        <textarea id="comment-input" placeholder="Escribe un comentario..."></textarea>
        <button id="add-comment">Enviar</button>
    </div>

    <script src="../js/script_product.js"></script>
</div>
</body>
</html>
