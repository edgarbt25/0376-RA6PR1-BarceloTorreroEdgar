<?php
/**
 * Panel de Administración
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

require_once __DIR__ . '/../includes/auth.php';

// Requerir rol de administrador
requireAdmin('../login.php');

// Obtener datos del usuario actual
$user = getCurrentUser();

// Configuración de página
$pageTitle = 'Administración';
$currentPage = 'admin';

// Obtener estadísticas generales
try {
    $pdo = getDBConnection();
    
    // Total de usuarios por rol
    $stmt = $pdo->query("
        SELECT role, COUNT(*) as count 
        FROM users 
        GROUP BY role
    ");
    $usersByRole = $stmt->fetchAll();
    $roleCounts = [];
    foreach ($usersByRole as $row) {
        $roleCounts[$row['role']] = $row['count'];
    }
    
    // Total de proyectos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM projects");
    $totalProjects = $stmt->fetch()['total'];
    
    // Horas totales registradas
    $stmt = $pdo->query("
        SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, COALESCE(clock_out, NOW()))) as total
        FROM time_logs
    ");
    $totalHours = $stmt->fetch()['total'] ?? 0;
    
    // Usuarios activos hoy
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT user_id) as active 
        FROM time_logs 
        WHERE DATE(clock_in) = CURDATE()
    ");
    $activeToday = $stmt->fetch()['active'];
    
    // Últimos usuarios registrados
    $stmt = $pdo->query("
        SELECT id, name, email, role, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentUsers = $stmt->fetchAll();
    
    // Proyectos con más horas
    $stmt = $pdo->query("
        SELECT 
            p.name,
            p.client,
            p.budgeted_hours,
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, tl.clock_in, COALESCE(tl.clock_out, NOW()))), 0) as logged_minutes
        FROM projects p
        LEFT JOIN time_logs tl ON p.id = tl.project_id
        GROUP BY p.id
        ORDER BY logged_minutes DESC
        LIMIT 5
    ");
    $topProjects = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $roleCounts = ['admin' => 0, 'manager' => 0, 'employee' => 0];
    $totalProjects = 0;
    $totalHours = 0;
    $activeToday = 0;
    $recentUsers = [];
    $topProjects = [];
}

// Función para formatear minutos a horas
function formatMinutesToHours($minutes) {
    if ($minutes === null || $minutes == 0) return '0h 0m';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h ' . $mins . 'm';
}

include '../includes/header.php';
?>

<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-label">Administración</div>
        <nav class="sidebar-nav">
            <a href="/admin/" class="sidebar-link active">
                <span class="sidebar-icon">📊</span>
                <span>Panel Principal</span>
            </a>
            <a href="/admin/users.php" class="sidebar-link">
                <span class="sidebar-icon">👥</span>
                <span>Usuarios</span>
            </a>
            <a href="/admin/projects.php" class="sidebar-link">
                <span class="sidebar-icon">📁</span>
                <span>Proyectos</span>
            </a>
            <a href="/admin/logs.php" class="sidebar-link">
                <span class="sidebar-icon">📋</span>
                <span>Registros</span>
            </a>
        </nav>
        
        <div class="sidebar-divider"></div>
        
        <div class="sidebar-label">Acciones</div>
        <nav class="sidebar-nav">
            <a href="/dashboard.php" class="sidebar-link">
                <span class="sidebar-icon">←</span>
                <span>Volver al Panel</span>
            </a>
            <a href="/logout.php" class="sidebar-link">
                <span class="sidebar-icon">🚪</span>
                <span>Cerrar Sesión</span>
            </a>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <div class="main-area">
        <div class="dashboard-header">
            <h1 class="dashboard-welcome">⚙️ Panel de Administración</h1>
            <p class="dashboard-subtitle">Gestiona usuarios, proyectos y registros del sistema</p>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue">👥</div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo array_sum($roleCounts); ?></span>
                    <span class="stat-label">Total Usuarios</span>
                    <div class="stat-change">
                        <span class="badge badge-primary">Admin: <?php echo $roleCounts['admin'] ?? 0; ?></span>
                        <span class="badge badge-warning">Jefes: <?php echo $roleCounts['manager'] ?? 0; ?></span>
                        <span class="badge badge-gray">Empleados: <?php echo $roleCounts['employee'] ?? 0; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-green">✅</div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo $activeToday; ?></span>
                    <span class="stat-label">Activos Hoy</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-orange">📁</div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo $totalProjects; ?></span>
                    <span class="stat-label">Proyectos</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-purple">⏱️</div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo formatMinutesToHours($totalHours); ?></span>
                    <span class="stat-label">Horas Totales</span>
                </div>
            </div>
        </div>
        
        <!-- Recent Users & Top Projects -->
        <div class="stats-grid">
            <!-- Recent Users -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🆕 Últimos Usuarios</h3>
                    <a href="/admin/users.php" class="btn btn-sm btn-outline">Ver todos</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentUsers)): ?>
                        <p class="text-muted text-center">No hay usuarios registrados</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach ($recentUsers as $u): ?>
                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; background: var(--gray-50); border-radius: var(--radius-md);">
                                <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                    <?php echo strtoupper(substr($u['name'], 0, 2)); ?>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($u['name']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--gray-500);">
                                        <?php echo htmlspecialchars($u['email']); ?>
                                    </div>
                                </div>
                                <span class="badge badge-<?php echo $u['role'] === 'admin' ? 'primary' : ($u['role'] === 'manager' ? 'warning' : 'gray'); ?>">
                                    <?php echo $u['role']; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Top Projects -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🔥 Proyectos Más Activos</h3>
                    <a href="/admin/projects.php" class="btn btn-sm btn-outline">Ver todos</a>
                </div>
                <div class="card-body">
                    <?php if (empty($topProjects)): ?>
                        <p class="text-muted text-center">No hay proyectos registrados</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach ($topProjects as $p): ?>
                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; background: var(--gray-50); border-radius: var(--radius-md);">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600; font-size: 0.875rem;">
                                        <?php echo htmlspecialchars($p['name']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--gray-500);">
                                        <?php echo htmlspecialchars($p['client']); ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 600; font-size: 0.875rem; color: var(--primary);">
                                        <?php echo formatMinutesToHours($p['logged_minutes']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--gray-500);">
                                        de <?php echo formatMinutesToHours($p['budgeted_hours'] * 60); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Coming Soon Features -->
        <div class="coming-soon mt-xl">
            <div class="coming-soon-header">
                <span class="coming-soon-icon">🔜</span>
                <h3 class="coming-soon-title">Próximamente (ITERACIONES 3-5)</h3>
            </div>
            <div class="feature-preview">
                <div class="preview-item">
                    <span class="preview-icon">📊</span>
                    <span class="preview-text">Informes detallados con gráficos Chart.js</span>
                </div>
                <div class="preview-item">
                    <span class="preview-icon">🔔</span>
                    <span class="preview-text">Sistema de alertas de incumplimiento</span>
                </div>
                <div class="preview-item">
                    <span class="preview-icon">📈</span>
                    <span class="preview-text">Comparativa presupuestado vs real</span>
                </div>
                <div class="preview-item">
                    <span class="preview-icon">👥</span>
                    <span class="preview-text">CRUD completo de empleados y proyectos</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>