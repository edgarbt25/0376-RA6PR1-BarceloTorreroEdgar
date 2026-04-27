<?php
/**
 * Funciones de autenticación y autorización
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

// Configurar sesiones para evitar errores de permisos
if (session_status() === PHP_SESSION_NONE) {
    // Crear carpeta de sesiones local
    $sessionPath = __DIR__ . '/../sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }
    
    // Configurar PHP para usar nuestra carpeta de sesiones
    session_save_path($sessionPath);
    
    // Configuraciones seguras de sesión
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Registra un nuevo usuario
 * @param string $name Nombre del usuario
 * @param string $email Email del usuario
 * @param string $password Contraseña del usuario
 * @param string $role Rol del usuario (admin, manager o employee)
 * @return array ['success' => bool, 'message' => string]
 */
function registerUser(string $name, string $email, string $password, string $role = 'employee'): array {
    // Validar datos
    $name = trim(htmlspecialchars(strip_tags($name)));
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    
    // Validaciones
    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Todos los campos son obligatorios.'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'El email no es válido.'];
    }
    
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'];
    }
    
    if (!in_array($role, ['admin', 'manager', 'employee'])) {
        $role = 'employee';
    }
    
    // Cifrar contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $pdo = getDBConnection();
        
        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'El email ya está registrado.'];
        }
        
        // Insertar nuevo usuario
        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role) 
             VALUES (:name, :email, :password_hash, :role)"
        );
        
        $result = $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':role' => $role
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Usuario registrado correctamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar el usuario.'];
        }
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
    }
}

/**
 * Inicia sesión de un usuario
 * @param string $email Email del usuario
 * @param string $password Contraseña del usuario
 * @return array ['success' => bool, 'message' => string]
 */
function loginUser(string $email, string $password): array {
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email y contraseña son obligatorios.'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'El email no es válido.'];
    }
    
    try {
        $pdo = getDBConnection();
        
        // Buscar usuario por email
        $stmt = $pdo->prepare(
            "SELECT id, name, email, password_hash, role FROM users WHERE email = :email"
        );
        $stmt->execute([':email' => $email]);
        
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Credenciales inválidas.'];
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Credenciales inválidas.'];
        }
        
        // Regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);
        
        // Guardar datos en sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        return ['success' => true, 'message' => 'Bienvenido, ' . htmlspecialchars($user['name']) . '!'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
    }
}

/**
 * Cierra la sesión del usuario actual
 * @return void
 */
function logoutUser(): void {
    // Limpiar todas las variables de sesión
    $_SESSION = [];
    
    // Destruir la cookie de sesión
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destruir la sesión
    session_destroy();
}

/**
 * Verifica si un usuario está autenticado
 * @return bool
 */
function isAuthenticated(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifica si el usuario actual es administrador
 * @return bool
 */
function isAdmin(): bool {
    return isAuthenticated() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Verifica si el usuario actual es manager (jefe)
 * @return bool
 */
function isManager(): bool {
    return isAuthenticated() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager';
}

/**
 * Verifica si el usuario es admin o manager
 * @return bool
 */
function isSupervisor(): bool {
    return isAdmin() || isManager();
}

/**
 * Obtiene los datos del usuario actual
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}

/**
 * Redirecciona a usuarios no autenticados a la página de login
 * @param string $redirectUrl URL de redirección
 * @return void
 */
function requireAuth(string $redirectUrl = '/login.php'): void {
    if (!isAuthenticated()) {
        header('Location: ' . $redirectUrl);
        exit;
    }
}

/**
 * Redirecciona a usuarios no administradores a la página principal
 * @param string $redirectUrl URL de redirección
 * @return void
 */
function requireAdmin(string $redirectUrl = '/index.php'): void {
    requireAuth($redirectUrl);
    
    if (!isAdmin()) {
        header('Location: ' . $redirectUrl);
        exit;
    }
}

/**
 * Redirecciona a usuarios que no son manager o admin
 * @param string $redirectUrl URL de redirección
 * @return void
 */
function requireSupervisor(string $redirectUrl = '/index.php'): void {
    requireAuth($redirectUrl);
    
    if (!isSupervisor()) {
        header('Location: ' . $redirectUrl);
        exit;
    }
}

/**
 * Protege contra ataques CSRF generando un token
 * @return string
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica el token CSRF
 * @param string $token Token a verificar
 * @return bool
 */
function validateCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Establece un mensaje flash (mensaje de una sola vez)
 * @param string $type Tipo de mensaje (success, error, warning, info)
 * @param string $message Mensaje
 * @return void
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obtiene y elimina el mensaje flash
 * @return array|null
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}