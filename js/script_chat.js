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
            .then(response => response.json())
            .then(messages => {
                chatContent.innerHTML = "";
                messages.forEach(msg => {
                    const div = document.createElement("div");
                    div.classList.add(msg.sender === 'yo' ? 'user-msg' : 'contact-msg');
                    div.textContent = msg.texto;
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
    }

    searchBar.addEventListener("input", () => {
        renderContacts(searchBar.value);
    });

    sendBtn.addEventListener("click", sendMessage);
    messageInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") sendMessage();
    });

    renderContacts(); // Renderiza los contactos al cargar la página
});
