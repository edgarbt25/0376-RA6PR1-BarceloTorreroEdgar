<?php
/**
 * FitxApp - Empleado - Mis Proyectos
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirLogin();
$usuario_id = $_SESSION['usuario_id'];

// Obtener proyectos del usuario
$stmt = $pdo->prepare("SELECT p.*, up.rol
                       FROM usuario_proyectos up
                       JOIN proyectos p ON up.proyecto_id = p.id
                       WHERE up.usuario_id = ? AND p.estado = 'activo'
                       ORDER BY p.nombre ASC");
$stmt->execute([$usuario_id]);
$proyectos = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Proyectos - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/sidebar_empleado.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="margin-bottom: 2rem; color: #1a237e;">
            <i class="fas fa-project-diagram"></i> Mis Proyectos
        </h1>

        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <?php foreach ($proyectos as $p): ?>
            <div class="card">
                <div class="card-header" style="background: <?php echo $p['color']; ?>; color: white; border-radius: 12px 12px 0 0;">
                    <h3 style="margin: 0;"><i class="fas fa-folder-open"></i> <?php echo escape($p['nombre']); ?></h3>
                </div>
                <div class="card-body">
                    <p style="color: #757575; margin-bottom: 1rem;"><?php echo escape($p['descripcion']); ?></p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Cliente: <strong><?php echo escape($p['cliente']); ?></strong></span>
                        <span class="badge verde"><?php echo escape($p['rol']); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (count($proyectos) == 0): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #757575;">
                <i class="fas fa-folder" style="font-size: 4rem; color: #bdbdbd; margin-bottom: 1rem;"></i>
                <h3>No tienes proyectos asignados</h3>
                <p>Contacta con tu supervisor para que te asigne proyectos.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>