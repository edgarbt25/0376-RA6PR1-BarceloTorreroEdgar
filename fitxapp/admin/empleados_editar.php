<?php
/**
 * FitxApp - Administrador - Editar Empleado
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

$id = $_GET['id'];

// Obtener datos del empleado
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$empleado = $stmt->fetch();

if (!$empleado) {
    header('Location: empleados.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $email = $_POST['email'];
    $departamento_id = $_POST['departamento_id'];
    $cargo = $_POST['cargo'];
    $rol = $_POST['rol'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellidos = ?, email = ?, departamento_id = ?, cargo = ?, rol = ?, activo = ? WHERE id = ?");
    $stmt->execute([$nombre, $apellidos, $email, $departamento_id, $cargo, $rol, $activo, $id]);
    
    if (!empty($_POST['password_nueva'])) {
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($_POST['password_nueva'], PASSWORD_DEFAULT), $id]);
    }
    
    registrarLog($_SESSION['usuario_id'], 'editar_empleado', 'usuarios', $id);
    header('Location: empleados.php?mensaje=empleado_actualizado');
    exit;
}

// Obtener departamentos
$stmt = $pdo->query("SELECT id, nombre FROM departamentos ORDER BY nombre ASC");
$departamentos = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empleado - FitxApp</title>
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
                <i class="fas fa-user-edit"></i> Editar Empleado
            </h1>
            <a href="empleados.php" class="btn btn-secundario">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-grupo">
                            <label>Nombre</label>
                            <input type="text" name="nombre" class="form-control" value="<?php echo escape($empleado['nombre']); ?>" required>
                        </div>
                        <div class="form-grupo">
                            <label>Apellidos</label>
                            <input type="text" name="apellidos" class="form-control" value="<?php echo escape($empleado['apellidos']); ?>" required>
                        </div>
                        <div class="form-grupo">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo escape($empleado['email']); ?>" required>
                        </div>
                        <div class="form-grupo">
                            <label>Nueva Contraseña (opcional)</label>
                            <input type="password" name="password_nueva" class="form-control">
                        </div>
                        <div class="form-grupo">
                            <label>Departamento</label>
                            <select name="departamento_id" class="form-control" required>
                                <option value="">Seleccionar departamento</option>
                                <?php foreach ($departamentos as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo $empleado['departamento_id'] == $d['id'] ? 'selected' : ''; ?>><?php echo escape($d['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-grupo">
                            <label>Cargo</label>
                            <input type="text" name="cargo" class="form-control" value="<?php echo escape($empleado['cargo']); ?>" required>
                        </div>
                        <div class="form-grupo">
                            <label>Rol</label>
                            <select name="rol" class="form-control" required>
                                <option value="empleado" <?php echo $empleado['rol'] == 'empleado' ? 'selected' : ''; ?>>Empleado</option>
                                <option value="supervisor" <?php echo $empleado['rol'] == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                                <option value="admin" <?php echo $empleado['rol'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </div>
                        <div class="form-grupo">
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="activo" <?php echo $empleado['activo'] ? 'checked' : ''; ?>>
                                Empleado activo
                            </label>
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem; text-align: right;">
                        <button type="submit" class="btn btn-exito">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>