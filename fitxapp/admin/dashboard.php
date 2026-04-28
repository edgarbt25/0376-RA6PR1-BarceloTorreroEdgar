<?php
/**
 * FitxApp - Panel Administrador - Dashboard Principal
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

// Estadísticas generales
$stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1");
$totalEmpleados = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM fichajes WHERE hora_salida IS NULL");
$empleadosFichados = $stmt->fetchColumn();

$mesActual = date('m');
$anioActual = date('Y');

$stmt = $pdo->prepare("SELECT SUM(horas_trabajadas) FROM fichajes 
                       WHERE MONTH(hora_entrada) = ? AND YEAR(hora_entrada) = ?");
$stmt->execute([$mesActual, $anioActual]);
$horasMes = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM solicitudes_correccion WHERE estado = 'pendiente'");
$alertasPendientes = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administración - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include '../includes/sidebar_admin.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="margin-bottom: 2rem; color: #1a237e;">
            <i class="fas fa-chart-line"></i> Panel de Administración
        </h1>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card azul">
                <div class="kpi-valor"><?php echo $totalEmpleados; ?></div>
                <div class="kpi-titulo">Empleados Activos</div>
                <i class="fas fa-users kpi-icono"></i>
            </div>
            
            <div class="kpi-card verde">
                <div class="kpi-valor"><?php echo $empleadosFichados; ?></div>
                <div class="kpi-titulo">Trabajando Ahora</div>
                <i class="fas fa-user-clock kpi-icono"></i>
            </div>
            
            <div class="kpi-card morado">
                <div class="kpi-valor"><?php echo number_format($horasMes, 1); ?>h</div>
                <div class="kpi-titulo">Horas Totales Este Mes</div>
                <i class="fas fa-clock kpi-icono"></i>
            </div>
            
            <div class="kpi-card <?php echo $alertasPendientes > 0 ? 'rojo' : 'azul'; ?>">
                <div class="kpi-valor"><?php echo $alertasPendientes; ?></div>
                <div class="kpi-titulo">Solicitudes Pendientes</div>
                <i class="fas fa-exclamation-triangle kpi-icono"></i>
            </div>
        </div>

        <!-- Empleados trabajando actualmente -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-clock"></i> Empleados trabajando en tiempo real</h3>
                <span class="badge azul">Auto-refresh 30s</span>
            </div>
            <div class="card-body">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Departamento</th>
                            <th>Hora Entrada</th>
                            <th>Tiempo Transcurrido</th>
                            <th>Proyecto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT u.id, u.nombre, u.apellidos, d.nombre as departamento, f.hora_entrada, p.nombre as proyecto
                                             FROM fichajes f 
                                             JOIN usuarios u ON f.usuario_id = u.id 
                                             LEFT JOIN departamentos d ON u.departamento_id = d.id
                                             LEFT JOIN proyectos p ON f.proyecto_id = p.id
                                             WHERE f.hora_salida IS NULL
                                             ORDER BY f.hora_entrada ASC");
                        
                        while ($empleado = $stmt->fetch()):
                            $tiempo = time() - strtotime($empleado['hora_entrada']);
                            $horas = floor($tiempo / 3600);
                            $minutos = floor(($tiempo % 3600) / 60);
                        ?>
                        <tr>
                            <td><strong><?php echo escape($empleado['nombre'] . ' ' . $empleado['apellidos']); ?></strong></td>
                            <td><?php echo escape($empleado['departamento']); ?></td>
                            <td><?php echo date('H:i', strtotime($empleado['hora_entrada'])); ?></td>
                            <td><?php echo $horas . 'h ' . $minutos . 'm'; ?></td>
                            <td><?php echo escape($empleado['proyecto']); ?></td>
                            <td><span class="badge verde">Trabajando</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Horas últimos 7 días</h3>
                </div>
                <div class="card-body">
                    <div class="grafico-contenedor">
                        <canvas id="graficoHorasDias"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribución por actividad</h3>
                </div>
                <div class="card-body">
                    <div class="grafico-contenedor">
                        <canvas id="graficoActividades"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Gráfico horas por día
new Chart(document.getElementById('graficoHorasDias'), {
    type: 'bar',
    data: {
        labels: ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
        datasets: [{
            label: 'Horas totales',
            data: [72.5, 78.2, 76.8, 74.1, 71.3, 0, 0],
            backgroundColor: '#1976d2',
            borderRadius: 8
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

// Auto refresh página cada 30 segundos
setTimeout(() => location.reload(), 30000);
</script>

<script src="../assets/js/main.js"></script>
</body>
</html>