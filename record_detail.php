<?php
/**
 * Detalle y edición de registro de tiempo
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

require_once __DIR__ . '/includes/auth.php';

// Requerir autenticación
requireAuth('login.php');

$user = getCurrentUser();
$pageTitle = 'Detalle de Registro';
$showBackButton = true;

$logId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$logId) {
    header('Location: history.php');
    exit;
}

try {
    $pdo = getDBConnection();

    // Obtener registro y verificar que pertenece al usuario actual
    $stmt = $pdo->prepare("
        SELECT 
            tl.id,
            tl.clock_in,
            tl.clock_out,
            tl.notes,
            tl.total_minutes,
            p.name as project_name
        FROM time_logs tl
        LEFT JOIN projects p ON tl.project_id = p.id
        WHERE tl.id = :id AND tl.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        ':id' => $logId,
        ':user_id' => $user['id']
    ]);
    
    $log = $stmt->fetch();

    if (!$log) {
        header('Location: history.php');
        exit;
    }

    // Procesar guardado de notas
    $success = '';
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Token de seguridad inválido';
        } else {
            $notes = trim($_POST['notes'] ?? '');
            
            $stmt = $pdo->prepare("
                UPDATE time_logs 
                SET notes = :notes 
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([
                ':notes' => $notes,
                ':id' => $logId,
                ':user_id' => $user['id']
            ]);

            $success = '✅ Notas guardadas correctamente';
            
            // Actualizar datos del log
            $log['notes'] = $notes;
        }
    }

} catch (PDOException $e) {
    header('Location: history.php');
    exit;
}

function formatMinutesToHours($minutes) {
    if ($minutes === null || $minutes == 0) return '0h 0m';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h ' . $mins . 'm';
}

include 'includes/header.php';
?>

<div class="container-sm">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📝 Detalle de Registro #<?php echo $log['id']; ?></h3>
        </div>
        <div class="card-body">

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error mb-lg"><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success mb-lg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">📅 Fecha</span>
                    <span class="detail-value"><?php echo date('d/m/Y', strtotime($log['clock_in'])); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">📁 Proyecto</span>
                    <span class="detail-value">
                        <?php echo $log['project_name'] ? htmlspecialchars($log['project_name']) : 'Sin proyecto'; ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">✅ Hora Entrada</span>
                    <span class="detail-value"><?php echo date('H:i', strtotime($log['clock_in'])); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">🔴 Hora Salida</span>
                    <span class="detail-value">
                        <?php echo $log['clock_out'] ? date('H:i', strtotime($log['clock_out'])) : 'En progreso'; ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">⏱️ Duración Total</span>
                    <span class="detail-value big-time-display" style="font-size: 1.5rem !important;">
                        <?php 
                        if ($log['clock_out']) {
                            echo formatMinutesToHours($log['total_minutes']);
                        } else {
                            $mins = time() - strtotime($log['clock_in']);
                            echo formatMinutesToHours($mins / 60);
                        }
                        ?>
                    </span>
                </div>
            </div>

            <hr style="margin: 2rem 0; border: 1px solid var(--gray-200);">

            <form method="POST" action="record_detail.php?id=<?php echo $logId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label class="form-label">📝 Notas del registro:</label>
                    <textarea 
                        name="notes" 
                        class="form-control" 
                        rows="5" 
                        placeholder="Añade notas o comentarios sobre esta jornada..."
                    ><?php echo htmlspecialchars($log['notes'] ?? ''); ?></textarea>
                </div>

                <div class="d-flex gap-md justify-end">
                    <a href="history.php" class="btn btn-secondary">← Volver al Historial</a>
                    <button type="submit" class="btn btn-primary">💾 Guardar Notas</button>
                </div>
            </form>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>