<?php
/**
 * FitxApp - Panel Empleado - Dashboard Principal
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirEmpleado();

$usuario_id = $_SESSION['usuario_id'];
$estado = obtenerEstadoEmpleado($usuario_id);
$fichajeAbierto = verificarFichajeAbierto($usuario_id);

// Estadísticas
$cumplimientoHoy = calcularCumplimiento($usuario_id);
$horasMes = calcularHorasExtra($usuario_id, date('m'), date('Y'));

// Contar notificaciones no leídas
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0");
$stmt->execute([$usuario_id]);
$notificacionesPendientes = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FitxApp</title>
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
            <i class="fas fa-home"></i> Bienvenido, <?php echo escape($_SESSION['nombre']); ?>
        </h1>

        <!-- Tarjeta de estado actual -->
        <div class="card" style="margin-bottom: 2rem; text-align: center; padding: 2rem;">
            <div class="estado-actual" style="font-size: 4rem; margin-bottom: 1rem;">
                <i class="fas <?php echo $estado['icono']; ?>" style="color: <?php echo $estado['color']; ?>"></i>
            </div>
                <h2 style="font-size: 2rem; margin-bottom: 0.5rem; color: <?php echo $estado['color']; ?>">
                    <?php echo strtoupper($estado['texto']); ?>
                </h2>
            
            <?php if ($fichajeAbierto): ?>
            <div id="contador-tiempo" style="font-size: 3rem; font-family: monospace; font-weight: 700; margin: 1rem 0;"></div>
            
            <form method="POST" action="fichar.php" style="display: inline-block;">
                <input type="hidden" name="accion" value="salida">
                <button type="submit" class="btn btn-peligro btn-grande" onclick="return confirm('¿Seguro que quieres finalizar la jornada?')">
                    <i class="fas fa-stop"></i> FINALIZAR JORNADA
                </button>
            </form>
            <?php else: ?>
            <a href="fichar.php" class="btn btn-exito btn-grande">
                <i class="fas fa-play"></i> INICIAR JORNADA
            </a>
            <?php endif; ?>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card azul">
                <div class="kpi-valor"><?php echo $cumplimientoHoy['horas_trabajadas']; ?>h</div>
                <div class="kpi-titulo">Horas Hoy</div>
                <i class="fas fa-clock kpi-icono"></i>
            </div>
            
            <div class="kpi-card verde">
                <div class="kpi-valor"><?php echo $horasMes['trabajadas']; ?>h</div>
                <div class="kpi-titulo">Horas Este Mes</div>
                <i class="fas fa-calendar-alt kpi-icono"></i>
            </div>
            
            <div class="kpi-card <?php echo $horasMes['saldo'] >=0 ? 'verde' : 'rojo'; ?>">
                <div class="kpi-valor"><?php echo $horasMes['saldo'] >=0 ? '+' : ''; ?><?php echo $horasMes['saldo']; ?>h</div>
                <div class="kpi-titulo">Balance Horas</div>
                <i class="fas fa-balance-scale kpi-icono"></i>
            </div>
            
            <div class="kpi-card naranja">
                <div class="kpi-valor"><?php echo $notificacionesPendientes; ?></div>
                <div class="kpi-titulo">Notificaciones</div>
                <i class="fas fa-bell kpi-icono"></i>
            </div>
        </div>

        <!-- Gráfico semanal -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar"></i> Horas esta semana</h3>
            </div>
            <div class="card-body">
                <div class="grafico-contenedor">
                    <canvas id="graficoSemanal"></canvas>
                </div>
            </div>
        </div>

        <!-- Últimas actividades -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Últimas actividades</h3>
                <a href="mi_historial.php" class="btn btn-primario">Ver todo</a>
            </div>
            <div class="card-body">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Horas</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM fichajes WHERE usuario_id = ? ORDER BY hora_entrada DESC LIMIT 5");
                        $stmt->execute([$usuario_id]);
                        while ($fichaje = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($fichaje['hora_entrada'])); ?></td>
                            <td><?php echo date('H:i', strtotime($fichaje['hora_entrada'])); ?></td>
                            <td><?php echo $fichaje['hora_salida'] ? date('H:i', strtotime($fichaje['hora_salida'])) : '-'; ?></td>
                            <td><?php echo $fichaje['horas_trabajadas']; ?>h</td>
                            <td>
                                <?php if ($fichaje['validado']): ?>
                                <span class="badge verde">Validado</span>
                                <?php else: ?>
                                <span class="badge amarillo">Pendiente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
// Reloj contador si está fichado
<?php if ($fichajeAbierto): ?>
function actualizarContador() {
    const inicio = new Date('<?php echo $fichajeAbierto['hora_entrada']; ?>');
    const ahora = new Date();
    const diff = ahora - inicio;
    
    const horas = Math.floor(diff / 3600000);
    const minutos = Math.floor((diff % 3600000) / 60000);
    const segundos = Math.floor((diff % 60000) / 1000);
    
    document.getElementById('contador-tiempo').textContent = 
        String(horas).padStart(2, '0') + ':' + 
        String(minutos).padStart(2, '0') + ':' + 
        String(segundos).padStart(2, '0');
}

setInterval(actualizarContador, 1000);
actualizarContador();
<?php endif; ?>

// Gráfico semanal
const ctx = document.getElementById('graficoSemanal').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
        datasets: [{
            label: 'Horas trabajadas',
            data: [8.2, 7.9, 8.5, 6.8, 8.1, 0, 0],
            backgroundColor: '#1976d2',
            borderRadius: 6
        }, {
            label: 'Horas esperadas',
            data: [8, 8, 8, 8, 8, 0, 0],
            backgroundColor: '#e0e0e0',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 10
            }
        }
    }
});
</script>

<script src="../assets/js/main.js"></script>
</body>
</html>