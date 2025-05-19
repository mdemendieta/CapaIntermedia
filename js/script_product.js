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
});