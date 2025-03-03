document.addEventListener("DOMContentLoaded", () => {
    const ordersList = document.getElementById("orders-list");
    const searchBar = document.getElementById("search-bar");
    const filterStatus = document.getElementById("filter-status");

    const orders = [
        { id: 1, name: "Alimento para perro", seller: "PetCo", price: "$200", status: "Entregado", image: "../recursos/categorias/alimentoscat.jpg" },
        { id: 2, name: "Correa", seller: "Veterinarias Allende", price: "$80", status: "Pendiente", image: "../recursos/categorias/correas.jpg" },
        { id: 3, name: "Juguete para gato", seller: "Tienda de Mascotas", price: "$50", status: "Transportado", image: "../recursos/categorias/juguetes.jpg" },
        { id: 4, name: "Cama para perro", seller: "Pet Shop", price: "$120", status: "Entregado", image: "../recursos/categorias/jaulas.jpg" }
    ];

    function renderOrders(filter = "all", searchQuery = "") {
        ordersList.innerHTML = "";
        let filteredOrders = orders.filter(order => {
            return (filter === "all" || order.status === filter) &&
                order.name.toLowerCase().includes(searchQuery.toLowerCase());
        });

        filteredOrders.forEach(order => {
            const orderItem = document.createElement("div");
            orderItem.classList.add("order-item");

            orderItem.innerHTML = `
                <img src="${order.image}" alt="${order.name}">
                <div class="order-details">
                    <div class="order-split">
                        <a href="#" class="link-item">${order.name}</a>
                        <label class="order-date">[Fecha de pedido]</label>
                    </div>
                    <p>Vendedor: <a href="#" class="link-seller">${order.seller}</a></p>
                    <p>Precio:<label style="color: #4caf50;"> ${order.price}</label></p>
                    <div class="order-split">
                        <p class="status ${order.status}">${order.status}</p>
                        <button class="details-btn">Detalles</button>
                    </div>
                </div>
            `;

            ordersList.appendChild(orderItem);
        });
    }

    searchBar.addEventListener("input", () => {
        renderOrders(filterStatus.value, searchBar.value);
    });

    filterStatus.addEventListener("change", () => {
        renderOrders(filterStatus.value, searchBar.value);
    });

    renderOrders();
});
