<?php
/**
 * FitxApp - Sistema de Autenticación y Seguridad
 */

// Iniciar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    // Configuración segura de sesión
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 7200); // 2 horas de inactividad
    
    session_start();
    
    // Regenerar ID de sesión cada 30 minutos para prevenir hijacking
    if (!isset($_SESSION['ultima_regeneracion'])) {
        $_SESSION['ultima_regeneracion'] = time();
    } else if (time() - $_SESSION['ultima_regeneracion'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['ultima_regeneracion'] = time();
    }
}

/**
 * Verificar si usuario está autenticado
 */
function estaAutenticado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Verificar rol de usuario
 */
function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function esSupervisor() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'supervisor';
}

function esEmpleado() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'empleado';
}

/**
 * Requerir autenticación para acceder a la página
 */
function requerirAutenticacion() {
    if (!estaAutenticado()) {
        header('Location: ../index.php?error=sesion_expirada');
        exit;
    }
}

/**
 * Requerir rol administrador
 */
function requerirAdmin() {
    requerirAutenticacion();
    if (!esAdmin()) {
        header('Location: ../empleado/dashboard.php?error=acceso_denegado');
        exit;
    }
}

/**
 * Requerir rol empleado
 */
function requerirEmpleado() {
    requerirAutenticacion();
    if (!esEmpleado() && !esSupervisor() && !esAdmin()) {
        header('Location: ../index.php?error=acceso_denegado');
        exit;
    }
}

/**
 * Redirigir según rol después de login
 */
function redirigirPorRol() {
    if (esAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: empleado/dashboard.php');
    }
    exit;
}

/**
 * Generar token CSRF
 */
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtener IP real del cliente
 */
function obtenerIPCliente() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (isset($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            return trim(end($ips));
        }
    }
    return '0.0.0.0';
}

?>