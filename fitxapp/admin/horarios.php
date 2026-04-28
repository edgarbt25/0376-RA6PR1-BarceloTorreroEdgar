<?php
/**
 * FitxApp - Administrador - Gestión Horarios
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

// Obtener todos los empleados con sus horarios
$stmt = $pdo->query("SELECT u.id, u.nombre, u.apellidos, d.nombre as departamento,
                            h.lunes_inicio, h.lunes_fin, h.martes_inicio, h.martes_fin,
                            h.miercoles_inicio, h.miercoles_fin, h.jueves_inicio, h.jueves_fin,
                            h.viernes_inicio, h.viernes_fin, h.horas_dia
                     FROM usuarios u
                     LEFT JOIN departamentos d ON u.departamento_id = d.id
                     LEFT JOIN horarios h ON u.id = h.usuario_id
                     WHERE u.activo = 1
                     ORDER BY u.apellidos ASC");
$empleados = $stmt->fetchAll();

// Guardar cambios de horario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_POST['usuario_id'];
    $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
    
    $camposInsert = [];
    $valoresUpdate = [];
    foreach ($dias as $dia) {
        $camposInsert[] = $dia.'_inicio';
        $camposInsert[] = $dia.'_fin';
        $valoresUpdate[] = $dia.'_inicio = ?';
        $valoresUpdate[] = $dia.'_fin = ?';
    }
    
    $sql = "INSERT INTO horarios (usuario_id, ".implode(', ', $camposInsert).", horas_dia, activo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE ".implode(', ', $valoresUpdate).", horas_dia = ?, activo = 1";
    
    $params = [
        $usuario_id,
        $_POST['lunes_inicio'], $_POST['lunes_fin'],
        $_POST['martes_inicio'], $_POST['martes_fin'],
        $_POST['miercoles_inicio'], $_POST['miercoles_fin'],
        $_POST['jueves_inicio'], $_POST['jueves_fin'],
        $_POST['viernes_inicio'], $_POST['viernes_fin'],
        '00:00', '00:00',
        '00:00', '00:00',
        8,
        $_POST['lunes_inicio'], $_POST['lunes_fin'],
        $_POST['martes_inicio'], $_POST['martes_fin'],
        $_POST['miercoles_inicio'], $_POST['miercoles_fin'],
        $_POST['jueves_inicio'], $_POST['jueves_fin'],
        $_POST['viernes_inicio'], $_POST['viernes_fin'],
        '00:00', '00:00',
        '00:00', '00:00',
        8
    ];
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    registrarLog($_SESSION['usuario_id'], 'editar_horario', 'horarios', $usuario_id);
    header('Location: horarios.php?mensaje=horario_actualizado');
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Horarios - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/sidebar_admin.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="margin-bottom: 2rem; color: #1a237e;">
            <i class="fas fa-calendar-alt"></i> Gestión de Horarios
        </h1>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i> Horarios de empleados</h3>
            </div>
            <div class="card-body">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Departamento</th>
                            <th>Lunes</th>
                            <th>Martes</th>
                            <th>Miércoles</th>
                            <th>Jueves</th>
                            <th>Viernes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $e): ?>
                        <tr>
                            <td><strong><?php echo escape($e['nombre'] . ' ' . $e['apellidos']); ?></strong></td>
                            <td><?php echo escape($e['departamento'] ?? '-'); ?></td>
                            <td><?php echo $e['lunes_inicio'] ? date('H:i', strtotime($e['lunes_inicio'])).' - '.date('H:i', strtotime($e['lunes_fin'])) : '<span class="badge rojo">Sin horario</span>'; ?></td>
                            <td><?php echo $e['martes_inicio'] ? date('H:i', strtotime($e['martes_inicio'])).' - '.date('H:i', strtotime($e['martes_fin'])) : ''; ?></td>
                            <td><?php echo $e['miercoles_inicio'] ? date('H:i', strtotime($e['miercoles_inicio'])).' - '.date('H:i', strtotime($e['miercoles_fin'])) : ''; ?></td>
                            <td><?php echo $e['jueves_inicio'] ? date('H:i', strtotime($e['jueves_inicio'])).' - '.date('H:i', strtotime($e['jueves_fin'])) : ''; ?></td>
                            <td><?php echo $e['viernes_inicio'] ? date('H:i', strtotime($e['viernes_inicio'])).' - '.date('H:i', strtotime($e['viernes_fin'])) : ''; ?></td>
                            <td>
                                <button onclick="editarHorario(<?php echo htmlspecialchars(json_encode($e), ENT_QUOTES); ?>)" class="btn btn-primario" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Modal Editar Horario -->
<div id="modalHorario" class="modal">
    <div class="modal-contenido">
        <div class="modal-header">
            <h3>Editar Horario</h3>
            <span class="modal-cerrar">&times;</span>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="usuario_id" id="modal_usuario_id">
                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <?php foreach(['Lunes','Martes','Miércoles','Jueves','Viernes'] as $i => $dia):
                        $campo = strtolower(str_replace('é', 'e', $dia));
                    ?>
                    <div style="grid-column: span 1;">
                        <label><strong><?php echo $dia; ?></strong></label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="time" name="<?php echo $campo; ?>_inicio" id="modal_<?php echo $campo; ?>_inicio" class="form-control">
                            <input type="time" name="<?php echo $campo; ?>_fin" id="modal_<?php echo $campo; ?>_fin" class="form-control">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secundario modal-cancelar">Cancelar</button>
                <button type="submit" class="btn btn-exito">Guardar Horario</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarHorario(empleado) {
    document.getElementById('modal_usuario_id').value = empleado.id;
    const dias = ['lunes','martes','miercoles','jueves','viernes'];
    dias.forEach(dia => {
        document.getElementById('modal_'+dia+'_inicio').value = empleado[dia+'_inicio'] || '08:00';
        document.getElementById('modal_'+dia+'_fin').value = empleado[dia+'_fin'] || '17:00';
    });
    document.getElementById('modalHorario').style.display = 'flex';
}

document.querySelectorAll('.modal-cerrar, .modal-cancelar').forEach(el => {
    el.addEventListener('click', () => document.getElementById('modalHorario').style.display = 'none');
});
</script>

<script src="../assets/js/main.js"></script>
</body>
</html>