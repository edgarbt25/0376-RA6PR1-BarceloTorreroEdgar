<?php
/**
 * Panel de Informes y Estadísticas para Administradores
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

require_once __DIR__ . '/../includes/auth.php';

// Requerir ser administrador
requireAdmin('../index.php');

$user = getCurrentUser();
$pageTitle = 'Informes y Estadísticas';
$showBackButton = true;

try {
    $pdo = getDBConnection();

    // Obtener proyectos con horas reales trabajadas
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.client,
            p.budgeted_hours,
            SUM(IFNULL(tl.total_minutes, TIMESTAMPDIFF(MINUTE, tl.clock_in, COALESCE(tl.clock_out, NOW())))) as total_minutes
        FROM projects p
        LEFT JOIN time_logs tl ON p.id = tl.project_id
        GROUP BY p.id, p.name, p.client, p.budgeted_hours
        ORDER BY total_minutes DESC
    ");
    $stmt->execute();
    $projects = $stmt->fetchAll();

    $projectsData = [];
    $chartBarLabels = [];
    $chartBarBudgeted = [];
    $chartBarActual = [];

    foreach ($projects as &$proj) {
        $proj['actual_hours'] = round($proj['total_minutes'] / 60, 2);
        $proj['percentage'] = $proj['budgeted_hours'] > 0 ? round(($proj['actual_hours'] / $proj['budgeted_hours']) * 100, 1) : 0;
        $proj['deviation'] = $proj['actual_hours'] - $proj['budgeted_hours'];
        $proj['over_budget'] = $proj['actual_hours'] > $proj['budgeted_hours'];

        $chartBarLabels[] = $proj['name'];
        $chartBarBudgeted[] = $proj['budgeted_hours'];
        $chartBarActual[] = $proj['actual_hours'];

        // Obtener distribución por empleado para este proyecto
        $stmt = $pdo->prepare("
            SELECT 
                u.name,
                SUM(IFNULL(tl.total_minutes, TIMESTAMPDIFF(MINUTE, tl.clock_in, COALESCE(tl.clock_out, NOW())))) as minutes
            FROM time_logs tl
            LEFT JOIN users u ON tl.user_id = u.id
            WHERE tl.project_id = :project_id
            GROUP BY u.id, u.name
            ORDER BY minutes DESC
        ");
        $stmt->execute([':project_id' => $proj['id']]);
        $proj['employees'] = $stmt->fetchAll();

        $employeesData = [];
        foreach ($proj['employees'] as $emp) {
            $employeesData[] = [
                'label' => $emp['name'],
                'value' => round($emp['minutes'] / 60, 2)
            ];
        }
        $proj['employees_json'] = json_encode($employeesData);
    }

} catch (PDOException $e) {
    $projects = [];
}

include '../includes/header.php';
?>

<div class="container-lg">
    <div class="dashboard-header">
        <h1 class="dashboard-welcome">📊 Informes de Proyectos</h1>
        <p class="dashboard-subtitle">Seguimiento de horas presupuestadas vs horas reales trabajadas</p>
    </div>

    <!-- Gráfico general de horas por proyecto -->
    <div class="card mb-xl">
        <div class="card-header">
            <h3 class="card-title">📊 Horas por Proyecto</h3>
        </div>
        <div class="card-body">
            <canvas id="projectsChart" height="100"></canvas>
        </div>
    </div>

    <!-- Listado detallado de proyectos -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📁 Resumen Detallado de Proyectos</h3>
        </div>
        <div class="card-body">

            <div class="projects-list">
                <?php foreach ($projects as $project): ?>
                <div class="project-item <?php echo $project['over_budget'] ? 'project-over-budget' : ''; ?>">
                    
                    <div class="project-header">
                        <div>
                            <h4 class="project-name">
                                <?php if ($project['over_budget']): ?>
                                    <span class="alert-badge">⚠️ SOBRE PRESUPUESTO</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($project['name']); ?>
                            </h4>
                            <span class="project-client">Cliente: <?php echo htmlspecialchars($project['client']); ?></span>
                        </div>
                    </div>

                    <div class="project-stats">
                        <div class="stat-item">
                            <span class="stat-label">Presupuestadas</span>
                            <span class="stat-value"><?php echo $project['budgeted_hours']; ?>h</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Reales</span>
                            <span class="stat-value <?php echo $project['over_budget'] ? 'text-danger' : 'text-success'; ?>"><?php echo $project['actual_hours']; ?>h</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Desviación</span>
                            <span class="stat-value <?php echo $project['deviation'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo $project['deviation'] > 0 ? '+' : ''; ?><?php echo $project['deviation']; ?>h
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Progreso</span>
                            <span class="stat-value"><?php echo $project['percentage']; ?>%</span>
                        </div>
                    </div>

                    <!-- Barra de progreso -->
                    <div class="progress-bar-container">
                        <div class="progress-bar <?php echo $project['over_budget'] ? 'progress-bar-danger' : ''; ?>" style="width: <?php echo min($project['percentage'], 100); ?>%;"></div>
                    </div>

                    <?php if (!empty($project['employees'])): ?>
                    <div class="project-employees mt-lg">
                        <h5>Distribución por empleado:</h5>
                        <div class="employees-grid">
                            <?php foreach ($project['employees'] as $emp): ?>
                            <div class="employee-badge">
                                <span class="employee-name"><?php echo htmlspecialchars($emp['name']); ?></span>
                                <span class="employee-hours"><?php echo round($emp['minutes'] / 60, 1); ?>h</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
<script>
// Gráfico de barras comparativo
const ctx = document.getElementById('projectsChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chartBarLabels); ?>,
        datasets: [
            {
                label: 'Horas Presupuestadas',
                data: <?php echo json_encode($chartBarBudgeted); ?>,
                backgroundColor: 'rgba(99, 102, 241, 0.7)',
                borderColor: 'rgb(99, 102, 241)',
                borderWidth: 1
            },
            {
                label: 'Horas Reales',
                data: <?php echo json_encode($chartBarActual); ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                borderColor: 'rgb(16, 185, 129)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Comparativa Horas Presupuestadas vs Reales'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Horas'
                }
            }
        }
    }
});
</script>

<style>
.projects-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.project-item {
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-xl);
    padding: 1.5rem;
    background: white;
    transition: all 0.3s ease;
}

.project-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.project-over-budget {
    border-left: 4px solid var(--danger);
    background: var(--danger-bg);
}

.project-name {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
}

.alert-badge {
    display: inline-block;
    background: var(--danger);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 800;
    margin-right: 0.75rem;
    animation: pulse-danger 2s infinite;
}

.project-client {
    color: var(--gray-500);
    font-size: 0.875rem;
}

.project-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
}

.stat-item {
    text-align: center;
}

.stat-label {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--gray-900);
}

.progress-bar-container {
    width: 100%;
    height: 8px;
    background: var(--gray-200);
    border-radius: var(--radius-full);
    overflow: hidden;
    margin: 1rem 0;
}

.progress-bar {
    height: 100%;
    background: var(--success);
    transition: width 0.5s ease;
    border-radius: var(--radius-full);
}

.progress-bar-danger {
    background: var(--danger);
}

.employees-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.employee-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--gray-100);
    border-radius: var(--radius-full);
    font-size: 0.875rem;
}

.employee-hours {
    font-weight: 700;
    color: var(--primary);
}

.text-danger { color: var(--danger) !important; }
.text-success { color: var(--success) !important; }
</style>

<?php include '../includes/footer.php'; ?>