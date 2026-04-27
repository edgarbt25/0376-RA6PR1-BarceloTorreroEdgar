<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>Vinewood Vice</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <a href="/" class="logo">
                    <span class="logo-icon">🕐</span>
                    <span class="logo-text">Vinewood Vice</span>
                </a>
                
                <nav class="main-nav">
                    <?php if (isset($user) && $user !== null): ?>
                        <!-- Menú para usuarios autenticados -->
                        <a href="/dashboard.php" class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? ' active' : ''; ?>">
                            <span>📊</span>
                            <span>Mi Panel</span>
                        </a>
                        
                        <?php if ($user['role'] === 'manager'): ?>
                            <a href="/manager/team.php" class="nav-link<?php echo isset($currentPage) && $currentPage === 'team' ? ' active' : ''; ?>">
                                <span>👥</span>
                                <span>Equipo</span>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="/admin/" class="nav-link<?php echo isset($currentPage) && $currentPage === 'admin' ? ' active' : ''; ?>">
                                <span>⚙️</span>
                                <span>Administración</span>
                            </a>
                        <?php endif; ?>
                        
                        <!-- Menú de usuario -->
                        <div class="user-menu">
                            <div class="user-info">
                                <div class="user-avatar" title="<?php echo htmlspecialchars($user['name']); ?>">
                                    <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                                </div>
                                <div class="user-details">
                                    <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                                    <span class="user-role-badge role-<?php echo htmlspecialchars($user['role']); ?>">
                                        <?php 
                                        $roleNames = [
                                            'admin' => 'Administrador',
                                            'manager' => 'Jefe de Equipo',
                                            'employee' => 'Empleado'
                                        ];
                                        echo $roleNames[$user['role']] ?? $user['role'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <a href="/logout.php" class="nav-link nav-link-danger" title="Cerrar sesión">
                                <span>🚪</span>
                                <span>Salir</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Menú para visitantes -->
                        <a href="/login.php" class="nav-link">
                            <span>Iniciar Sesión</span>
                        </a>
                        <a href="/register.php" class="nav-link nav-link-primary">
                            <span>Registrarse</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <?php
            // Mostrar mensajes flash si existen
            $flash = getFlashMessage();
            if ($flash !== null):
            ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> animate-slide-up">
                    <span class="alert-icon">
                        <?php
                        $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
                        echo $icons[$flash['type']] ?? 'ℹ️';
                        ?>
                    </span>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($showBackButton) && $showBackButton): ?>
                <a href="javascript:history.back()" class="back-button">
                    <span>←</span>
                    <span>Volver</span>
                </a>
            <?php endif; ?>