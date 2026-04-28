<?php
/**
 * FitxApp - Administrador - Gestión Empleados
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

// Obtener todos los empleados
$stmt = $pdo->query("SELECT u.*, d.nombre as departamento
                     FROM usuarios u 
                     LEFT JOIN departamentos d ON u.departamento_id = d.id
                     WHERE u.activo = 1
                     ORDER BY u.apellidos ASC");
$empleados = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Empleados - FitxApp</title>
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
                <i class="fas fa-users"></i> Gestión de Empleados
            </h1>
            <a href="empleados_añadir.php" class="btn btn-exito">
                <i class="fas fa-plus"></i> Nuevo Empleado
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th>Cargo</th>
                            <th>Horas Hoy</th>
                            <th>Horas Mes</th>
                            <th>Cumplimiento</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $emp): 
                            $cumplimiento = calcularCumplimiento($emp['id']);
                            $estado = obtenerEstadoEmpleado($emp['id']);
                            $horasMes = calcularHorasExtra($emp['id'], date('m'), date('Y'));
                        ?>
                        <tr>
                            <td>
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: #1976d2; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                    <?php echo strtoupper(substr($emp['nombre'], 0, 1) . substr($emp['apellidos'], 0, 1)); ?>
                                </div>
                            </td>
                            <td><strong><?php echo escape($emp['nombre'] . ' ' . $emp['apellidos']); ?></strong></td>
                            <td><?php echo escape($emp['departamento'] ?? '-'); ?></td>
                            <td><?php echo escape($emp['cargo']); ?></td>
                            <td><?php echo $cumplimiento['horas_trabajadas']; ?>h</td>
                            <td><?php echo $horasMes['trabajadas']; ?>h</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 80px; height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden;">
                                        <div style="width: <?php echo $cumplimiento['porcentaje']; ?>%; height: 100%; background: <?php echo $cumplimiento['color']; ?>"></div>
                                    </div>
                                    <span style="color: <?php echo $cumplimiento['color']; ?>; font-weight: 600;"><?php echo $cumplimiento['porcentaje']; ?>%</span>
                                </div>
                            </td>
                            <td>
                                <?php if ($estado['estado'] == 'fichado'): ?>
                                    <span class="badge verde">Trabajando</span>
                                <?php elseif ($estado['estado'] == 'pausa'): ?>
                                    <span class="badge amarillo">En pausa</span>
                                <?php else: ?>
                                    <span class="badge rojo">Fuera</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="empleados_ver.php?id=<?php echo $emp['id']; ?>" class="btn btn-primario" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="empleados_editar.php?id=<?php echo $emp['id']; ?>" class="btn btn-primario" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
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