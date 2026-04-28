<?php
/**
 * FitxApp - Administrador - Validar Fichajes
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

// Obtener fichajes pendientes de validación
$stmt = $pdo->query("SELECT f.*, u.nombre, u.apellidos, p.nombre as proyecto, d.nombre as departamento
                       FROM fichajes f
                       JOIN usuarios u ON f.usuario_id = u.id
                       LEFT JOIN proyectos p ON f.proyecto_id = p.id
                       LEFT JOIN departamentos d ON u.departamento_id = d.id
                       WHERE f.validado = 0 AND f.hora_salida IS NOT NULL
                       ORDER BY f.hora_entrada DESC");
$fichajesPendientes = $stmt->fetchAll();

// Procesar validación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fichaje_id = $_POST['fichaje_id'];
    $accion = $_POST['accion'];
    
    if ($accion == 'validar') {
        $stmt = $pdo->prepare("UPDATE fichajes SET validado = 1, validado_por = ?, fecha_validacion = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id'], $fichaje_id]);
        registrarLog($_SESSION['usuario_id'], 'validar_fichaje', 'fichajes', $fichaje_id);
        header('Location: fichajes_validar.php?mensaje=validado');
        exit;
    } elseif ($accion == 'rechazar') {
        $stmt = $pdo->prepare("DELETE FROM fichajes WHERE id = ?");
        $stmt->execute([$fichaje_id]);
        registrarLog($_SESSION['usuario_id'], 'rechazar_fichaje', 'fichajes', $fichaje_id);
        header('Location: fichajes_validar.php?mensaje=rechazado');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Fichajes - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/sidebar_admin.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="margin-bottom: 2rem; color: #1a237e;">
            <i class="fas fa-check-circle"></i> Validar Fichajes
        </h1>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-hourglass-half"></i> Fichajes pendientes de validación</h3>
                <span class="badge amarillo"><?php echo count($fichajesPendientes); ?> pendientes</span>
            </div>
            <div class="card-body">
                <?php if (count($fichajesPendientes) == 0): ?>
                    <div style="text-align: center; padding: 3rem; color: #757575;">
                        <i class="fas fa-check-double" style="font-size: 4rem; color: #43a047; margin-bottom: 1rem;"></i>
                        <h3>¡Genial!</h3>
                        <p>No hay fichajes pendientes de validación.</p>
                    </div>
                <?php else: ?>
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Fecha</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Horas</th>
                            <th>IP</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fichajesPendientes as $f): ?>
                        <tr>
                            <td><strong><?php echo escape($f['nombre'] . ' ' . $f['apellidos']); ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($f['hora_entrada'])); ?></td>
                            <td><?php echo date('H:i', strtotime($f['hora_entrada'])); ?></td>
                            <td><?php echo date('H:i', strtotime($f['hora_salida'])); ?></td>
                            <td><?php echo $f['horas_trabajadas']; ?>h</td>
                            <td><?php echo $f['ip_address']; ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="fichaje_id" value="<?php echo $f['id']; ?>">
                                    <button type="submit" name="accion" value="validar" class="btn btn-exito" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="submit" name="accion" value="rechazar" class="btn btn-peligro" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;" onclick="return confirm('¿Rechazar este fichaje?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>