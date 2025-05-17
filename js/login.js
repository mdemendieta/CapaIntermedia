document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('#loginForm');
  const usuarioInput = document.querySelector('#usuarioInput');
  const recordarCheckbox = document.querySelector('#recordarUsuario');

  // Si existe usuario guardado, precargarlo
  const usuarioGuardado = localStorage.getItem('usuarioRecordado');
  if (usuarioGuardado) {
    usuarioInput.value = usuarioGuardado;
    recordarCheckbox.checked = true;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(form);

    // Guardar o eliminar del LocalStorage
    if (recordarCheckbox.checked) {
      localStorage.setItem('usuarioRecordado', formData.get('usuario'));
    } else {
      localStorage.removeItem('usuarioRecordado');
    }

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
});
