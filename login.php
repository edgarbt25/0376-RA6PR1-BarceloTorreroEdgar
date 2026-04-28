<?php
/**
 * Página de inicio de sesión
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

require_once __DIR__ . '/includes/auth.php';

// Si ya está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido. Por favor, intenta de nuevo.';
    } else {
        // Obtener y sanitizar datos
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Intentar iniciar sesión
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            
            // Redirigir al dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

$pageTitle = 'Iniciar Sesión';
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
                <h1>Bienvenido de nuevo</h1>
                <p>Inicia sesión para acceder a tu panel</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">❌</span>
                    <span><?php echo htmlspecialchars(implode('<br>', $errors)); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label required">Correo Electrónico</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="tu@empresa.com"
                        required
                        autocomplete="email"
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label required">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="auth-footer">
                <p>¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a></p>
            </div>
            
            <div class="demo-credentials">
                <strong>🔑 Credenciales de prueba:</strong>
                <p><strong>Administrador:</strong> admin@vinewoodvice.com / admin123</p>
                <p><strong>Jefe:</strong> manager@vinewoodvice.com / manager123</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>