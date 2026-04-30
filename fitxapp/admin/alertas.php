<?php
/**
 * FitxApp - Administrador - Alertas y Cumplimiento
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

$periodo = $_GET['periodo'] ?? 'semana';

// Obtener cumplimiento de empleados
$empleados = [];
$stmt = $pdo->query("SELECT u.id, u.nombre, u.apellidos, d.nombre as departamento
                     FROM usuarios u
                     LEFT JOIN departamentos d ON u.departamento_id = d.id
                     WHERE u.activo = 1 AND u.rol = 'empleado'
                     ORDER BY u.apellidos ASC");

while ($emp = $stmt->fetch()) {
    $cumplimiento = calcularCumplimiento($emp['id'], $periodo);
    $emp = array_merge($emp, $cumplimiento);
    $empleados[] = $emp;
}

// Ordenar por cumplimiento ascendente
usort($empleados, fn($a, $b) => $a['porcentaje'] <=> $b['porcentaje']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas y Cumplimiento - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/sidebar_admin.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1 style="color: #1a237e;">
                <i class="fas fa-exclamation-circle"></i> Alertas y Cumplimiento
            </h1>
            <div style="display: flex; gap: 0.5rem;">
                <a href="?periodo=hoy" class="btn <?php echo $periodo == 'hoy' ? 'btn-primario' : 'btn-secundario'; ?>">Hoy</a>
                <a href="?periodo=semana" class="btn <?php echo $periodo == 'semana' ? 'btn-primario' : 'btn-secundario'; ?>">Esta Semana</a>
                <a href="?periodo=mes" class="btn <?php echo $periodo == 'mes' ? 'btn-primario' : 'btn-secundario'; ?>">Este Mes</a>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card verde">
                <div class="kpi-valor"><?php echo count(array_filter($empleados, fn($e) => $e['porcentaje'] >= 95)); ?></div>
                <div class="kpi-titulo">Cumplimiento Correcto</div>
                <i class="fas fa-check-circle kpi-icono"></i>
            </div>
            <div class="kpi-card amarillo">
                <div class="kpi-valor"><?php echo count(array_filter($empleados, fn($e) => $e['porcentaje'] >= 75 && $e['porcentaje'] < 95)); ?></div>
                <div class="kpi-titulo">Desviación Leve</div>
                <i class="fas fa-exclamation-triangle kpi-icono"></i>
            </div>
            <div class="kpi-card rojo">
                <div class="kpi-valor"><?php echo count(array_filter($empleados, fn($e) => $e['porcentaje'] < 75)); ?></div>
                <div class="kpi-titulo">Incumplimiento Grave</div>
                <i class="fas fa-times-circle kpi-icono"></i>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Estado de Cumplimiento por Empleado</h3>
            </div>
            <div class="card-body">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Departamento</th>
                            <th>Horas Esperadas</th>
                            <th>Horas Reales</th>
                            <th>Diferencia</th>
                            <th>Cumplimiento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $emp): ?>
                        <tr style="background: <?php echo $emp['color'].'10'; ?>;">
                            <td><strong><?php echo escape($emp['nombre'] . ' ' . $emp['apellidos']); ?></strong></td>
                            <td><?php echo escape($emp['departamento'] ?? '-'); ?></td>
                            <td><?php echo $emp['horas_esperadas']; ?>h</td>
                            <td><?php echo $emp['horas_trabajadas']; ?>h</td>
                            <td style="color: <?php echo (isset($emp['diferencia']) && $emp['diferencia'] >= 0) ? '#43a047' : '#c62828'; ?>;">
                                <?php 
                                if (isset($emp['diferencia'])) {
                                    echo $emp['diferencia'] > 0 ? '+' : '';
                                    echo $emp['diferencia'];
                                } else {
                                    echo 0;
                                }
                                ?>h
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 100px; height: 10px; background: #e0e0e0; border-radius: 5px; overflow: hidden;">
                                        <div style="width: <?php echo $emp['porcentaje']; ?>%; height: 100%; background: <?php echo $emp['color']; ?>"></div>
                                    </div>
                                    <span class="badge" style="background: <?php echo $emp['color']; ?>; color: white;"><?php echo $emp['porcentaje']; ?>%</span>
                                </div>
                            </td>
                            <td>
                                <button onclick="enviarRecordatorio('<?php echo escape($emp['nombre']); ?>')" class="btn btn-advertencia" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
                                    <i class="fas fa-envelope"></i> Recordatorio
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
function enviarRecordatorio(nombre) {
    alert('✅ Recordatorio enviado correctamente a ' + nombre);
}
</script>

<script src="../assets/js/main.js"></script>
</body>
</html>