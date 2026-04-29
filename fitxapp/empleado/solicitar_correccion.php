<?php
/**
 * FitxApp - Empleado - Solicitar Corrección
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirEmpleado();
$usuario_id = $_SESSION['usuario_id'];

$mensaje = '';

// Guardar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'];
    $tipo = $_POST['tipo'];
    $hora_correcta = $_POST['hora_correcta'];
    $motivo = $_POST['motivo'];
    
    $stmt = $pdo->prepare("INSERT INTO solicitudes_correccion (usuario_id, motivo, descripcion, hora_correcta_entrada)
                           VALUES (?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $tipo, $motivo, $fecha . ' ' . $hora_correcta]);
    
    $mensaje = '✅ Solicitud enviada correctamente';
}

// Obtener solicitudes anteriores
$stmt = $pdo->prepare("SELECT * FROM solicitudes_correccion WHERE usuario_id = ? ORDER BY fecha_solicitud DESC LIMIT 10");
$stmt->execute([$usuario_id]);
$solicitudes = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Corrección - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/sidebar_empleado.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="margin-bottom: 2rem; color: #1a237e;">
            <i class="fas fa-edit"></i> Solicitar Corrección de Fichaje
        </h1>

        <?php if ($mensaje): ?>
        <div class="toast exito" style="display: block; margin-bottom: 1rem;">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Nueva Solicitud</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-grupo">
                            <label>Fecha del fichaje</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-grupo">
                            <label>Tipo de problema</label>
                            <select name="tipo" class="form-control" required>
                                <option value="olvido_entrada">Olvido fichar entrada</option>
                                <option value="olvido_salida">Olvido fichar salida</option>
                                <option value="hora_incorrecta">Hora registrada incorrecta</option>
                                <option value="error_sistema">Error del sistema</option>
                            </select>
                        </div>
                        <div class="form-grupo">
                            <label>Hora correcta</label>
                            <input type="time" name="hora_correcta" class="form-control" required>
                        </div>
                        <div class="form-grupo">
                            <label>Motivo / Descripción</label>
                            <textarea name="motivo" class="form-control" rows="4" required placeholder="Explica brevemente el motivo de la corrección..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primario" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> Enviar Solicitud
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Mis Solicitudes Anteriores</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($solicitudes as $s): ?>
                    <div style="padding: 1rem; border-bottom: 1px solid #eee; margin-bottom: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong><?php echo date('d/m/Y', strtotime($s['fecha'])); ?></strong>
                            <?php if ($s['estado'] == 'pendiente'): ?>
                                <span class="badge amarillo">Pendiente</span>
                            <?php elseif ($s['estado'] == 'aprobada'): ?>
                                <span class="badge verde">Aprobada</span>
                            <?php else: ?>
                                <span class="badge rojo">Rechazada</span>
                            <?php endif; ?>
                        </div>
                        <div style="color: #666; font-size: 0.9rem;">
                            <?php echo escape($s['tipo_problema']); ?> - <?php echo $s['hora_correcta']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>