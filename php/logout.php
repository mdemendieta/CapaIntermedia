<?php
if(!isset($_SESSION)) {
    session_start(); // Aseguramos que la sesión esté activa
}


// Destruir todas las variables de sesión
session_unset();

// Destruir la sesión
session_destroy();

// Redirigir al login
header("Location: landing.php");
exit();
?>