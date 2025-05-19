document.addEventListener('DOMContentLoaded', () => {
  const thumbnailGallery = document.getElementById('thumbnail-gallery');
  const mainPreviewContainer = document.getElementById('main-preview-container');

  if (thumbnailGallery && mainPreviewContainer) {
    const thumbnails = thumbnailGallery.querySelectorAll('.thumbnail-item');

    thumbnails.forEach(thumb => {
      thumb.addEventListener('click', () => {
        const type = thumb.dataset.type;
        const src = thumb.dataset.src;
        const altText = thumb.alt || `Vista previa del producto`; // Para imágenes

        mainPreviewContainer.innerHTML = ''; // Limpiar el contenedor

        if (type === 'image') {
          const img = document.createElement('img');
          img.src = src;
          img.alt = altText;
          img.className = 'max-w-full max-h-full object-contain rounded';
          mainPreviewContainer.appendChild(img);
        } else if (type === 'video') {
          const video = document.createElement('video');
          video.src = src;
          video.className = 'max-w-full max-h-full object-contain rounded';
          video.controls = true;
          video.autoplay = true;
          // video.muted = true; // Puedes decidir si quieres que el video al hacer clic esté silenciado o no
          mainPreviewContainer.appendChild(video);
        }
      });
    });
  } else {
    if (!thumbnailGallery) console.error("Elemento con id 'thumbnail-gallery' no encontrado.");
    if (!mainPreviewContainer) console.error("Elemento con id 'main-preview-container' no encontrado.");
  }

  const estrellasContainer = document.getElementById('estrellasValoracion');
  const puntuacionInput = document.getElementById('puntuacion_seleccionada');
  const btnEnviarValoracion = document.getElementById('btnEnviarValoracion');
  const valoracionError = document.getElementById('valoracionError');
  const formValoracion = document.getElementById('formValoracion');

  if (estrellasContainer) {
    const estrellas = estrellasContainer.querySelectorAll('.star');
    let currentRating = 0;

    estrellas.forEach(star => {
      star.addEventListener('mouseover', () => {
        resetEstrellas();
        const hoverValue = parseInt(star.dataset.value);
        for (let i = 0; i < hoverValue; i++) {
          estrellas[i].textContent = '★';
          estrellas[i].classList.add('text-yellow-500');
          estrellas[i].classList.remove('text-gray-400');
        }
      });

      star.addEventListener('mouseout', () => {
        resetEstrellas();
        if (currentRating > 0) {
          iluminarEstrellasHasta(currentRating);
        }
      });

      star.addEventListener('click', () => {
        currentRating = parseInt(star.dataset.value);
        puntuacionInput.value = currentRating;
        iluminarEstrellasHasta(currentRating);
        if (btnEnviarValoracion) {
          btnEnviarValoracion.disabled = false;
        }
        if (valoracionError) {
          valoracionError.textContent = '';
        }
      });
    });

    function iluminarEstrellasHasta(value) {
      estrellas.forEach((s, index) => {
        if (index < value) {
          s.textContent = '★';
          s.classList.add('text-yellow-500');
          s.classList.remove('text-gray-400');
        } else {
          s.textContent = '☆';
          s.classList.remove('text-yellow-500');
          s.classList.add('text-gray-400');
        }
      });
    }

    function resetEstrellas() {
      estrellas.forEach(s => {
        if (parseInt(s.dataset.value) > currentRating) {
          s.textContent = '☆';
          s.classList.remove('text-yellow-500');
          s.classList.add('text-gray-400');
        }
      });
    }

    if (formValoracion) {
      formValoracion.addEventListener('submit', function (event) {
        if (puntuacionInput.value === "0" || parseInt(puntuacionInput.value) < 1) {
          event.preventDefault(); // Detiene el envío del formulario
          if (valoracionError) {
            valoracionError.textContent = 'Por favor, selecciona una valoración.';
          }
        }
      });
    }
  }
});