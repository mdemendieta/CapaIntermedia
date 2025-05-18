
  const miniaturas = document.querySelectorAll('.miniaturas img');
  const imagenGrande = document.getElementById('imagenGrande');

  miniaturas.forEach(mini => {
    mini.addEventListener('click', () => {
      imagenGrande.src = mini.src;
    });
  });


