document.addEventListener("DOMContentLoaded", () => {
    const contactList = document.getElementById("contact-list");
    const chatNameUser = document.getElementById("chat-username");
    const chatName = document.getElementById("chat-name");
    const chatImg = document.getElementById("chat-img");
    const chatContent = document.getElementById("message-container"); // Contenedor de mensajes individuales
    const chatBoxContent = document.getElementById("chat-content"); // Contenedor general del chat (para scroll)
    const messageInput = document.getElementById("message-input");
    const sendBtn = document.getElementById("send-btn");
    const quoteBtn = document.getElementById("quote-btn");
    const modal = document.getElementById("quote-modal");
    const modalContent = document.getElementById("quote-container");
    const closeModalBtn = document.getElementById("close-modal"); // Corregido de closeModal a closeModalBtn
    const searchBar = document.getElementById("search-bar");
    let currentContactId = null;
    let contacts = [];

    function getContactIdFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('contactId');
    }

    async function fetchContactDetails(contactId) {
        try {
            const response = await fetch(`../modelos/detalles_usuario.php?id=${contactId}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            if (data.success) {
                return data.contact;
            } else {
                console.warn(data.error);
                return null;
            }
        } catch (error) {
            console.error("Error fetching contact details:", error);
            return null;
        }
    }

    function activateChat(contact) {
        if (!contact) {
            chatNameUser.textContent = "Selecciona un contacto";
            chatName.textContent = "";
            chatImg.src = "../recursos/perfilvacio.jpg";
            messageInput.disabled = true;
            sendBtn.disabled = true;
            if (quoteBtn) quoteBtn.disabled = true;
            currentContactId = null;
            chatContent.innerHTML = "<p>Selecciona un contacto para empezar a chatear.</p>";
            history.pushState({}, '', 'chat.php'); // Limpiar contactId de la URL si no hay contacto
            return;
        }

        currentContactId = contact.id;
        chatNameUser.textContent = contact.username;
        chatName.textContent = `(${contact.name})`;
        chatImg.src = contact.img || "../recursos/perfilvacio.jpg";

        messageInput.disabled = false;
        sendBtn.disabled = false;
        if (quoteBtn) quoteBtn.disabled = false;
         // Asignar el ID del comprador al campo oculto del formulario de cotización
        const idCompradorInput = document.getElementById('id_comprador');
        if (idCompradorInput) {
            idCompradorInput.value = currentContactId;
        }


        openChatMessages(contact.id);
        history.pushState({ contactId: contact.id }, `Chat con ${contact.username}`, `chat.php?contactId=${contact.id}`);
    }

    // Cargar contactos existentes (con conversaciones previas)
    fetch('../modelos/contactos.php')
        .then(response => response.json())
        .then(async data => { // Hacer esta función async para poder usar await dentro
            if (data.error) {
                console.error('Error cargando contactos existentes:', data.error);
                contacts = [];
            } else {
                contacts = data;
            }
            renderContacts();

            const contactIdFromUrl = getContactIdFromUrl();
            if (contactIdFromUrl) {
                let contactToOpen = contacts.find(c => c.id.toString() === contactIdFromUrl.toString());
                if (contactToOpen) {
                    activateChat(contactToOpen);
                } else {
                    // Si no está en contactos existentes, es un nuevo chat potencial
                    console.log(`Contacto ${contactIdFromUrl} no encontrado en lista, intentando obtener detalles...`);
                    const userDetails = await fetchContactDetails(contactIdFromUrl);
                    if (userDetails) {
                        // Es un nuevo contacto para la UI, no se añade a la lista 'contacts' permanentemente aquí
                        activateChat(userDetails);
                    } else {
                        chatNameUser.textContent = "Contacto no encontrado";
                        chatName.textContent = "";
                        chatImg.src = "../recursos/perfilvacio.jpg";
                        chatContent.innerHTML = "<p>No se pudo iniciar el chat. El usuario podría no existir.</p>";
                    }
                }
            } else {
                // Estado inicial si no hay contactId en la URL
                activateChat(null);
            }
        })
        .catch(error => {
            console.error('Error crítico cargando contactos existentes:', error);
            contacts = [];
            renderContacts();
            activateChat(null); // Estado por defecto en caso de error
        });

    function openChatMessages(contactId) {
        currentContactId = contactId;
        chatContent.innerHTML = "<p>Cargando mensajes...</p>";

        fetch('../modelos/cargarmensajes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ contactId: contactId })
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(messages => {
            chatContent.innerHTML = "";
            if (messages.error) {
                chatContent.innerHTML = `<p>${messages.error}</p>`;
                return;
            }
            if (!Array.isArray(messages) || messages.length === 0) {
                // Esto es normal si es un chat nuevo (obtenido por URL y fetchContactDetails)
                chatContent.innerHTML = "<p id='no-msg'>Aún no hay mensajes. ¡Envía el primero!</p>";
            } else {
                messages.forEach(msg => {
                    const div = document.createElement("div");
                    if (msg.tipo === 'mensaje') {
                        div.classList.add(msg.sender === 'yo' ? 'user-msg' : 'contact-msg');
                        div.textContent = msg.texto;
                    } else if (msg.tipo === 'cotizacion') {
                        const UsuarioActualEsRemitente = msg.sender === 'yo';
                        div.classList.add('quote-wrapper', UsuarioActualEsRemitente ? 'quote-right' : 'quote-left');
                        const quoteHTML = `
                            <div class="quote-msg">
                                <img src="${msg.imagen || '../recursos/placeholder.png'}" alt="Producto">
                                <div class="section">
                                 <p><strong>Producto:</strong> ${msg.nombre || 'N/A'}</p>
                                 <p><strong>Cantidad:</strong> ${msg.unidades || 'N/A'}</p>
                                 <p><strong>Precio total:</strong> $${msg.precio || 'N/A'}</p>
                                 ${msg.estado === 'Rechazado' || msg.estado === 'Terminado' ?
                                '<button class="details disabled" disabled style="margin-left:0px">Expirado</button>' :
                                `<button class="details" data-producto='${JSON.stringify(msg).replace(/'/g, "&apos;")}' style="margin-left:0px">Ver detalles</button>`}
                                </div>
                            </div>
                        `;
                        div.innerHTML = quoteHTML;
                    }
                    chatContent.appendChild(div);
                });
            }
            if (chatBoxContent) chatBoxContent.scrollTop = chatBoxContent.scrollHeight;
        })
        .catch(error => {
            console.error('Error cargando mensajes:', error);
            chatContent.innerHTML = "<p>Error al cargar mensajes. Intenta de nuevo más tarde.</p>";
        });
    }


    if (quoteBtn) {
        quoteBtn.addEventListener("click", () => {
            if (currentContactId) {
                modal.style.display = "block";
                modalContent.style.display = "block";
            } else {
                alert("Por favor, selecciona un contacto para cotizar.");
            }
        });
    }
    
    function closeModalWindows() {
        if (modal) modal.style.display = "none";
        if (modalContent) modalContent.style.display = "none";
        const cotizacionModal = document.getElementById('cotizacion-modal');
        const modalContentDetalles = document.getElementById('modal-content'); // Este es el de detalles, renombrar si confunde
        if (cotizacionModal) cotizacionModal.classList.add('hidden');
        if (modalContentDetalles) modalContentDetalles.style.display = "none";
    }

    if (closeModalBtn) { // Asegurarse que closeModalBtn exista
        closeModalBtn.addEventListener("click", closeModalWindows);
    }
    
    const closeDetailsBtn = document.querySelector('#modal-content .close-btn'); // modal-content es el de detalles de cotización
    if (closeDetailsBtn) {
        closeDetailsBtn.addEventListener('click', closeModalWindows);
    }

    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) closeModalWindows();
        });
    }
    const cotizacionModalBackground = document.getElementById('cotizacion-modal');
    if (cotizacionModalBackground) {
        cotizacionModalBackground.addEventListener("click", (e) => {
            if (e.target === cotizacionModalBackground) closeModalWindows();
        });
    }

    function sendMessage() {
        const text = messageInput.value.trim();
        if (!text || !currentContactId) return;

        fetch('../modelos/enviarmensaje.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ contactId: currentContactId, mensaje: text })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const div = document.createElement("div");
                div.classList.add("user-msg");
                div.textContent = text;

                const noMessagesP = chatContent.querySelector("p");
                if (noMessagesP && (noMessagesP.textContent.includes("Aún no hay mensajes") || noMessagesP.textContent.includes("Selecciona un contacto"))) {
                    chatContent.innerHTML = ""; // Limpiar mensaje inicial
                    chatContent.appendChild(div);
                } else {
                    chatContent.appendChild(div);
                }
                messageInput.value = "";
                if (chatBoxContent) chatBoxContent.scrollTop = chatBoxContent.scrollHeight;

                // Si era un chat "nuevo" (solo por URL), ahora que se envió un mensaje,
                // el contacto debería aparecer en la lista de `contactos.php` la próxima vez.
                // Para actualizar la lista de contactos inmediatamente:
                const isNewContactInUI = !contacts.some(c => c.id.toString() === currentContactId.toString());
                if (isNewContactInUI) {
                    // Volver a cargar los contactos para incluir el nuevo.
                    fetch('../modelos/contactos.php')
                        .then(res => res.json())
                        .then(updatedContacts => {
                            if (!updatedContacts.error) {
                                contacts = updatedContacts;
                                renderContacts(searchBar.value); // Re-renderizar con el filtro actual
                            }
                        });
                }

            } else {
                alert('Error enviando el mensaje: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error enviando mensaje:', error);
            alert('Error de conexión al enviar mensaje.');
        });
    }

    // Carga de productos para el select de cotización
    const productNameSelect = document.querySelector('#product-name');
    if (productNameSelect && (document.getElementById('quote-btn') != null) ) { // Solo si el select existe (Vendedor)
        window.addEventListener('DOMContentLoaded', async () => {
            try {
                const response = await fetch('../controladores/productos_vendedor.php');
                const productos = await response.json();
                if (productos.error) {
                    console.error('Error al cargar productos del vendedor:', productos.error);
                    return;
                }
                if (productos && productos.length > 0) {
                    productos.forEach(producto => {
                        const opt = document.createElement('option');
                        opt.value = producto.id_producto;
                        opt.textContent = producto.nombre;
                        productNameSelect.appendChild(opt);
                    });
                }
            } catch (error) {
                console.error('Error en fetch de productos del vendedor:', error);
            }
        });

        productNameSelect.addEventListener('change', async function () {
            const idProducto = this.value;
            const productImage = document.querySelector('#product-image');
            const productInventory = document.querySelector('#product-inventory');

            if (!idProducto) {
                if(productImage) productImage.src = '../recursos/placeholder.png';
                if(productInventory) productInventory.value = '#';
                return;
            }
            try {
                const response = await fetch(`../controladores/productos_vendedor.php?id=${idProducto}`);
                const producto = await response.json();
                if (producto && !producto.error) {
                    if(productInventory) productInventory.value = producto.inventario || '0';
                    if(productImage && producto.imagen) productImage.src = producto.imagen;
                    else if(productImage) productImage.src = '../recursos/placeholder.png';
                } else {
                    console.error("Error obteniendo detalle del producto:", producto.error);
                    if(productInventory) productInventory.value = 'Error';
                    if(productImage) productImage.src = '../recursos/placeholder.png';
                }
            } catch (error) {
                console.error('Error en fetch detalle producto:', error);
                 if(productInventory) productInventory.value = 'Error';
                 if(productImage) productImage.src = '../recursos/placeholder.png';
            }
        });
    }


    contactList.addEventListener("click", (e) => {
        const contactElement = e.target.closest("li.contact");
        if (!contactElement) return;
        const contactId = contactElement.dataset.contact;
        const selectedContact = contacts.find(c => c.id.toString() === contactId.toString());
        if (selectedContact) {
            activateChat(selectedContact);
        }
    });

    function renderContacts(searchQuery = "") {
        contactList.innerHTML = "";
        if (!Array.isArray(contacts)) {
            contactList.innerHTML = "<li>Error al cargar contactos.</li>";
            return;
        }
        const query = searchQuery.toLowerCase();
        const filteredContacts = contacts.filter(contact =>
            (contact.username && contact.username.toLowerCase().includes(query)) ||
            (contact.name && contact.name.toLowerCase().includes(query))
        );

        if (filteredContacts.length === 0) {
            contactList.innerHTML = searchQuery ? "<li>No se encontraron contactos.</li>" : "<li>No tienes conversaciones activas.</li>";
            return;
        }
        filteredContacts.forEach(contact => {
            const li = document.createElement("li");
            li.classList.add("contact");
            li.dataset.contact = contact.id;
            li.innerHTML = `
                <img src="${contact.img || '../recursos/perfilvacio.jpg'}" alt="${contact.username || 'Usuario'}">
                <span>${contact.username || 'Usuario Desconocido'}</span>
                ${contact.name ? `<small style="margin-left: 5px; color: #ccc;">(${contact.name})</small>` : ''}
            `;
            contactList.appendChild(li);
        });
    }

    searchBar.addEventListener("input", () => renderContacts(searchBar.value));
    sendBtn.addEventListener("click", sendMessage);
    messageInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter" && !sendBtn.disabled) sendMessage();
    });

    renderContacts(); // Inicial

    // Lógica para el formulario de cotización
    const quoteForm = document.querySelector('#quote-form');
    if (quoteForm) {
        quoteForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!currentContactId) {
                alert('Por favor, selecciona un contacto para enviar la cotización.');
                return;
            }
            const form = e.target;
            const formData = new FormData(form);
            formData.set('id_comprador', currentContactId); // Asegurar que el id_comprador es el correcto

            const cantidad = parseInt(formData.get('product-quantity'));
            const inventarioText = document.querySelector('#product-inventory').value;

            if (inventarioText === '#' || inventarioText === 'Error' || inventarioText === '') {
                alert('Selecciona un producto válido para ver el inventario.'); return;
            }
            const inventario = parseInt(inventarioText);
            if (isNaN(cantidad) || cantidad <= 0) { alert('La cantidad debe ser un número positivo.'); return; }
            if (isNaN(inventario)) { alert('No se pudo determinar el inventario.'); return; }
            if (cantidad > inventario) { alert('La cantidad excede el inventario disponible.'); return; }

            try {
                const response = await fetch('../controladores/CotizacionController.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success && result.producto) {
                    const quoteHTML = `
                        <div class="quote-wrapper quote-right">
                            <div class="quote-msg">
                                <img src="${result.producto.imagen || '../recursos/placeholder.png'}" alt="Producto">
                                <div class="section">
                                    <p><strong>Producto:</strong> ${result.producto.nombre}</p>
                                    <p><strong>Cantidad:</strong> ${result.producto.unidades}</p>
                                    <p><strong>Precio total:</strong> $${result.producto.precio}</p>
                                    <button class="details" data-producto='${JSON.stringify(result.producto).replace(/'/g, "&apos;")}' style="margin-left:0px">Ver detalles</button>
                                </div>
                            </div>
                        </div>`;
                    const noMessagesP = chatContent.querySelector("p");
                    if (noMessagesP && (noMessagesP.textContent.includes("Aún no hay mensajes") || noMessagesP.textContent.includes("Selecciona un contacto"))) {
                        chatContent.innerHTML = quoteHTML;
                    } else {
                        chatContent.innerHTML += quoteHTML;
                    }
                    if (chatBoxContent) chatBoxContent.scrollTop = chatBoxContent.scrollHeight;
                    form.reset();
                    const productImage = document.querySelector('#product-image');
                    const productInventory = document.querySelector('#product-inventory');
                    if(productImage) productImage.src = '../recursos/placeholder.png';
                    if(productInventory) productInventory.value = '#';
                    closeModalWindows();
                } else {
                    alert(result.mensaje || 'Error al enviar la cotización.');
                }
            } catch (error) {
                console.error("Error al enviar cotización:", error);
                alert("Error de conexión al enviar cotización.");
            }
        });
    }

    // Delegado para botón de "ver detalles" de cotización
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('details')) {
            const dataString = e.target.dataset.producto;
            try {
                const data = JSON.parse(dataString.replace(/&apos;/g, "'"));
                document.getElementById('modal-img').src = data.imagen || '../recursos/placeholder.png';
                document.getElementById('id-cotizacion').textContent = data.id_cotizacion || data.id || 'N/A'; // data.id puede ser el id de cotizacion
                document.getElementById('modal-producto').textContent = data.nombre || 'No disponible';
                document.getElementById('modal-unidades').textContent = data.unidades || '0';
                document.getElementById('modal-precio').textContent = data.precio_total || data.precio || '0.00';
                document.getElementById('modal-detalles').textContent = data.detalles || data.descripcion || "[ Sin descripción adicional. ]";

                document.getElementById('cotizacion-modal').classList.remove('hidden');
                document.getElementById('modal-content').style.display = "flex";

                const btnContinuar = document.getElementById('modal-continuar');
                const idCotizacionReal = data.id_cotizacion || data.id; // Usar el ID correcto

                if (data.sender === 'otro' && data.estado !== 'Rechazado' && data.estado !== 'Terminado') {
                    btnContinuar.textContent = 'Continuar con el pago';
                    btnContinuar.disabled = false;
                    btnContinuar.onclick = () => {
                        alert('Aqui se abriría la página de pago para la cotización ID: ' + idCotizacionReal);
                    };
                } else if (data.sender === 'yo' && data.estado !== 'Rechazado' && data.estado !== 'Terminado') {
                    btnContinuar.textContent = 'Cancelar cotización';
                    btnContinuar.disabled = false;
                    btnContinuar.onclick = () => cancelarCotizacion(idCotizacionReal, e.target);
                } else {
                    btnContinuar.textContent = data.estado || 'No disponible';
                    btnContinuar.disabled = true;
                    btnContinuar.onclick = null;
                }
            } catch (error) {
                console.error("Error al parsear datos del producto para detalles:", error, "Data:", dataString);
                alert("No se pudieron cargar los detalles de la cotización.");
            }
        }
    });

    function cancelarCotizacion(idCotizacion, botonDetalleOriginal) {
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
                closeModalWindows();
                botonDetalleOriginal.textContent = 'Expirado';
                botonDetalleOriginal.disabled = true;
                botonDetalleOriginal.classList.add('disabled');
                 // Opcional: Recargar mensajes para reflejar el estado actualizado desde la BD
                if (currentContactId) openChatMessages(currentContactId);
            } else {
                alert('Error al cancelar la cotización: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
             console.error("Error en fetch cancelarCotizacion:", error);
             alert('Error de conexión al cancelar la cotización.');
        });
    }
});