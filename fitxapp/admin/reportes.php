<?php
/**
 * FitxApp - Administrador - Reportes Avanzados
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');

// Obtener resumen general
$stmt = $pdo->prepare("SELECT SUM(horas_trabajadas) as total_horas, COUNT(DISTINCT usuario_id) as empleados_activos
                       FROM fichajes 
                       WHERE MONTH(hora_entrada) = ? AND YEAR(hora_entrada) = ? AND hora_salida IS NOT NULL");
$stmt->execute([$mes, $anio]);
$resumen = $stmt->fetch();

// Horas por departamento
$stmt = $pdo->prepare("SELECT d.nombre, SUM(f.horas_trabajadas) as horas
                       FROM fichajes f
                       JOIN usuarios u ON f.usuario_id = u.id
                       LEFT JOIN departamentos d ON u.departamento_id = d.id
                       WHERE MONTH(f.hora_entrada) = ? AND YEAR(f.hora_entrada) = ?
                       GROUP BY d.id
                       ORDER BY horas DESC");
$stmt->execute([$mes, $anio]);
$horasDepartamento = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Avanzados - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include '../includes/sidebar_admin.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1 style="color: #1a237e;">
                <i class="fas fa-chart-bar"></i> Reportes Avanzados
            </h1>
            <form method="GET" style="display: flex; gap: 0.5rem;">
                <select name="mes" class="form-control" style="padding: 0.5rem; border-radius: 8px; border: 1px solid #e0e0e0;">
                    <?php for($i=1;$i<=12;$i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $mes == $i ? 'selected' : ''; ?>><?php echo strftime('%B', mktime(0,0,0,$i,1)); ?></option>
                    <?php endfor; ?>
                </select>
                <select name="anio" class="form-control" style="padding: 0.5rem; border-radius: 8px; border: 1px solid #e0e0e0;">
                    <option value="<?php echo date('Y'); ?>" <?php echo $anio == date('Y') ? 'selected' : ''; ?>><?php echo date('Y'); ?></option>
                    <option value="<?php echo date('Y')-1; ?>" <?php echo $anio == date('Y')-1 ? 'selected' : ''; ?>><?php echo date('Y')-1; ?></option>
                </select>
                <button type="submit" class="btn btn-primario">Ver</button>
            </form>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card azul">
                <div class="kpi-valor"><?php echo number_format($resumen['total_horas'], 1); ?>h</div>
                <div class="kpi-titulo">Total Horas Registradas</div>
                <i class="fas fa-clock kpi-icono"></i>
            </div>
            <div class="kpi-card verde">
                <div class="kpi-valor"><?php echo $resumen['empleados_activos']; ?></div>
                <div class="kpi-titulo">Empleados Activos</div>
                <i class="fas fa-users kpi-icono"></i>
            </div>
        </div>

        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-building"></i> Horas por Departamento</h3>
                </div>
                <div class="card-body">
                    <div class="grafico-contenedor">
                        <canvas id="graficoDepartamentos"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribución Actividades</h3>
                </div>
                <div class="card-body">
                    <div class="grafico-contenedor">
                        <canvas id="graficoActividades"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-download"></i> Exportar Datos</h3>
            </div>
            <div class="card-body" style="display: flex; gap: 1rem;">
                <a href="exportar.php?tipo=empleados&mes=<?php echo $mes; ?>&anio=<?php echo $anio; ?>" class="btn btn-primario">
                    <i class="fas fa-file-csv"></i> Exportar Horas Empleados CSV
                </a>
                <a href="exportar.php?tipo=proyectos&mes=<?php echo $mes; ?>&anio=<?php echo $anio; ?>" class="btn btn-primario">
                    <i class="fas fa-file-csv"></i> Exportar Horas Proyectos CSV
                </a>
            </div>
        </div>

    </div>
</div>

<script>
// Gráfico horas por departamento
new Chart(document.getElementById('graficoDepartamentos'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_keys($horasDepartamento)); ?>,
        datasets: [{
            label: 'Horas trabajadas',
            data: <?php echo json_encode(array_values($horasDepartamento)); ?>,
            backgroundColor: '#1976d2',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Gráfico distribución actividades
new Chart(document.getElementById('graficoActividades'), {
    type: 'doughnut',
    data: {
        labels: ['Fabricación', 'Montaje', 'Administración', 'Logística', 'Reunión', 'Formación'],
        datasets: [{
            data: [38, 22, 15, 12, 8, 5],
            backgroundColor: ['#2e7d32', '#1565c0', '#6d4c41', '#ef6c00', '#7b1fa2', '#0277bd']
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