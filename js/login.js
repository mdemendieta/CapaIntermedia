const form = document.querySelector('#loginForm');
form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const formData = new FormData(form);
  const response = await fetch('../controladores/LoginController.php', {
    method: 'POST',
    body: formData
  });

  const result = await response.json();

  if (result.success) {
    window.location.href = result.redirect;
  } else {
    alert(result.error);
  }
});
