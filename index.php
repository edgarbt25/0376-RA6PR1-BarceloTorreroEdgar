<?php
/**
 * Página principal / Landing page
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

require_once __DIR__ . '/includes/auth.php';

// Si está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Inicio';
$user = null;

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content animate-fade-in">
            <div class="hero-badge">
                <span>🚀</span>
                <span>Sistema Profesional de Fichaje</span>
            </div>
            <h1 class="hero-title">
                Control de Horas <span>Inteligente</span> para tu Empresa
            </h1>
            <p class="hero-subtitle">
                Vinewood Vice es la solución completa para el seguimiento de horas de trabajo. 
                Gestiona equipos, proyectos y tiempos de forma simple, rápida y fiable.
            </p>
            <div class="hero-actions">
                <a href="register.php" class="btn btn-primary btn-lg">
                    Comenzar Ahora
                    <span>→</span>
                </a>
                <a href="login.php" class="btn btn-secondary btn-lg">
                    Iniciar Sesión
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Características</span>
            <h2 class="section-title">Todo lo que necesitas para gestionar el tiempo</h2>
            <p class="section-description">
                Una plataforma completa diseñada para empresas de 200 a 500 empleados que 
                necesitan control preciso y reportes detallados.
            </p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card animate-slide-up">
                <div class="feature-icon">⏱️</div>
                <h3 class="feature-title">Fichaje en Tiempo Real</h3>
                <p class="feature-description">
                    Registro instantáneo de entradas y salidas con un solo clic. 
                    Accesible desde cualquier dispositivo móvil o computadora.
                </p>
            </div>
            
            <div class="feature-card animate-slide-up">
                <div class="feature-icon">📁</div>
                <h3 class="feature-title">Gestión por Proyectos</h3>
                <p class="feature-description">
                    Asocia cada registro de tiempo a un proyecto específico. 
                    Controla horas presupuestadas vs. horas reales.
                </p>
            </div>
            
            <div class="feature-card animate-slide-up">
                <div class="feature-icon">👥</div>
                <h3 class="feature-title">Roles y Permisos</h3>
                <p class="feature-description">
                    Tres niveles de acceso: Empleado, Jefe de Equipo y Administrador. 
                    Cada usuario ve solo lo que necesita.
                </p>
            </div>
            
            <div class="feature-card animate-slide-up">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">Informes Detallados</h3>
                <p class="feature-description">
                    Genera reportes de horas por empleado, proyecto o período. 
                    Exporta datos para nómina y facturación.
                </p>
            </div>
            
            <div class="feature-card animate-slide-up">
                <div class="feature-icon">🔔</div>
                <h3 class="feature-title">Alertas Automáticas</h3>
                <p class="feature-description">
                    Notificaciones de llegadas tarde, salidas tempranas y 
                    empleados que no completan sus horas requeridas.
                </p>
            </div>
            
            <div class="feature-card animate-slide-up">
                <div class="feature-icon">🔒</div>
                <h3 class="feature-title">Seguridad Máxima</h3>
                <p class="feature-description">
                    Contraseñas cifradas, protección CSRF, prevención de SQL Injection 
                    y sesiones seguras. Tus datos siempre protegidos.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Steps Section -->
<section class="steps-section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Cómo Funciona</span>
            <h2 class="section-title">Comienza en 3 simples pasos</h2>
        </div>
        
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3 class="step-title">Crea tu Cuenta</h3>
                <p class="step-description">
                    Regístrate con tu correo corporativo en menos de 1 minuto.
                </p>
            </div>
            
            <div class="step-card">
                <div class="step-number">2</div>
                <h3 class="step-title">Inicia Sesión</h3>
                <p class="step-description">
                    Accede a tu panel personalizado según tu rol en la empresa.
                </p>
            </div>
            
            <div class="step-card">
                <div class="step-number">3</div>
                <h3 class="step-title">Registra tu Tiempo</h3>
                <p class="step-description">
                    Usa el botón de fichaje para registrar entradas y salidas diarias.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="hero-section" style="background: linear-gradient(135deg, var(--primary-subtle), white); padding: 4rem 0;">
    <div class="container">
        <div class="hero-content">
            <h2 style="font-size: 2rem; margin-bottom: 1rem; color: var(--gray-900);">
                ¿Listo para optimizar el control de horas?
            </h2>
            <p style="font-size: 1.125rem; color: var(--gray-600); margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                Únete a Vinewood Vice y transforma la forma en que gestionas el tiempo de tu equipo.
            </p>
            <div class="hero-actions">
                <a href="register.php" class="btn btn-primary btn-lg">
                    Crear Cuenta Gratis
                </a>
                <a href="login.php" class="btn btn-outline btn-lg">
                    Ya tengo cuenta
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>