<?php
/**
 * FitxApp - Empleado - Mis Reportes
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirLogin();
$usuario_id = $_SESSION['usuario_id'];

// Horas por día últimos 30 días
$datosHoras = [];
$labelsHoras = [];
for ($i=29; $i>=0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT SUM(horas_trabajadas) FROM fichajes WHERE usuario_id = ? AND DATE(hora_entrada) = ?");
    $stmt->execute([$usuario_id, $fecha]);
    $horas = $stmt->fetchColumn() ?: 0;
    $datosHoras[] = round($horas, 1);
    $labelsHoras[] = date('d/m', strtotime($fecha));
}

// Horas por proyecto
$stmt = $pdo->prepare("SELECT p.nombre, SUM(f.horas_trabajadas) as horas
                       FROM fichajes f
                       LEFT JOIN proyectos p ON f.proyecto_id = p.id
                       WHERE f.usuario_id = ? AND MONTH(f.hora_entrada) = MONTH(NOW())
                       GROUP BY p.id");
$stmt->execute([$usuario_id]);
$proyectosHoras = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Reportes - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include '../includes/sidebar_empleado.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="margin-bottom: 2rem; color: #1a237e;">
            <i class="fas fa-chart-line"></i> Mis Reportes
        </h1>

        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Evolución Horas Últimos 30 días</h3>
                </div>
                <div class="card-body">
                    <div class="grafico-contenedor">
                        <canvas id="graficoEvolucion"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Horas por Proyecto</h3>
                </div>
                <div class="card-body">
                    <div class="grafico-contenedor">
                        <canvas id="graficoProyectos"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 2rem;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3><i class="fas fa-table"></i> Resumen Mensual</h3>
                <a href="../exportar.php?tipo=mis_horas" class="btn btn-primario" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                    <i class="fas fa-download"></i> Exportar CSV
                </a>
            </div>
            <div class="card-body">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Periodo</th>
                            <th>Horas Trabajadas</th>
                            <th>Horas Esperadas</th>
                            <th>Diferencia</th>
                            <th>Horas Extra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT SUM(horas_trabajadas) as total FROM fichajes WHERE usuario_id = ? AND MONTH(hora_entrada) = MONTH(NOW())");
                        $stmt->execute([$usuario_id]);
                        $totalMes = $stmt->fetchColumn();
                        $esperadas = 22 * 8;
                        $diferencia = $totalMes - $esperadas;
                        ?>
                        <tr>
                            <td><strong><?php echo strftime('%B %Y'); ?></strong></td>
                            <td><?php echo number_format($totalMes, 1); ?>h</td>
                            <td><?php echo $esperadas; ?>h</td>
                            <td style="color: <?php echo $diferencia >=0 ? '#43a047' : '#c62828'; ?>">
                                <?php echo $diferencia > 0 ? '+' : ''; ?><?php echo number_format($diferencia, 1); ?>h
                            </td>
                            <td><?php echo max(0, $diferencia); ?>h</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
// Gráfico evolución horas
new Chart(document.getElementById('graficoEvolucion'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labelsHoras); ?>,
        datasets: [{
            label: 'Horas trabajadas',
            data: <?php echo json_encode($datosHoras); ?>,
            borderColor: '#1976d2',
            backgroundColor: 'rgba(25, 118, 210, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 12 } }
    }
});

// Gráfico por proyectos
new Chart(document.getElementById('graficoProyectos'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_keys($proyectosHoras)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($proyectosHoras)); ?>,
            backgroundColor: ['#1976d2', '#43a047', '#f57c00', '#7b1fa2', '#ef5350', '#0288d1']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

<script src="../assets/js/main.js"></script>
</body>
</html>