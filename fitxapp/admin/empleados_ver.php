<?php
/**
 * FitxApp - Administrador - Ver Detalle Empleado
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

$id = $_GET['id'];

// Obtener datos del empleado
$stmt = $pdo->prepare("SELECT u.*, d.nombre as departamento FROM usuarios u LEFT JOIN departamentos d ON u.departamento_id = d.id WHERE u.id = ?");
$stmt->execute([$id]);
$empleado = $stmt->fetch();

if (!$empleado) {
    header('Location: empleados.php');
    exit;
}

// Estadísticas
$mesActual = date('m');
$anioActual = date('Y');

$stmt = $pdo->prepare("SELECT SUM(horas_trabajadas) FROM fichajes WHERE usuario_id = ? AND MONTH(hora_entrada) = ?");
$stmt->execute([$id, $mesActual]);
$horasMes = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM fichajes WHERE usuario_id = ? AND DATE(hora_entrada) = CURDATE()");
$stmt->execute([$id]);
$hoyFichado = $stmt->fetchColumn();

$estado = obtenerEstadoEmpleado($id);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Empleado - FitxApp</title>
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
                <i class="fas fa-eye"></i> Perfil Empleado
            </h1>
            <div style="display: flex; gap: 0.5rem;">
                <a href="empleados_editar.php?id=<?php echo $id; ?>" class="btn btn-primario">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="empleados.php" class="btn btn-secundario">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="grid" style="grid-template-columns: 1fr 2fr; gap: 2rem;">
            <div class="card">
                <div class="card-body" style="text-align: center;">
                    <div style="width: 120px; height: 120px; background: linear-gradient(135deg, #1976d2, #1a237e); border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user" style="color: white; font-size: 3rem;"></i>
                    </div>
                    <h2 style="margin-bottom: 0.5rem;"><?php echo escape($empleado['nombre'] . ' ' . $empleado['apellidos']); ?></h2>
                    <p style="color: #757575; margin-bottom: 1rem;"><?php echo escape($empleado['cargo']); ?></p>
                    
                    <div class="badge <?php echo $estado == 'fichado' ? 'verde' : ($estado == 'pausa' ? 'amarillo' : 'rojo'); ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
                        <?php echo $estado == 'fichado' ? '✅ Trabajando' : ($estado == 'pausa' ? '⏸ En Pausa' : '❌ Fuera'); ?>
                    </div>
                    
                    <hr style="margin: 1.5rem 0;">
                    
                    <div style="text-align: left;">
                        <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo escape($empleado['email']); ?></p>
                        <p><i class="fas fa-phone"></i> <strong>Teléfono:</strong> <?php echo escape($empleado['telefono'] ?? '-'); ?></p>
                        <p><i class="fas fa-building"></i> <strong>Departamento:</strong> <?php echo escape($empleado['departamento'] ?? '-'); ?></p>
                        <p><i class="fas fa-clock"></i> <strong>Horas este mes:</strong> <?php echo number_format($horasMes, 1); ?>h</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Últimos Fichajes</h3>
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
                            $stmt = $pdo->prepare("SELECT * FROM fichajes WHERE usuario_id = ? ORDER BY hora_entrada DESC LIMIT 10");
                            $stmt->execute([$id]);
                            $fichajes = $stmt->fetchAll();
                            
                            foreach ($fichajes as $f):
                            ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($f['hora_entrada'])); ?></td>
                                <td><?php echo date('H:i', strtotime($f['hora_entrada'])); ?></td>
                                <td><?php echo $f['hora_salida'] ? date('H:i', strtotime($f['hora_salida'])) : '-'; ?></td>
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
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>