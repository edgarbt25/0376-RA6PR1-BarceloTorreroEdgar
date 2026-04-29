<?php
/**
 * FitxApp - Empleado - Mi Perfil
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirEmpleado();
$usuario_id = $_SESSION['usuario_id'];

$mensaje = '';

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT u.*, d.nombre as departamento FROM usuarios u LEFT JOIN departamentos d ON u.departamento_id = d.id WHERE u.id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guardar_datos'])) {
        $telefono = $_POST['telefono'];
        $stmt = $pdo->prepare("UPDATE usuarios SET telefono = ? WHERE id = ?");
        $stmt->execute([$telefono, $usuario_id]);
        $mensaje = '✅ Datos actualizados correctamente';
    }
    
    if (isset($_POST['cambiar_password'])) {
        $actual = $_POST['password_actual'];
        $nueva = $_POST['password_nueva'];
        
        if (password_verify($actual, $usuario['password'])) {
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([password_hash($nueva, PASSWORD_DEFAULT), $usuario_id]);
            $mensaje = '✅ Contraseña cambiada correctamente';
        } else {
            $mensaje = '❌ La contraseña actual no es correcta';
        }
    }
}

// Obtener horario
$stmt = $pdo->prepare("SELECT * FROM horarios WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$horario = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/sidebar_empleado.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="margin-bottom: 2rem; color: #1a237e;">
            <i class="fas fa-user"></i> Mi Perfil
        </h1>

        <?php if ($mensaje): ?>
        <div class="toast <?php echo strpos($mensaje, '✅') !== false ? 'exito' : 'error'; ?>" style="display: block; margin-bottom: 1rem;">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-circle"></i> Datos Personales</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-grupo">
                            <label>Nombre</label>
                            <input type="text" class="form-control" value="<?php echo escape($usuario['nombre'] . ' ' . $usuario['apellidos']); ?>" disabled>
                        </div>
                        <div class="form-grupo">
                            <label>Email</label>
                            <input type="email" class="form-control" value="<?php echo escape($usuario['email']); ?>" disabled>
                        </div>
                        <div class="form-grupo">
                            <label>Departamento</label>
                            <input type="text" class="form-control" value="<?php echo escape($usuario['departamento'] ?? '-'); ?>" disabled>
                        </div>
                        <div class="form-grupo">
                            <label>Cargo</label>
                            <input type="text" class="form-control" value="<?php echo escape($usuario['cargo'] ?? '-'); ?>" disabled>
                        </div>
                        <div class="form-grupo">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?php echo escape($usuario['telefono'] ?? ''); ?>">
                        </div>
                        <button type="submit" name="guardar_datos" class="btn btn-primario">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-key"></i> Cambiar Contraseña</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-grupo">
                            <label>Contraseña Actual</label>
                            <input type="password" name="password_actual" class="form-control" required>
                        </div>
                        <div class="form-grupo">
                            <label>Nueva Contraseña</label>
                            <input type="password" name="password_nueva" class="form-control" required>
                        </div>
                        <button type="submit" name="cambiar_password" class="btn btn-exito">
                            <i class="fas fa-key"></i> Cambiar Contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h3><i class="fas fa-calendar-alt"></i> Mi Horario Asignado</h3>
            </div>
            <div class="card-body">
                <?php if ($horario): ?>
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Lunes</th>
                            <th>Martes</th>
                            <th>Miércoles</th>
                            <th>Jueves</th>
                            <th>Viernes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo $horario['lunes_inicio'] ? date('H:i', strtotime($horario['lunes_inicio'])).' - '.date('H:i', strtotime($horario['lunes_fin'])) : '-'; ?></td>
                            <td><?php echo $horario['martes_inicio'] ? date('H:i', strtotime($horario['martes_inicio'])).' - '.date('H:i', strtotime($horario['martes_fin'])) : '-'; ?></td>
                            <td><?php echo $horario['miercoles_inicio'] ? date('H:i', strtotime($horario['miercoles_inicio'])).' - '.date('H:i', strtotime($horario['miercoles_fin'])) : '-'; ?></td>
                            <td><?php echo $horario['jueves_inicio'] ? date('H:i', strtotime($horario['jueves_inicio'])).' - '.date('H:i', strtotime($horario['jueves_fin'])) : '-'; ?></td>
                            <td><?php echo $horario['viernes_inicio'] ? date('H:i', strtotime($horario['viernes_inicio'])).' - '.date('H:i', strtotime($horario['viernes_fin'])) : '-'; ?></td>
                        </tr>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color: #757575;">No tienes horario asignado. Contacta con tu supervisor.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>