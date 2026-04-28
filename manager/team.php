<?php
/**
 * Panel de Jefe de Equipo - Vista de Equipo
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

require_once __DIR__ . '/../includes/auth.php';

// Requerir rol de manager o admin
requireSupervisor('../login.php');

// Obtener datos del usuario actual
$user = getCurrentUser();

// Configuración de página
$pageTitle = 'Equipo';
$currentPage = 'team';

// Obtener datos del equipo
try {
    $pdo = getDBConnection();
    
    // Todos los empleados
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.created_at,
            COALESCE(
                (SELECT TIMESTAMPDIFF(MINUTE, clock_in, COALESCE(clock_out, NOW())) 
                 FROM time_logs 
                 WHERE user_id = u.id AND DATE(clock_in) = CURDATE()
                 ORDER BY clock_in DESC LIMIT 1),
                0
            ) as today_minutes,
            COALESCE(
                (SELECT clock_in 
                 FROM time_logs 
                 WHERE user_id = u.id AND DATE(clock_in) = CURDATE()
                 ORDER BY clock_in DESC LIMIT 1),
                NULL
            ) as clock_in_today,
            COALESCE(
                (SELECT clock_out 
                 FROM time_logs 
                 WHERE user_id = u.id AND DATE(clock_in) = CURDATE()
                 ORDER BY clock_in DESC LIMIT 1),
                NULL
            ) as clock_out_today
        FROM users u
        WHERE u.role = 'employee'
        ORDER BY u.name ASC
    ");
    $employees = $stmt->fetchAll();
    
    // Estadísticas del equipo
    $totalEmployees = count($employees);
    $activeNow = 0;
    $totalTodayMinutes = 0;
    
    foreach ($employees as $emp) {
        if ($emp['clock_in_today'] && !$emp['clock_out_today']) {
            $activeNow++;
        }
        $totalTodayMinutes += $emp['today_minutes'];
    }
    
    // Empleados con menos horas hoy (menos de 8h)
    $underperforming = 0;
    foreach ($employees as $emp) {
        if ($emp['today_minutes'] > 0 && $emp['today_minutes'] < 480) { // 8 horas = 480 minutos
            $underperforming++;
        }
    }
    
} catch (PDOException $e) {
    $employees = [];
    $totalEmployees = 0;
    $activeNow = 0;
    $totalTodayMinutes = 0;
    $underperforming = 0;
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
        <div class="sidebar-label">Jefe de Equipo</div>
        <nav class="sidebar-nav">
            <a href="/manager/team.php" class="sidebar-link active">
                <span class="sidebar-icon">👥</span>
                <span>Ver Equipo</span>
            </a>
            <a href="/manager/reports.php" class="sidebar-link">
                <span class="sidebar-icon">📊</span>
                <span>Informes</span>
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
            <h1 class="dashboard-welcome">👥 Vista de Equipo</h1>
            <p class="dashboard-subtitle">Supervisa la actividad de tu equipo de trabajo</p>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue">👥</div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo $totalEmployees; ?></span>
                    <span class="stat-label">Total Empleados</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-green">✅</div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo $activeNow; ?></span>
                    <span class="stat-label">Trabajando Ahora</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-orange">⏱️</div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo formatMinutesToHours($totalTodayMinutes); ?></span>
                    <span class="stat-label">Horas Equipo Hoy</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-icon-purple">⚠️</div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo $underperforming; ?></span>
                    <span class="stat-label">Por Debajo de 8h</span>
                </div>
            </div>
        </div>
        
        <!-- Employee List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📋 Lista de Empleados</h3>
                <span class="badge badge-gray"><?php echo $totalEmployees; ?> empleados</span>
            </div>
            <div class="card-body">
                <?php if (empty($employees)): ?>
                    <div class="text-center" style="padding: 3rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                        <h3 style="margin-bottom: 0.5rem; color: var(--gray-700);">No hay empleados</h3>
                        <p style="color: var(--gray-500);">Los empleados registrados aparecerán aquí.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Email</th>
                                    <th>Estado</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Horas Hoy</th>
                                    <th>Progreso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $emp): 
                                    $progress = min(100, ($emp['today_minutes'] / 480) * 100); // 8h = 480min
                                    $statusClass = '';
                                    $statusText = '';
                                    
                                    if ($emp['clock_in_today'] && !$emp['clock_out_today']) {
                                        $statusClass = 'badge-success';
                                        $statusText = 'Trabajando';
                                    } elseif ($emp['clock_out_today']) {
                                        $statusClass = 'badge-gray';
                                        $statusText = 'Finalizado';
                                    } else {
                                        $statusClass = 'badge-gray';
                                        $statusText = 'Sin registro';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                                <?php echo strtoupper(substr($emp['name'], 0, 2)); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($emp['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.8125rem; color: var(--gray-500);">
                                            <?php echo htmlspecialchars($emp['email']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($emp['clock_in_today']): ?>
                                            <?php echo date('H:i', strtotime($emp['clock_in_today'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($emp['clock_out_today']): ?>
                                            <?php echo date('H:i', strtotime($emp['clock_out_today'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo formatMinutesToHours($emp['today_minutes']); ?></strong>
                                    </td>
                                    <td style="width: 120px;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div style="flex: 1; height: 6px; background: var(--gray-200); border-radius: 3px; overflow: hidden;">
                                                <div style="width: <?php echo $progress; ?>%; height: 100%; background: <?php echo $progress >= 100 ? 'var(--success)' : 'var(--primary)'; ?>; border-radius: 3px;"></div>
                                            </div>
                                            <span style="font-size: 0.6875rem; color: var(--gray-500); min-width: 30px; text-align: right;">
                                                <?php echo round($progress); ?>%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Coming Soon -->
        <div class="coming-soon mt-xl">
            <div class="coming-soon-header">
                <span class="coming-soon-icon">🔜</span>
                <h3 class="coming-soon-title">Próximamente (ITERACIONES 3-5)</h3>
            </div>
            <div class="feature-preview">
                <div class="preview-item">
                    <span class="preview-icon">📊</span>
                    <span class="preview-text">Informes detallados por empleado y proyecto</span>
                </div>
                <div class="preview-item">
                    <span class="preview-icon">🔔</span>
                    <span class="preview-text">Alertas automáticas de incumplimiento</span>
                </div>
                <div class="preview-item">
                    <span class="preview-icon">📈</span>
                    <span class="preview-text">Exportar datos a Excel/PDF</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>