<?php
/**
 * FitxApp - Administrador - Vista Fichajes Tiempo Real
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

// Obtener fichajes de hoy
$hoy = date('Y-m-d');
$stmt = $pdo->prepare("SELECT f.*, u.nombre, u.apellidos, p.nombre as proyecto, d.nombre as departamento
                       FROM fichajes f
                       JOIN usuarios u ON f.usuario_id = u.id
                       LEFT JOIN proyectos p ON f.proyecto_id = p.id
                       LEFT JOIN departamentos d ON u.departamento_id = d.id
                       WHERE DATE(f.hora_entrada) = ?
                       ORDER BY f.hora_entrada DESC");
$stmt->execute([$hoy]);
$fichajesHoy = $stmt->fetchAll();

// Contar empleados fichados ahora
$stmt = $pdo->query("SELECT COUNT(*) FROM fichajes WHERE hora_salida IS NULL");
$fichadosAhora = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Fichajes Tiempo Real - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta http-equiv="refresh" content="30">
</head>
<body>

<?php include '../includes/sidebar_admin.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="margin-bottom: 2rem; color: #1a237e;">
            <i class="fas fa-clock"></i> Vista Fichajes Tiempo Real
        </h1>

        <div class="kpi-grid">
            <div class="kpi-card verde">
                <div class="kpi-valor"><?php echo $fichadosAhora; ?></div>
                <div class="kpi-titulo">Empleados trabajando ahora</div>
                <i class="fas fa-user-clock kpi-icono"></i>
            </div>
            <div class="kpi-card azul">
                <div class="kpi-valor"><?php echo count($fichajesHoy); ?></div>
                <div class="kpi-titulo">Fichajes registrados hoy</div>
                <i class="fas fa-clipboard-list kpi-icono"></i>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Todos los fichajes de hoy</h3>
                <span class="badge azul">Actualiza cada 30s</span>
            </div>
            <div class="card-body">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Departamento</th>
                            <th>Proyecto</th>
                            <th>Hora Entrada</th>
                            <th>Hora Salida</th>
                            <th>Horas</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fichajesHoy as $f): ?>
                        <tr>
                            <td><strong><?php echo escape($f['nombre'] . ' ' . $f['apellidos']); ?></strong></td>
                            <td><?php echo escape($f['departamento'] ?? '-'); ?></td>
                            <td><?php echo escape($f['proyecto'] ?? '-'); ?></td>
                            <td><?php echo date('H:i', strtotime($f['hora_entrada'])); ?></td>
                            <td><?php echo $f['hora_salida'] ? date('H:i', strtotime($f['hora_salida'])) : '<span class="badge verde">En curso</span>'; ?></td>
                            <td><?php echo $f['horas_trabajadas'] > 0 ? $f['horas_trabajadas'].'h' : '-'; ?></td>
                            <td>
                                <?php if ($f['validado']): ?>
                                    <span class="badge verde">Validado</span>
                                <?php else: ?>
                                    <span class="badge amarillo">Pendiente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>