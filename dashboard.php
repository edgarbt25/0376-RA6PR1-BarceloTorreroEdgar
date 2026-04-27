<?php
/**
 * Panel de usuario (Dashboard) - Vistas por rol
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

require_once __DIR__ . '/includes/auth.php';

// Requerir autenticación
requireAuth('login.php');

// Obtener datos del usuario actual
$user = getCurrentUser();

// Configuración de página
$pageTitle = 'Mi Panel';
$showBackButton = false;

// Función para formatear minutos a horas
function formatMinutesToHours($minutes) {
    if ($minutes === null || $minutes == 0) return '0h 0m';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h ' . $mins . 'm';
}

// Obtener estadísticas del usuario
try {
    $pdo = getDBConnection();
    
    // Horas trabajadas hoy
    $stmt = $pdo->prepare("
        SELECT 
            clock_in, 
            clock_out,
            TIMESTAMPDIFF(MINUTE, clock_in, COALESCE(clock_out, NOW())) as minutes_worked
        FROM time_logs 
        WHERE user_id = :user_id 
            AND DATE(clock_in) = CURDATE()
        ORDER BY clock_in DESC
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $todayLog = $stmt->fetch();
    
    // Horas trabajadas esta semana
    $stmt = $pdo->prepare("
        SELECT 
            SUM(TIMESTAMPDIFF(MINUTE, clock_in, COALESCE(clock_out, NOW()))) as total_minutes
        FROM time_logs 
        WHERE user_id = :user_id 
            AND YEARWEEK(clock_in) = YEARWEEK(NOW())
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $weekStats = $stmt->fetch();
    
    // Horas trabajadas este mes
    $stmt = $pdo->prepare("
        SELECT 
            SUM(TIMESTAMPDIFF(MINUTE, clock_in, COALESCE(clock_out, NOW()))) as total_minutes
        FROM time_logs 
        WHERE user_id = :user_id 
            AND MONTH(clock_in) = MONTH(NOW())
            AND YEAR(clock_in) = YEAR(NOW())
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $monthStats = $stmt->fetch();
    
    // Últimos registros
    $stmt = $pdo->prepare("
        SELECT 
            tl.id,
            tl.clock_in,
            tl.clock_out,
            tl.notes,
            p.name as project_name
        FROM time_logs tl
        LEFT JOIN projects p ON tl.project_id = p.id
        WHERE tl.user_id = :user_id
        ORDER BY tl.clock_in DESC
        LIMIT 10
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $recentLogs = $stmt->fetchAll();
    
    // Estadísticas para manager/admin (todos los empleados)
    $totalEmployees = 0;
    $activeNow = 0;
    $todayTotalHours = 0;
    
    if ($user['role'] === 'manager' || $user['role'] === 'admin') {
        // Total de empleados
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'employee'");
        $totalEmployees = $stmt->fetch()['total'];
        
        // Activos ahora (con clock_in hoy sin clock_out)
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT tl.user_id) as active 
            FROM time_logs tl
            WHERE tl.clock_out IS NULL 
                AND DATE(tl.clock_in) = CURDATE()
        ");
        $activeNow = $stmt->fetch()['active'];
        
        // Horas totales hoy
        $stmt = $pdo->query("
            SELECT SUM(TIMESTAMPDIFF(MINUTE, tl.clock_in, COALESCE(tl.clock_out, NOW()))) as total
            FROM time_logs tl
            WHERE DATE(tl.clock_in) = CURDATE()
        ");
        $todayTotalHours = $stmt->fetch()['total'] ?? 0;
    }
    
} catch (PDOException $e) {
    $todayLog = null;
    $weekStats = ['total_minutes' => 0];
    $monthStats = ['total_minutes' => 0];
    $recentLogs = [];
}

include 'includes/header.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-welcome">
            <?php
            $hour = date('H');
            if ($hour < 12) echo 'Buenos días';
            elseif ($hour < 19) echo 'Buenas tardes';
            else echo 'Buenas noches';
            ?>, <?php echo htmlspecialchars($user['name']); ?>
        </h1>
        <p class="dashboard-subtitle">
            <?php 
            $roleNames = [
                'admin' => 'Panel de Administrador',
                'manager' => 'Panel de Jefe de Equipo',
                'employee' => 'Panel de Empleado'
            ];
            echo $roleNames[$user['role']] ?? 'Panel de Usuario';
            ?>
        </p>
    </div>
    
    <!-- Estadísticas principales -->
    <div class="stats-grid">
        <!-- Tarjeta: Horas Hoy -->
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue">📅</div>
            <div class="stat-content">
                <span class="stat-value">
                    <?php echo $todayLog ? formatMinutesToHours($todayLog['minutes_worked']) : '0h 0m'; ?>
                </span>
                <span class="stat-label">Horas Hoy</span>
                <?php if ($todayLog && !$todayLog['clock_out']): ?>
                    <div class="stat-change positive">
                        <span>●</span> En progreso
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tarjeta: Horas Semana -->
        <div class="stat-card">
            <div class="stat-icon stat-icon-green">📆</div>
            <div class="stat-content">
                <span class="stat-value">
                    <?php echo formatMinutesToHours($weekStats['total_minutes'] ?? 0); ?>
                </span>
                <span class="stat-label">Esta Semana</span>
            </div>
        </div>
        
        <!-- Tarjeta: Horas Mes -->
        <div class="stat-card">
            <div class="stat-icon stat-icon-orange">📊</div>
            <div class="stat-content">
                <span class="stat-value">
                    <?php echo formatMinutesToHours($monthStats['total_minutes'] ?? 0); ?>
                </span>
                <span class="stat-label">Este Mes</span>
            </div>
        </div>
        
        <!-- Tarjeta: Próximamente -->
        <div class="stat-card">
            <div class="stat-icon stat-icon-purple">🚀</div>
            <div class="stat-content">
                <span class="stat-value" style="font-size: 1rem;">Próximamente</span>
                <span class="stat-label">Más funciones</span>
            </div>
        </div>
    </div>
    
    <!-- Estado actual y próximas funcionalidades -->
    <div class="current-status">
        <div class="status-header">
            <h2 class="status-title">Estado Actual</h2>
            <span class="status-indicator <?php echo ($todayLog && !$todayLog['clock_out']) ? 'status-active' : 'status-inactive'; ?>">
                <span class="status-dot"></span>
                <?php if ($todayLog && !$todayLog['clock_out']): ?>
                    Trabajando desde las <?php echo date('H:i', strtotime($todayLog['clock_in'])); ?>
                <?php elseif ($todayLog): ?>
                    Jornada completada
                <?php else: ?>
                    Sin registro hoy
                <?php endif; ?>
            </span>
        </div>
    </div>
    
    <!-- Sección específica por rol -->
    <?php if ($user['role'] === 'admin'): ?>
        <!-- Panel de Administrador -->
        <div class="card mb-xl">
            <div class="card-header">
                <h3 class="card-title">⚙️ Administración del Sistema</h3>
                <a href="/admin/" class="btn btn-primary btn-sm">Ir al Panel Admin</a>
            </div>
            <div class="card-body">
                <div class="stats-grid" style="margin-bottom: 0;">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-blue">👥</div>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo $totalEmployees; ?></span>
                            <span class="stat-label">Empleados</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-green">✅</div>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo $activeNow; ?></span>
                            <span class="stat-label">Activos Ahora</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-orange">⏱️</div>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo formatMinutesToHours($todayTotalHours); ?></span>
                            <span class="stat-label">Horas Hoy (Total)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($user['role'] === 'manager'): ?>
        <!-- Panel de Jefe -->
        <div class="card mb-xl">
            <div class="card-header">
                <h3 class="card-title">👥 Vista de Equipo</h3>
                <a href="/manager/team.php" class="btn btn-primary btn-sm">Ver Equipo Completo</a>
            </div>
            <div class="card-body">
                <div class="stats-grid" style="margin-bottom: 0;">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-blue">👥</div>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo $totalEmployees; ?></span>
                            <span class="stat-label">Empleados</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-green">✅</div>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo $activeNow; ?></span>
                            <span class="stat-label">Activos Ahora</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-orange">⏱️</div>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo formatMinutesToHours($todayTotalHours); ?></span>
                            <span class="stat-label">Horas Hoy (Equipo)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Panel de Empleado -->
        <div class="coming-soon">
            <div class="coming-soon-header">
                <span class="coming-soon-icon">🔜</span>
                <h3 class="coming-soon-title">Próximamente (ITERACIÓN 2)</h3>
            </div>
            <div class="feature-preview">
                <div class="preview-item">
                    <span class="preview-icon">⏰</span>
                    <span class="preview-text">Botón de Entrada/Salida (Clock In/Out) - Registra tu jornada con un clic</span>
                </div>
                <div class="preview-item">
                    <span class="preview-icon">📁</span>
                    <span class="preview-text">Asociar tiempo a proyectos - Vincula tus horas a proyectos específicos</span>
                </div>
                <div class="preview-item">
                    <span class="preview-icon">📝</span>
                    <span class="preview-text">Notas de actividad - Añade comentarios a tus registros</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Últimos registros -->
    <?php if (!empty($recentLogs)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📋 Últimos Registros</h3>
            <span class="badge badge-gray"><?php echo count($recentLogs); ?> registros</span>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Duración</th>
                            <th>Proyecto</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td>
                                <strong><?php echo date('d/m/Y', strtotime($log['clock_in'])); ?></strong>
                            </td>
                            <td><?php echo date('H:i', strtotime($log['clock_in'])); ?></td>
                            <td>
                                <?php if ($log['clock_out']): ?>
                                    <?php echo date('H:i', strtotime($log['clock_out'])); ?>
                                <?php else: ?>
                                    <span class="badge badge-success">En progreso</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($log['clock_out']) {
                                    $mins = strtotime($log['clock_out']) - strtotime($log['clock_in']);
                                    echo formatMinutesToHours($mins / 60);
                                } else {
                                    $mins = time() - strtotime($log['clock_in']);
                                    echo formatMinutesToHours($mins / 60);
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($log['project_name']): ?>
                                    <span class="badge badge-primary"><?php echo htmlspecialchars($log['project_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Sin proyecto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['notes']): ?>
                                    <?php echo htmlspecialchars(substr($log['notes'], 0, 30)) . (strlen($log['notes']) > 30 ? '...' : ''); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 3rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
            <h3 style="margin-bottom: 0.5rem; color: var(--gray-700);">Sin registros aún</h3>
            <p style="color: var(--gray-500);">Tus registros de tiempo aparecerán aquí una vez que comiences a fichar.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>