<?php
/**
 * FitxApp - Administrador - Resolver Incidencia
 */

// Habilitar visualización de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

// Verificar que se envía id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: incidencias.php');
    exit;
}

$id = (int)$_GET['id'];

// Obtener datos de la solicitud
$stmt = $pdo->prepare("SELECT s.*, u.nombre, u.apellidos, f.hora_entrada, f.hora_salida 
                       FROM solicitudes_correccion s
                       JOIN usuarios u ON s.usuario_id = u.id
                       LEFT JOIN fichajes f ON s.fichaje_id = f.id
                       WHERE s.id = ?");
$stmt->execute([$id]);
$solicitud = $stmt->fetch();

if (!$solicitud || $solicitud['estado'] != 'pendiente') {
    header('Location: incidencias.php');
    exit;
}

$error = '';
$exito = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Error de seguridad';
    } else {
        $accion = trim($_POST['accion'] ?? '');
        $comentario = trim($_POST['comentario'] ?? '');
        
        if (!in_array($accion, ['aprobar', 'rechazar'])) {
            $error = 'Acción no válida';
        } else {
            $nuevo_estado = $accion == 'aprobar' ? 'aprobada' : 'rechazada';
            
            // Actualizar estado - NOMBRE CORRECTO DE CAMPO EN BASE DE DATOS: motivo_resolucion
            $stmt = $pdo->prepare("UPDATE solicitudes_correccion 
                                   SET estado = ?, 
                                       motivo_resolucion = ?, 
                                       fecha_resolucion = NOW(), 
                                       admin_id = ? 
                                   WHERE id = ?");
            $stmt->execute([$nuevo_estado, $comentario, $_SESSION['usuario_id'], $id]);
            
            // Si se aprueba, actualizar también el fichaje si corresponde
            if ($accion == 'aprobar' && $solicitud['fichaje_id']) {
                $stmtUpdate = $pdo->prepare("UPDATE fichajes 
                                             SET hora_entrada = ?, 
                                                 hora_salida = ?, 
                                                 corregido = 1 
                                             WHERE id = ?");
                $stmtUpdate->execute([
                    $solicitud['hora_correcta_entrada'], 
                    $solicitud['hora_correcta_salida'], 
                    $solicitud['fichaje_id']
                ]);
            }
            
            registrarLog($_SESSION['usuario_id'], 'resolver_incidencia', 'solicitudes_correccion', $id, null, [
                'solicitud_id' => $id,
                'estado' => $nuevo_estado,
                'usuario_afectado' => $solicitud['usuario_id']
            ]);
            
            header('Location: incidencias.php?mensaje=incidencia_resuelta');
            exit;
        }
    }
}

$token = generarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolver Incidencia - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/sidebar_admin.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="margin-bottom: 2rem; color: #1a237e;">
            <i class="fas fa-edit"></i> Resolver Solicitud de Corrección
        </h1>

        <div class="card">
            <div class="card-body">
                
                <div class="info-solicitud">
                    <p><strong>Empleado:</strong> <?php echo escape($solicitud['nombre'] . ' ' . $solicitud['apellidos']); ?></p>
                    <p><strong>Fecha Solicitud:</strong> <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?></p>
                    <p><strong>Motivo:</strong> <?php echo escape($solicitud['motivo']); ?></p>
                    
                    <?php if ($solicitud['fichaje_id']): ?>
                    <hr style="margin:1rem 0;">
                    <p><strong>Hora Entrada Original:</strong> <?php echo $solicitud['hora_entrada'] ? date('H:i', strtotime($solicitud['hora_entrada'])) : 'Sin registrar'; ?></p>
                    <p><strong>Hora Salida Original:</strong> <?php echo $solicitud['hora_salida'] ? date('H:i', strtotime($solicitud['hora_salida'])) : 'Sin registrar'; ?></p>
                    <hr style="margin:1rem 0;">
                    <p><strong>Nueva Hora Entrada Solicitada:</strong> <?php echo date('H:i', strtotime($solicitud['nueva_hora_entrada'])); ?></p>
                    <p><strong>Nueva Hora Salida Solicitada:</strong> <?php echo date('H:i', strtotime($solicitud['nueva_hora_salida'])); ?></p>
                    <?php endif; ?>
                </div>

                <hr style="margin:2rem 0;">

                <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo escape($token); ?>">
                    
                    <div class="grupo-formulario">
                        <label>Comentario (opcional):</label>
                        <textarea name="comentario" rows="3" class="form-control" placeholder="Añade un comentario justificando la resolución..."></textarea>
                    </div>

                    <div style="display:flex; gap:1rem; margin-top:2rem;">
                        <button type="submit" name="accion" value="aprobar" class="btn btn-verde" style="flex:1;">
                            <i class="fas fa-check"></i> Aprobar Solicitud
                        </button>
                        <button type="submit" name="accion" value="rechazar" class="btn btn-rojo" style="flex:1;">
                            <i class="fas fa-times"></i> Rechazar Solicitud
                        </button>
                    </div>
                    
                    <a href="incidencias.php" class="btn btn-secundario" style="display:block; text-align:center; margin-top:1rem;">
                        <i class="fas fa-arrow-left"></i> Volver sin modificar
                    </a>

                </form>

            </div>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>