<?php
/**
 * FitxApp - Administrador - Incidencias y Correcciones
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

// Obtener solicitudes de corrección
$stmt = $pdo->query("SELECT s.*, u.nombre, u.apellidos, f.hora_entrada, f.hora_salida
                       FROM solicitudes_correccion s
                       JOIN usuarios u ON s.usuario_id = u.id
                       LEFT JOIN fichajes f ON s.fichaje_id = f.id
                       ORDER BY s.fecha_solicitud DESC");
$solicitudes = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incidencias y Correcciones - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/sidebar_admin.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="margin-bottom: 2rem; color: #1a237e;">
            <i class="fas fa-exclamation-triangle"></i> Incidencias y Correcciones
        </h1>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Solicitudes de corrección</h3>
            </div>
            <div class="card-body">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Fecha Solicitud</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $s): ?>
                        <tr>
                            <td><strong><?php echo escape($s['nombre'] . ' ' . $s['apellidos']); ?></strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($s['fecha_solicitud'])); ?></td>
                            <td><?php echo escape($s['motivo']); ?></td>
                            <td>
                                <?php if ($s['estado'] == 'pendiente'): ?>
                                    <span class="badge amarillo">Pendiente</span>
                                <?php elseif ($s['estado'] == 'aprobada'): ?>
                                    <span class="badge verde">Aprobada</span>
                                <?php else: ?>
                                    <span class="badge rojo">Rechazada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['estado'] == 'pendiente'): ?>
                                <a href="incidencias_resolver.php?id=<?php echo $s['id']; ?>" class="btn btn-primario" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
                                    <i class="fas fa-edit"></i> Resolver
                                </a>
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