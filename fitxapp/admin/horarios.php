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
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        header('Location: horarios.php?mensaje=error_seguridad');
        exit;
    }
    
    $usuario_id = (int)$_POST['usuario_id'];
    
    // Agregar segundos a las horas para formato TIME correcto
    function formatearHora($hora) {
        if(empty($hora)) return null;
        if(strlen($hora) == 5) return $hora . ':00';
        return $hora;
    }
    
    $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    
    $campos = ['usuario_id'];
    $valores = [$usuario_id];
    $actualizaciones = [];
    
    foreach ($dias as $dia) {
        $campos[] = $dia.'_inicio';
        $campos[] = $dia.'_fin';
        $valores[] = formatearHora($_POST[$dia.'_inicio']);
        $valores[] = formatearHora($_POST[$dia.'_fin']);
        $actualizaciones[] = $dia.'_inicio = VALUES('.$dia.'_inicio)';
        $actualizaciones[] = $dia.'_fin = VALUES('.$dia.'_fin)';
    }
    
    $campos[] = 'horas_dia';
    $campos[] = 'activo';
    $valores[] = 8;
    $valores[] = 1;
    
    $actualizaciones[] = 'horas_dia = VALUES(horas_dia)';
    $actualizaciones[] = 'activo = 1';
    
    $sql = "INSERT INTO horarios (".implode(', ', $campos).", fecha_creacion)
            VALUES (".implode(', ', array_fill(0, count($campos), '?')).", NOW())
            ON DUPLICATE KEY UPDATE ".implode(', ', $actualizaciones);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($valores);
    
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
                                <button onclick="editarHorario(<?php echo $e['id']; ?>, '<?php echo $e['lunes_inicio'] ?? ''; ?>', '<?php echo $e['lunes_fin'] ?? ''; ?>', '<?php echo $e['martes_inicio'] ?? ''; ?>', '<?php echo $e['martes_fin'] ?? ''; ?>', '<?php echo $e['miercoles_inicio'] ?? ''; ?>', '<?php echo $e['miercoles_fin'] ?? ''; ?>', '<?php echo $e['jueves_inicio'] ?? ''; ?>', '<?php echo $e['jueves_fin'] ?? ''; ?>', '<?php echo $e['viernes_inicio'] ?? ''; ?>', '<?php echo $e['viernes_fin'] ?? ''; ?>')" class="btn btn-primario" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
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
                    <input type="hidden" name="csrf_token" value="<?php echo escape(generarTokenCSRF()); ?>">
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
function editarHorario(id, lunes_inicio, lunes_fin, martes_inicio, martes_fin, miercoles_inicio, miercoles_fin, jueves_inicio, jueves_fin, viernes_inicio, viernes_fin) {
    document.getElementById('modal_usuario_id').value = id;
    
    document.getElementById('modal_lunes_inicio').value = lunes_inicio || '08:00';
    document.getElementById('modal_lunes_fin').value = lunes_fin || '17:00';
    document.getElementById('modal_martes_inicio').value = martes_inicio || '08:00';
    document.getElementById('modal_martes_fin').value = martes_fin || '17:00';
    document.getElementById('modal_miercoles_inicio').value = miercoles_inicio || '08:00';
    document.getElementById('modal_miercoles_fin').value = miercoles_fin || '17:00';
    document.getElementById('modal_jueves_inicio').value = jueves_inicio || '08:00';
    document.getElementById('modal_jueves_fin').value = jueves_fin || '17:00';
    document.getElementById('modal_viernes_inicio').value = viernes_inicio || '08:00';
    document.getElementById('modal_viernes_fin').value = viernes_fin || '17:00';

    document.getElementById('modalHorario').classList.add('activo');
}

document.querySelectorAll('.modal-cerrar, .modal-cancelar').forEach(el => {
    el.addEventListener('click', () => {
        document.getElementById('modalHorario').classList.remove('activo');
    });
});

// Cerrar al hacer clic fuera del modal
document.getElementById('modalHorario').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.remove('activo');
    }
});
</script>

<script src="../assets/js/main.js"></script>
</body>
</html>