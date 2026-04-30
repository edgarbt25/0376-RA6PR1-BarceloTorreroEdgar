<?php
/**
 * FitxApp - Empleado - Mis Proyectos
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirEmpleado();
$usuario_id = $_SESSION['usuario_id'];

// Procesar solicitud de proyecto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Añadir proyecto
    if (isset($_POST['accion']) && $_POST['accion'] == 'solicitar_proyecto') {
        $proyecto_id = $_POST['proyecto_solicitar'];
        
        // Verificar que ya no esta asignado
        $stmtCheck = $pdo->prepare("SELECT id FROM usuario_proyectos WHERE usuario_id = ? AND proyecto_id = ? LIMIT 1");
        $stmtCheck->execute([$usuario_id, $proyecto_id]);
        
        if ($stmtCheck->rowCount() == 0) {
            $stmt = $pdo->prepare("INSERT INTO usuario_proyectos (usuario_id, proyecto_id, rol) 
                                   VALUES (?, ?, 'miembro')");
            $stmt->execute([$usuario_id, $proyecto_id]);
        }
        
        header('Location: mis_proyectos.php?mensaje=proyecto_asignado');
        exit;
    }
    
    // Eliminar proyecto
    if (isset($_POST['accion']) && $_POST['accion'] == 'eliminar_proyecto') {
        $proyecto_id = $_POST['proyecto_id'];
        
        $stmt = $pdo->prepare("DELETE FROM usuario_proyectos WHERE usuario_id = ? AND proyecto_id = ? LIMIT 1");
        $stmt->execute([$usuario_id, $proyecto_id]);
        
        header('Location: mis_proyectos.php?mensaje=proyecto_eliminado');
        exit;
    }
}

// Obtener proyectos del usuario
$stmt = $pdo->prepare("SELECT p.*, up.rol
                       FROM usuario_proyectos up
                       JOIN proyectos p ON up.proyecto_id = p.id
                       WHERE up.usuario_id = ? AND p.estado = 'activo'
                       ORDER BY p.nombre ASC");
$stmt->execute([$usuario_id]);
$proyectos = $stmt->fetchAll();

// Obtener proyectos NO asignados al usuario
$stmt = $pdo->prepare("SELECT id, nombre FROM proyectos 
                       WHERE estado = 'activo' AND id NOT IN (SELECT proyecto_id FROM usuario_proyectos WHERE usuario_id = ?)
                       ORDER BY nombre ASC");
$stmt->execute([$usuario_id]);
$proyectosDisponibles = $stmt->fetchAll();

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
                    
                    <form method="POST" style="margin-top: 1.5rem;">
                        <input type="hidden" name="proyecto_id" value="<?php echo $p['id']; ?>">
                        <button type="submit" name="accion" value="eliminar_proyecto" class="btn btn-peligro" style="width: 100%;" onclick="return confirm('¿Seguro que quieres abandonar este proyecto?')">
                            <i class="fas fa-times"></i> Abandonar Proyecto
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
        
        <!-- Añadir nuevo proyecto SIEMPRE visible incluso si ya tienes proyectos -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header" style="background: #1a237e; color: white;">
                <h3 style="margin: 0;"><i class="fas fa-plus"></i> Añadir nuevo proyecto</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div style="display: flex; gap: 1rem; align-items: end;">
                        <div style="flex: 1;">
                            <label class="form-label">Selecciona proyecto</label>
                            <select name="proyecto_solicitar" class="form-control" required style="padding: 0.8rem;">
                                <option value="">-- Seleccionar proyecto --</option>
                                <?php foreach ($proyectosDisponibles as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo escape($p['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="accion" value="solicitar_proyecto" class="btn btn-primario">
                            <i class="fas fa-plus"></i> Añadir Proyecto
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>