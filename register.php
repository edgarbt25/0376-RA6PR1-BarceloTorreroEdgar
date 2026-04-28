<?php
/**
 * Página de registro de usuarios
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

require_once __DIR__ . '/includes/auth.php';

// Si ya está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$name = '';
$email = '';
$role = 'employee';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido. Por favor, intenta de nuevo.';
    } else {
        // Obtener y sanitizar datos
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $role = $_POST['role'] ?? 'employee';
        
        // Validaciones
        if (empty($name)) {
            $errors[] = 'El nombre es obligatorio.';
        } elseif (strlen($name) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres.';
        }
        
        if (empty($email)) {
            $errors[] = 'El email es obligatorio.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es válido.';
        }
        
        if (empty($password)) {
            $errors[] = 'La contraseña es obligatoria.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
        }
        
        if ($password !== $passwordConfirm) {
            $errors[] = 'Las contraseñas no coinciden.';
        }
        
        // Validar rol (solo employee por defecto para registros públicos)
        if (!in_array($role, ['employee'])) {
            $role = 'employee';
        }
        
        // Si no hay errores, registrar usuario
        if (empty($errors)) {
            $result = registerUser($name, $email, $password, $role);
            
            if ($result['success']) {
                setFlashMessage('success', '¡Cuenta creada con éxito! Ahora puedes iniciar sesión.');
                header('Location: login.php');
                exit;
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$pageTitle = 'Crear Cuenta';
$showBackButton = true;

include 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-card animate-slide-up">
            <div class="auth-header">
                <a href="/" class="auth-logo">
                    <div class="auth-logo-icon">🕐</div>
                    <span class="auth-logo-text">Vinewood Vice</span>
                </a>
                <h1>Crea tu cuenta</h1>
                <p>Únete al sistema de seguimiento de horas</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">❌</span>
                    <div>
                        <strong>Errores encontrados:</strong>
                        <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="register.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="name" class="form-label required">Nombre Completo</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control" 
                        placeholder="Ej: Juan Pérez García"
                        value="<?php echo htmlspecialchars($name); ?>"
                        required
                        autocomplete="name"
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label required">Correo Electrónico</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="juan@empresa.com"
                        value="<?php echo htmlspecialchars($email); ?>"
                        required
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label required">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Mínimo 6 caracteres"
                        required
                        autocomplete="new-password"
                        minlength="6"
                    >
                    <small class="form-hint">La contraseña debe tener al menos 6 caracteres.</small>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm" class="form-label required">Confirmar Contraseña</label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        class="form-control" 
                        placeholder="Repite tu contraseña"
                        required
                        autocomplete="new-password"
                    >
                </div>
                
                <input type="hidden" name="role" value="employee">
                
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    Crear Cuenta
                </button>
            </form>
            
            <div class="auth-footer">
                <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>