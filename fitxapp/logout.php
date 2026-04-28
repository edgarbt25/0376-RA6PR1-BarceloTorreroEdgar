<?php
/**
 * FitxApp - Cierre de Sesión
 */

require_once 'includes/auth.php';
require_once 'includes/funciones.php';

if (estaAutenticado()) {
    registrarLog($_SESSION['usuario_id'], 'logout');
}

// Destruir sesión completamente
session_unset();
session_destroy();

// Eliminar cookie recordarme si existe
if (isset($_COOKIE['fitxapp_email'])) {
    setcookie('fitxapp_email', '', time() - 3600, "/");
}

header('Location: index.php?mensaje=sesion_cerrada');
exit;
?>