document.addEventListener("DOMContentLoaded", () => {
    const productList = document.getElementById("myProduct-list");
    const searchBar = document.getElementById("search-bar");
    const filterStatus = document.getElementById("filter-status");

    const products = [
        { id: 1, name: "Alimento para gato 3Kg", price: "$280", status: "Aceptado", image: "../recursos/categorias/alimentoscat.jpg" },
        { id: 2, name: "Correa", price: "$80", status: "Pendiente", image: "../recursos/categorias/correas.jpg" },
        { id: 3, name: "Gatos", price: "$500", status: "Rechazado", image: "../recursos/productos/gato2.jpeg" },
        { id: 4, name: "Jaula para mascotas", price: "$120", status: "Aceptado", image: "../recursos/categorias/jaulas.jpg" },
        { id: 5, name: "Jaula para masacuatas", price: "$120", status: "Rechazado", image: "../recursos/categorias/jaulas.jpg" }
    ];

    function renderProducts(filter = "all", searchQuery = "") {
        productList.innerHTML = "";
        let filteredProducts = products.filter(product => {
            return (filter === "all" || product.status === filter) &&
                product.name.toLowerCase().includes(searchQuery.toLowerCase());
        });

        filteredProducts.forEach(product => {
            const productItem = document.createElement("div");
            productItem.classList.add("product-item");

            productItem.innerHTML = `
                <img src="${product.image}" alt="${product.name}">
                <div class="product-details">
                    <div class="product-split">
                        <a href="#" class="link-item">${product.name}</a>
                        <label class="product-date">[Fecha de publicacion]</label>
                    </div>
                    <p>Precio:<label style="color: #4caf50;"> ${product.price}</label></p>
                    <div class="product-split">
                        <p class="status ${product.status}">${product.status}</p>
                        <button class="details-btn">Detalles</button>
                    </div>
                </div>
            `;

            productList.appendChild(productItem);
        });
    }

    searchBar.addEventListener("input", () => {
        renderProducts(filterStatus.value, searchBar.value);
    });

    filterStatus.addEventListener("change", () => {
        renderProducts(filterStatus.value, searchBar.value);
    });

    renderProducts();
});
