<?php
/**
 * FitxApp - Empleado - Mi Historial de Fichajes
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirLogin();
$usuario_id = $_SESSION['usuario_id'];

$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');

// Obtener fichajes del empleado
$stmt = $pdo->prepare("SELECT f.*, p.nombre as proyecto
                       FROM fichajes f
                       LEFT JOIN proyectos p ON f.proyecto_id = p.id
                       WHERE f.usuario_id = ? AND MONTH(f.hora_entrada) = ? AND YEAR(f.hora_entrada) = ?
                       ORDER BY f.hora_entrada DESC");
$stmt->execute([$usuario_id, $mes, $anio]);
$fichajes = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/sidebar_empleado.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1 style="color: #1a237e;">
                <i class="fas fa-history"></i> Mi Historial de Fichajes
            </h1>
            <form method="GET" style="display: flex; gap: 0.5rem;">
                <select name="mes" class="form-control" style="padding: 0.5rem; border-radius: 8px;">
                    <?php for($i=1;$i<=12;$i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $mes == $i ? 'selected' : ''; ?>><?php echo strftime('%B', mktime(0,0,0,$i,1)); ?></option>
                    <?php endfor; ?>
                </select>
                <select name="anio" class="form-control" style="padding: 0.5rem; border-radius: 8px;">
                    <option value="<?php echo date('Y'); ?>" <?php echo $anio == date('Y') ? 'selected' : ''; ?>><?php echo date('Y'); ?></option>
                </select>
                <button type="submit" class="btn btn-primario">Ver</button>
            </form>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Pausas</th>
                            <th>Horas Netas</th>
                            <th>Proyecto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fichajes as $f): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($f['hora_entrada'])); ?></td>
                            <td><?php echo date('H:i', strtotime($f['hora_entrada'])); ?></td>
                            <td><?php echo $f['hora_salida'] ? date('H:i', strtotime($f['hora_salida'])) : '<span class="badge verde">En curso</span>'; ?></td>
                            <td><?php 
                                $stmt = $pdo->prepare("SELECT SUM(duracion_minutos) FROM pausas WHERE fichaje_id = ?");
                                $stmt->execute([$f['id']]);
                                $pausas = $stmt->fetchColumn();
                                echo $pausas > 0 ? $pausas.' min' : '-';
                            ?></td>
                            <td><strong><?php echo $f['horas_trabajadas'] > 0 ? $f['horas_trabajadas'].'h' : '-'; ?></strong></td>
                            <td><?php echo escape($f['proyecto'] ?? '-'); ?></td>
                            <td>
                                <?php if ($f['validado']): ?>
                                    <span class="badge verde">Validado</span>
                                <?php else: ?>
                                    <span class="badge amarillo">Pendiente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($fichajes) == 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #757575;">
                                <i class="fas fa-info-circle"></i> No hay fichajes en este período
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>