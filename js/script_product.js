// Cambiar imagen principal al hacer clic en una miniatura
function changeImage(element) {
    document.getElementById("main-img").src = element.src;
}

// Sistema de valoración con estrellas
function rateProduct(rating) {
    const stars = document.querySelectorAll(".star");
    stars.forEach((star, index) => {
        star.classList.toggle("active", index < rating);
    });
    document.getElementById("rating-text").textContent = rating.toFixed(1);
}

// Manejo de comentarios
document.getElementById("add-comment").addEventListener("click", () => {
    const commentInput = document.getElementById("comment-input");
    const commentText = commentInput.value.trim();
    if (commentText === "") return;

    const commentList = document.getElementById("comments-list");
    const commentElement = document.createElement("p");
    commentElement.textContent = commentText;
    commentList.appendChild(commentElement);

    commentInput.value = "";
});
