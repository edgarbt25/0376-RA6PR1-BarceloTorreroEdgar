<?php
/**
 * Historial de registros de tiempo del empleado
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

require_once __DIR__ . '/includes/auth.php';

// Requerir autenticación
requireAuth('login.php');

$user = getCurrentUser();
$pageTitle = 'Mi Historial';
$showBackButton = true;

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

try {
    $pdo = getDBConnection();

    // Condición de filtrado
    $whereConditions = ["user_id = :user_id"];
    $params = [':user_id' => $user['id']];

    if ($filter === 'week') {
        $whereConditions[] = "YEARWEEK(clock_in) = YEARWEEK(NOW())";
    } elseif ($filter === 'month') {
        $whereConditions[] = "MONTH(clock_in) = MONTH(NOW()) AND YEAR(clock_in) = YEAR(NOW())";
    }

    $whereSql = implode(' AND ', $whereConditions);

    // Contar total registros
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM time_logs WHERE {$whereSql}");
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $perPage);

    // Obtener registros paginados
    $offset = ($page - 1) * $perPage;
    
    $stmt = $pdo->prepare("
        SELECT 
            tl.id,
            tl.clock_in,
            tl.clock_out,
            tl.notes,
            tl.total_minutes,
            p.name as project_name,
            p.id as project_id
        FROM time_logs tl
        LEFT JOIN projects p ON tl.project_id = p.id
        WHERE {$whereSql}
        ORDER BY tl.clock_in DESC
        LIMIT :offset, :perPage
    ");
    
    $stmt->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $logs = $stmt->fetchAll();

    // Calcular totales
    $stmt = $pdo->prepare("
        SELECT 
            SUM(IFNULL(tl.total_minutes, TIMESTAMPDIFF(MINUTE, tl.clock_in, NOW()))) as total_minutes
        FROM time_logs tl
        WHERE {$whereSql}
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $totals = $stmt->fetch();

} catch (PDOException $e) {
    $logs = [];
    $totalRecords = 0;
    $totalPages = 1;
    $totals = ['total_minutes' => 0];
}

function formatMinutesToHours($minutes) {
    if ($minutes === null || $minutes == 0) return '0h 0m';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h ' . $mins . 'm';
}

include 'includes/header.php';
?>

<div class="container-md">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <h3 class="card-title">📋 Mi Historial de Registros</h3>
            <div class="filter-buttons">
                <a href="?filter=all" class="btn btn-sm <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">Todos</a>
                <a href="?filter=week" class="btn btn-sm <?php echo $filter === 'week' ? 'btn-primary' : 'btn-secondary'; ?>">Esta Semana</a>
                <a href="?filter=month" class="btn btn-sm <?php echo $filter === 'month' ? 'btn-primary' : 'btn-secondary'; ?>">Este Mes</a>
            </div>
        </div>
        <div class="card-body">
            <div class="stats-grid" style="margin-bottom: 2rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                <div class="stat-card" style="padding: 1rem;">
                    <div class="stat-content">
                        <span class="stat-value" style="font-size: 1.5rem;"><?php echo $totalRecords; ?></span>
                        <span class="stat-label">Registros</span>
                    </div>
                </div>
                <div class="stat-card" style="padding: 1rem;">
                    <div class="stat-content">
                        <span class="stat-value" style="font-size: 1.5rem;"><?php echo formatMinutesToHours($totals['total_minutes']); ?></span>
                        <span class="stat-label">Horas Totales</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($logs)): ?>
            <div class="table-container">
                <table class="data-table clickable-rows">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Proyecto</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Duración</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr onclick="window.location.href='record_detail.php?id=<?php echo $log['id']; ?>'">
                            <td>
                                <strong><?php echo date('d/m/Y', strtotime($log['clock_in'])); ?></strong>
                            </td>
                            <td>
                                <?php if ($log['project_name']): ?>
                                    <span class="badge badge-primary"><?php echo htmlspecialchars($log['project_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Sin proyecto</span>
                                <?php endif; ?>
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
                                <strong>
                                    <?php 
                                    if ($log['clock_out']) {
                                        echo formatMinutesToHours($log['total_minutes']);
                                    } else {
                                        $mins = time() - strtotime($log['clock_in']);
                                        echo formatMinutesToHours($mins / 60);
                                    }
                                    ?>
                                </strong>
                            </td>
                            <td>
                                <?php if ($log['notes']): ?>
                                    <span class="text-muted"><?php echo htmlspecialchars(substr($log['notes'], 0, 20)) . '...'; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination mt-xl">
                <?php if ($page > 1): ?>
                    <a href="?filter=<?php echo urlencode($filter); ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary btn-sm">← Anterior</a>
                <?php endif; ?>
                
                <span class="pagination-info">Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?filter=<?php echo urlencode($filter); ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary btn-sm">Siguiente →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="text-center" style="padding: 3rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                <h3 style="margin-bottom: 0.5rem; color: var(--gray-700);">Sin registros aún</h3>
                <p style="color: var(--gray-500);">Aún no tienes registros de tiempo en este periodo.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>