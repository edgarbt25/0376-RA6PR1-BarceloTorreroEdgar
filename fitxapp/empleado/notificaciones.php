<?php
/**
 * FitxApp - Empleado - Notificaciones
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirLogin();
$usuario_id = $_SESSION['usuario_id'];

// Marcar como leida
if (isset($_GET['leer'])) {
    $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$_GET['leer'], $usuario_id]);
    header('Location: notificaciones.php');
    exit;
}

// Marcar todas leidas
if (isset($_GET['todas'])) {
    $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    header('Location: notificaciones.php');
    exit;
}

// Obtener notificaciones
$stmt = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY fecha_creacion DESC");
$stmt->execute([$usuario_id]);
$notificaciones = $stmt->fetchAll();

// Contar no leidas
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0");
$stmt->execute([$usuario_id]);
$noLeidas = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - FitxApp</title>
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
                <i class="fas fa-bell"></i> Mis Notificaciones
                <?php if ($noLeidas > 0): ?>
                    <span class="badge rojo" style="margin-left: 0.5rem;"><?php echo $noLeidas; ?></span>
                <?php endif; ?>
            </h1>
            <?php if ($noLeidas > 0): ?>
            <a href="?todas=1" class="btn btn-primario">
                <i class="fas fa-check-double"></i> Marcar todas leídas
            </a>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-body">
                <?php foreach ($notificaciones as $n): ?>
                <div style="padding: 1rem; border-bottom: 1px solid #eee; background: <?php echo $n['leida'] ? 'white' : '#e3f2fd'; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <i class="fas fa-<?php 
                                echo ($n['tipo'] == 'aviso') ? 'exclamation-triangle' : 
                                     (($n['tipo'] == 'alerta') ? 'bell' : 
                                     (($n['tipo'] == 'correccion') ? 'edit' : 'info-circle')); 
                            ?>" style="color: <?php 
                                echo ($n['tipo'] == 'aviso') ? '#f57c00' : 
                                     (($n['tipo'] == 'alerta') ? '#c62828' : 
                                     (($n['tipo'] == 'correccion') ? '#1976d2' : '#43a047')); 
                            ?>; font-size: 1.2rem;"></i>
                            <div>
                                <div style="font-weight: 600;"><?php echo escape($n['mensaje']); ?></div>
                                <div style="color: #757575; font-size: 0.85rem; margin-top: 0.25rem;">
                                    <?php echo date('d/m/Y H:i', strtotime($n['fecha_creacion'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php if (!$n['leida']): ?>
                        <a href="?leer=<?php echo $n['id']; ?>" class="btn btn-secundario" style="padding: 0.3rem 0.5rem; font-size: 0.8rem;">
                            <i class="fas fa-check"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (count($notificaciones) == 0): ?>
                <div style="text-align: center; padding: 3rem; color: #757575;">
                    <i class="fas fa-bell-slash" style="font-size: 4rem; color: #bdbdbd; margin-bottom: 1rem;"></i>
                    <h3>No tienes notificaciones</h3>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>