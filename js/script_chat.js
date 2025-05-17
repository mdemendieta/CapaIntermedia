document.addEventListener("DOMContentLoaded", () => {
    const contactList = document.getElementById("contact-list");
    const chatName = document.getElementById("chat-name");
    const chatImg = document.getElementById("chat-img");
    const chatContent = document.getElementById("message-container");
    const messageInput = document.getElementById("message-input");
    const sendBtn = document.getElementById("send-btn");
    const quoteBtn = document.getElementById("quote-btn");
    const modal = document.getElementById("quote-modal");
    const modalContent = document.getElementById("quote-container");
    const closeModal = document.getElementById("close-modal");
    const searchBar = document.getElementById("search-bar");
    let currentContact = null;

    // Lista de contactos predefinidos
    let contacts = [
        { name: "Miguel Nuñez", id: "Vendedor1", img: "../recursos/productos/gato1.jpg" },
        { name: "Juan Pérez", id: "Vendedor2", img: "../recursos/productos/huron.jpg" }
    ];

    fetch('../modelos/contactos.php')
        .then(response => response.json())
        .then(data => {
            contacts = data;
            renderContacts(); // Renderizamos cuando ya tenemos los contactos
        })
        .catch(error => {
            console.error('Error cargando contactos:', error);
        });

    function openChat(contactId) {
        currentContact = contactId; // ya no "chat_" porque ahora es de base de datos
        chatContent.innerHTML = "<p>Cargando mensajes...</p>";

        //habilitar el botones de chat
        sendBtn.disabled = false;
        if (quoteBtn) quoteBtn.disabled = false;
        messageInput.disabled = false;

        fetch('../modelos/cargarmensajes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ contactId: contactId })
        })
            .then(response => response.text())
            .then(text => {
                //console.log("Respuesta cruda:", text);
                const messages = JSON.parse(text);
                chatContent.innerHTML = "";
                messages.forEach(msg => {
                    const div = document.createElement("div");
                    if (msg.tipo === 'mensaje') {
                        div.classList.add(msg.sender === 'yo' ? 'user-msg' : 'contact-msg');
                        div.textContent = msg.texto;
                    } else if (msg.tipo === 'cotizacion') {
                        const UsuarioActual = msg.sender === 'yo';
                        div.classList.add('quote-wrapper', UsuarioActual ? 'quote-right' : 'quote-left');
                        msg = `
                            <div class="quote-msg">
                                <img src="${msg.imagen}" alt="Producto">
                                <div class="section">
                                 <p><strong>Producto:</strong> ${msg.nombre}</p>
                                 <p><strong>Cantidad:</strong> ${msg.unidades}</p>
                                 <p><strong>Precio total:</strong> $${msg.precio}</p>
                                 ${msg.estado === 'Rechazado' || msg.estado === 'Terminado' ?
                                '<button class="details disabled" disabled style="margin-left:0px">Expirado</button>' :
                                `<button class="details" data-producto='${JSON.stringify(msg)}' style="margin-left:0px">Ver detalles</button>`}
                                </div>
                            </div>
                        `;
                        div.innerHTML += msg;
                    }
                    chatContent.appendChild(div);
                });
            })
            .catch(error => {
                console.error('Error cargando mensajes:', error);
            });
    }

    if (quoteBtn) {
        quoteBtn.addEventListener("click", quoteModal);
    }
    function quoteModal() {
        // definir modal como display: block
        modal.style.display = "block";
        modalContent.style.display = "block";
    }

    addEventListener("click", (e) => {
        if (e.target === modal || e.target === closeModal) {
            modal.style.display = "none";
            modalContent.style.display = "none";
        }
    });

    function sendMessage() {
        const text = messageInput.value.trim();
        if (!text || !currentContact) return;

        fetch('../modelos/enviarmensaje.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                contactId: currentContact,
                mensaje: text
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const div = document.createElement("div");
                    div.classList.add("user-msg");
                    div.textContent = text;
                    chatContent.appendChild(div);
                    messageInput.value = "";
                } else {
                    alert('Error enviando el mensaje.');
                }
            })
            .catch(error => {
                console.error('Error enviando mensaje:', error);
            });
    }

    // llenar el select con los productos del vendedor
    window.addEventListener('DOMContentLoaded', async () => {
        const select = document.querySelector('#product-name');
        const response = await fetch('../controladores/productos_vendedor.php');
        const productos = await response.json();
        //console.log(productos);

        if (productos.error) {
            console.error('Error:', productos.error);
            return;
        }


        productos.forEach(producto => {
            const opt = document.createElement('option');
            opt.value = producto.id_producto;
            opt.textContent = producto.nombre;
            select.appendChild(opt);
        });
    });

    document.querySelector('#product-name').addEventListener('change', async function () {
        const idProducto = this.value;
        if (!idProducto) return;

        const response = await fetch(`../controladores/productos_vendedor.php?id=${idProducto}`);
        const producto = await response.json();
        console.log(producto);

        if (producto) {
            document.querySelector('#product-inventory').value = producto.inventario;
            document.querySelector('#product-image').src = producto.imagen;
        }
    });


    contactList.addEventListener("click", (e) => {
        const contactElement = e.target.closest("li");
        if (!contactElement) return;

        const contactId = contactElement.dataset.contact;
        if (!contactId) return;

        chatName.textContent = contactElement.querySelector("span").textContent;
        chatImg.src = contactElement.querySelector("img")?.src || "../recursos/default.jpg";
        openChat(contactId);

    });


    function renderContacts(searchQuery = "") {
        contactList.innerHTML = "";
        const filteredContacts = contacts.filter(contact =>
            contact.name.toLowerCase().includes(searchQuery.toLowerCase())
        );

        filteredContacts.forEach(contact => {
            const li = document.createElement("li");
            li.classList.add("contact");
            li.dataset.contact = contact.id;

            li.innerHTML = `
                <img src="${contact.img}" alt="${contact.name}">
                <span>${contact.name}</span>
            `;

            contactList.appendChild(li);
        });


        contactList.querySelectorAll(".contact").forEach(contactItem => {
            contactItem.addEventListener("click", () => {
                currentContact = contactItem.dataset.contact;
                document.querySelector('#id_comprador').value = currentContact;
                console.log("Cliente seleccionado (ID):", currentContact);
            });
        });

    }

    searchBar.addEventListener("input", () => {
        renderContacts(searchBar.value);
    });

    sendBtn.addEventListener("click", sendMessage);
    messageInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") sendMessage();
    });

    renderContacts(); // Renderiza los contactos al cargar la página

    // Crear mensaje de cotización
    document.querySelector('#quote-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const cantidad = parseInt(form['product-quantity'].value);
        const inventario = parseInt(form['product-inventory'].value);

        // Validar cantidad con el inventario
        if (cantidad > inventario) {
            alert('La cantidad excede el inventario disponible.');
            return;
        }

        const response = await fetch('../controladores/CotizacionController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            const msg = `
            <div class="quote-wrapper quote-right">
            <div class="quote-msg">
                <img src="${result.producto.imagen}" alt="Producto">
                <div class="section">
                 <p><strong>Producto:</strong> ${result.producto.nombre}</p>
                 <p><strong>Cantidad:</strong> ${result.producto.unidades}</p>
                 <p><strong>Precio total:</strong> $${result.producto.precio}</p>
                 <button class="details" data-producto='${JSON.stringify(result.producto)}'>Ver detalles</button>
                </div>
            </div>
            </div>
        `;

            chatContent.innerHTML += msg;
            form.reset();
            modal.style.display = "none";
            modalContent.style.display = "none";

        } else {
            alert(result.mensaje);
        }
    });

    // Delegado para botón de "ver detalles"
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('details')) {
            const data = JSON.parse(e.target.dataset.producto);

            // Asignar datos al modal
            document.getElementById('modal-img').src = data.imagen;
            document.getElementById('id-cotizacion').textContent = data.id;
            document.getElementById('modal-producto').textContent = data.nombre;
            document.getElementById('modal-unidades').textContent = data.unidades;
            document.getElementById('modal-precio').textContent = data.precio;
            document.getElementById('modal-detalles').textContent = data.detalles || " [ Sin descripción. ]";

            // Mostrar el modal
            document.getElementById('cotizacion-modal').classList.remove('hidden');
            document.getElementById('modal-content').style.display = "flex";

            // Mostrar botón correspondiente
            const btnContinuar = document.getElementById('modal-continuar');
            if (data.sender == 'otro') {
                btnContinuar.textContent = 'Continuar';
                btnContinuar.onclick = () => {
                    // Abrir la página de pago
                    //window.location.href = `pagina_pago.php?id=${data.id_cotizacion}`;
                    alert('Aqui se abriría la página de pago');
                };
            } else if (data.sender == 'yo') {
                btnContinuar.textContent = 'Cancelar cotización';
                btnContinuar.onclick = () => cancelarCotizacion(data.id, e.target);
            }
        }
    });

    // Cerrar modal al hacer click en la X o fuera del contenido
    addEventListener("click", (e) => {
        if (e.target.classList.contains('close-btn') || e.target.classList.contains('modal')) {
            document.getElementById('cotizacion-modal').classList.add('hidden');
            document.getElementById('modal-content').style.display = "none";
        }
    });


    function cancelarCotizacion(idCotizacion, botonDetalle) {
        if (!confirm('¿Estás seguro de que quieres cancelar esta cotización?')) return;

        fetch('../modelos/cancelar_cotizacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cotizacion: idCotizacion })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Cotización cancelada');
                    document.getElementById('cotizacion-modal').classList.add('hidden');

                    // Cambiar botón a "Expirado"
                    botonDetalle.textContent = 'Expirado';
                    botonDetalle.disabled = true;
                    botonDetalle.classList.add('disabled');
                } else {
                    alert('Error al cancelar la cotización');
                }
            });
    }




});
