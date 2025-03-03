document.addEventListener("DOMContentLoaded", () => {
    const contactList = document.getElementById("contact-list");
    const chatName = document.getElementById("chat-name");
    const chatImg = document.getElementById("chat-img");
    const chatContent = document.getElementById("chat-content");
    const messageInput = document.getElementById("message-input");
    const sendBtn = document.getElementById("send-btn");
    let currentContact = null;

    function openChat(contact) {
        currentContact = contact;
        chatContent.innerHTML = "<p>Cargando mensajes...</p>";

        setTimeout(() => {
            chatContent.innerHTML = ""; 
            const messages = JSON.parse(localStorage.getItem(contact)) || [];
            messages.forEach(msg => {
                const div = document.createElement("div");
                div.classList.add(msg.sender);
                div.textContent = msg.text;
                chatContent.appendChild(div);
            });
        }, 200);
    }

    function sendMessage() {
        const text = messageInput.value.trim();
        if (!text || !currentContact) return;

        const messages = JSON.parse(localStorage.getItem(currentContact)) || [];
        messages.push({ sender: "user-msg", text });
        localStorage.setItem(currentContact, JSON.stringify(messages));//guarda el msg en memoria local (cambiarlo a bd)

        const div = document.createElement("div");
        div.classList.add("user-msg");
        div.textContent = text;
        chatContent.appendChild(div);
        messageInput.value = "";
    }

    contactList.addEventListener("click", (e) => {
        const contactElement = e.target.closest("li");
        if (!contactElement) return; 

        const contact = contactElement.dataset.contact;
        if (!contact) return;

        chatName.textContent = contactElement.querySelector("span").textContent;
        chatImg.src = contactElement.querySelector("img")?.src || "../recursos/default.jpg";
        openChat(contact);
    });

    sendBtn.addEventListener("click", sendMessage);
    messageInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") sendMessage();
    });
});
