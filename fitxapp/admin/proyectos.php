<?php
/**
 * FitxApp - Administrador - Gestión Proyectos
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

// Obtener todos los proyectos
$stmt = $pdo->query("SELECT p.*, COUNT(DISTINCT up.usuario_id) as empleados_asignados, 
                     SUM(f.horas_trabajadas) as horas_consumidas
                     FROM proyectos p
                     LEFT JOIN usuario_proyectos up ON p.id = up.proyecto_id
                     LEFT JOIN fichajes f ON p.id = f.proyecto_id
                     GROUP BY p.id
                     ORDER BY p.estado DESC, p.nombre ASC");
$proyectos = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Proyectos - FitxApp</title>
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
                <i class="fas fa-project-diagram"></i> Gestión de Proyectos
            </h1>
            <a href="proyectos_añadir.php" class="btn btn-exito">
                <i class="fas fa-plus"></i> Nuevo Proyecto
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Proyecto</th>
                            <th>Cliente</th>
                            <th>Empleados</th>
                            <th>Presupuesto</th>
                            <th>Consumido</th>
                            <th>Progreso</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proyectos as $p): 
                            $progreso = $p['horas_presupuestadas'] > 0 ? min(100, round(($p['horas_consumidas'] / $p['horas_presupuestadas']) * 100, 1)) : 0;
                            $colorProgreso = $progreso < 70 ? '#43a047' : ($progreso < 90 ? '#f57c00' : '#c62828');
                        ?>
                        <tr>
                            <td>
                                <div style="width: 12px; height: 12px; border-radius: 50%; background: <?php echo $p['color']; ?>"></div>
                            </td>
                            <td><strong><?php echo escape($p['nombre']); ?></strong></td>
                            <td><?php echo escape($p['cliente']); ?></td>
                            <td><span class="badge azul"><?php echo $p['empleados_asignados']; ?></span></td>
                            <td><?php echo $p['horas_presupuestadas']; ?>h</td>
                            <td><?php echo $p['horas_consumidas'] ?? 0; ?>h</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 100px; height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden;">
                                        <div style="width: <?php echo $progreso; ?>%; height: 100%; background: <?php echo $colorProgreso; ?>"></div>
                                    </div>
                                    <span style="color: <?php echo $colorProgreso; ?>; font-weight: 600;"><?php echo $progreso; ?>%</span>
                                </div>
                            </td>
                            <td>
                                <?php if ($p['estado'] == 'activo'): ?>
                                    <span class="badge verde">Activo</span>
                                <?php elseif ($p['estado'] == 'pausado'): ?>
                                    <span class="badge amarillo">Pausado</span>
                                <?php else: ?>
                                    <span class="badge azul">Finalizado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="proyectos_editar.php?id=<?php echo $p['id']; ?>" class="btn btn-primario" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
                                    <i class="fas fa-edit"></i>
                                </a>
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