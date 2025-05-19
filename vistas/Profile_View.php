<?php
// vistas/profile_view.php
// Las variables $userData y $seccionActiva son pasadas por el ProfileController->index()
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles_profile.css"> <title>Perfil de <?php echo htmlspecialchars($userData['nombre_usuario'] ?? 'Usuario'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script> </head>
<header>
    <?php include 'navbar.php'; // Asumiendo que navbar.php está en el mismo directorio 'vistas' ?>
</header>
<body class="bg-orange-100">
    <div id="main-content" class="flex justify-center transition-all duration-300 p-6 min-h-screen bg-orange-100">
        <div class="w-full justify-center">
            <div class="card-header w-full bg-gradient-to-r from-orange-100 from-1% via-gray-50 via-20% to-orange-100 to-90% mb-5">
                <div class="section-left relative flex items-center justify-center">
                    <img src="<?php echo htmlspecialchars($userData['avatar']); ?>" alt="Foto de perfil" class="profile-img">
                    <a href="../php/editarperfil.php"> <img src="../recursos/iconos/editar.png" class="absolute w-8 h-8 bg-red-500 rounded-full top-4 right-16 transform cursor-pointer z-30">
                    </a>
                </div>
                <div class="section-middle">
                    <h2 class="justify-left"><?php echo htmlspecialchars($userData['nombre_completo'] ?? 'Usuario'); ?></h2>
                    <p class="text-v1">Se unió el: <?php echo htmlspecialchars($userData['fecha_union'] ?? 'N/A'); ?></p>
                    <span class="role seller"><?php echo htmlspecialchars($userData['tipo_usuario'] ?? 'Cliente'); ?></span>
                    <span class="text-v1">Público</span>
                    <button onclick="window.location.href='../php/chat.php'" class="btn-v2 pl-4 pr-4 ml-20">Mensaje</button> </div>
            </div>

            <div class="flex space-x-4 items-start mb-4">
                <?php
                function claseBotonActivoVista($nombreSeccion, $seccionActivaActual) {
                    return ($nombreSeccion === $seccionActivaActual)
                        ? "bg-white text-orange-500 px-6 py-4 rounded-full transition"
                        : "bg-blue-950 text-white px-6 py-4 rounded-full border-4 border-orange-500 hover:bg-orange-500 transition";
                }
                ?>
                <div class="flex gap-4 mb-2" id="profile-section-buttons">
                    <button class="<?= claseBotonActivoVista('listas', $seccionActiva) ?>" data-seccion="listas">Listas</button>
                    <button class="<?= claseBotonActivoVista('historial', $seccionActiva) ?>" data-seccion="historial">Historial de pedidos</button>
                    <button class="<?= claseBotonActivoVista('productospubli', $seccionActiva) ?>" data-seccion="productospubli">Productos Publicados</button>
                    <button class="<?= claseBotonActivoVista('productospend', $seccionActiva) ?>" data-seccion="productospend">Solicitudes de Publicaciones</button>
                    <button class="<?= claseBotonActivoVista('reportes', $seccionActiva) ?>" data-seccion="reportes">Ventas</button>
                </div>
            </div>

            <div id="contenedor" class="flex justify-center min-h-[200px] bg-gray-50 p-4 rounded-md shadow">
                </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const botonesSeccion = document.querySelectorAll('#profile-section-buttons button');
            const contenedor = document.getElementById('contenedor');
            const seccionInicial = '<?php echo $seccionActiva; // Viene del controlador ?>';

            // Función global para cargar contenido, ahora accesible por las vistas parciales
            window.cargarContenidoGlobal = function(seccion, botonClicado, additionalQueryString = '') {
                // El spinner/loading podría ir aquí
                if(contenedor) contenedor.innerHTML = '<p class="text-center p-10">Cargando...</p>';
                
                // Desde vistas/profile_view.php, la ruta a controladores es ../controladores/
                const baseUrl = `../controladores/ProfileController.php?action=loadSection&s=${seccion}`;
                const finalUrl = baseUrl + (additionalQueryString ? additionalQueryString : '');

                fetch(finalUrl)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Error HTTP ${response.status} al cargar la sección.`);
                        }
                        return response.text();
                    })
                    .then(html => {
                        if(contenedor) contenedor.innerHTML = html;
                        // Re-adjuntar listener para el form de crear lista si la sección es 'listas'
                        if (seccion === 'listas') {
                            const formCrearLista = document.getElementById('crearListaForm');
                            if (formCrearLista) {
                                formCrearLista.addEventListener('submit', handleCrearListaSubmit);
                            }
                        }
                        // Aquí podrías inicializar otros scripts específicos de la sección si es necesario
                        // Por ejemplo, si una vista parcial cargada tiene un carrusel Swiper, lo inicializas aquí.
                        // O, como hicimos, la propia vista parcial tiene su <script> que se ejecuta.
                    })
                    .catch(error => {
                        console.error('Error al cargar la sección:', error);
                        if(contenedor) contenedor.innerHTML = `<p class="text-red-500 p-10 text-center">Error al cargar contenido: ${error.message}</p>`;
                    });

                if (botonClicado) {
                    // Actualizar URL del navegador si la carga fue por clic en botón principal de sección
                    // Desde vistas/profile_view.php, la ruta a php/profile.php es ../php/profile.php
                    history.pushState(null, '', `../php/profile.php?seccion=${seccion}`);
                    
                    // Actualizar clases de botones principales
                    botonesSeccion.forEach(btn => {
                        btn.className = (btn.dataset.seccion === seccion)
                            ? "bg-white text-orange-500 px-6 py-4 rounded-full transition"
                            : "bg-blue-950 text-white px-6 py-4 rounded-full border-4 border-orange-500 hover:bg-orange-500 transition";
                    });
                }
            }

            // Listener para los botones principales de sección
            botonesSeccion.forEach(boton => {
                boton.addEventListener('click', function() {
                    window.cargarContenidoGlobal(this.dataset.seccion, this);
                });
            });

            // Cargar la sección activa (o por defecto) al inicio
            if (seccionInicial) {
                const botonActivoInicial = Array.from(botonesSeccion).find(b => b.dataset.seccion === seccionInicial);
                window.cargarContenidoGlobal(seccionInicial, botonActivoInicial);
            }

            // Manejador para el formulario de crear lista (submit)
            function handleCrearListaSubmit(event) {
                event.preventDefault();
                const formData = new FormData(this);
                // La URL de fetch para POST también va al controlador
                fetch(`../controladores/ProfileController.php?action=loadSection&s=listas`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.lista) {
                        alert('Lista creada exitosamente!');
                        // Recargar la sección de listas para ver la nueva lista
                        // (El botón activo ya debería ser 'listas' o el que corresponda)
                        const botonListas = document.querySelector('#profile-section-buttons button[data-seccion="listas"]');
                        window.cargarContenidoGlobal('listas', botonListas); 
                        
                        if(document.getElementById('crearListaModal')) {
                            document.getElementById('crearListaModal').classList.add('hidden');
                        }
                        this.reset(); // Limpiar el formulario
                    } else {
                        alert('Error al crear la lista: ' + (data.mensaje || 'Error desconocido.'));
                    }
                })
                .catch(error => {
                    console.error('Error en fetch al crear lista:', error);
                    alert('Error de comunicación al crear la lista.');
                });
            }
        });
    </script>
</body>
</html>