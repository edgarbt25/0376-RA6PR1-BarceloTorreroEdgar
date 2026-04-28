<?php
/**
 * Página de cierre de sesión
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

require_once __DIR__ . '/includes/auth.php';

// Cerrar sesión
logoutUser();

// Redirigir a la página de login
header('Location: login.php');
exit;