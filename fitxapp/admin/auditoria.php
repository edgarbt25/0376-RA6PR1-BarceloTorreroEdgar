<?php
/**
 * FitxApp - Administrador - Log de Auditoría
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

$pagina = max(1, $_GET['pagina'] ?? 1);
$porPagina = 20;
$offset = ($pagina - 1) * $porPagina;

// Contar total registros
$stmt = $pdo->query("SELECT COUNT(*) FROM logs_auditoria");
$total = $stmt->fetchColumn();
$totalPaginas = ceil($total / $porPagina);

// Obtener registros
$stmt = $pdo->prepare("SELECT l.*, u.nombre, u.apellidos
                       FROM logs_auditoria l
                       LEFT JOIN usuarios u ON l.usuario_id = u.id
                       ORDER BY l.fecha DESC
                       LIMIT ? OFFSET ?");
$stmt->bindValue(1, $porPagina, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// Exportar CSV
if (isset($_GET['exportar'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=auditoria_fitxapp_'.date('Y-m-d').'.csv');
    $salida = fopen('php://output', 'w');
    fprintf($salida, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($salida, ['Fecha', 'Usuario', 'Acción', 'Tabla', 'ID Registro', 'IP']);
    $stmt = $pdo->query("SELECT l.fecha, CONCAT(u.nombre, ' ', u.apellidos), l.accion, l.tabla_afectada, l.id_registro, l.ip_address FROM logs_auditoria l LEFT JOIN usuarios u ON l.usuario_id = u.id ORDER BY l.fecha DESC");
    while ($fila = $stmt->fetch(PDO::FETCH_NUM)) fputcsv($salida, $fila);
    fclose($salida);
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log de Auditoría - FitxApp</title>
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
                <i class="fas fa-history"></i> Log de Auditoría
            </h1>
            <a href="?exportar=1" class="btn btn-primario">
                <i class="fas fa-download"></i> Exportar CSV
            </a>
        </div>

        <div class="card">
            <div class="card-body" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px;">
                <strong><i class="fas fa-info-circle"></i> Cumplimiento Legal:</strong> Todos los registros se conservan durante 4 años mínimo según Estatuto de los Trabajadores Art. 34.9 y RGPD.
            </div>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Tabla Afectada</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($log['fecha'])); ?></td>
                        <td><?php echo $log['nombre'] ? escape($log['nombre'] . ' ' . $log['apellidos']) : 'Sistema'; ?></td>
                        <td><?php echo escape($log['accion']); ?></td>
                        <td><code><?php echo escape($log['tabla_afectada']); ?></code></td>
                        <td><code><?php echo escape($log['ip_address']); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem;">
                <?php for($i=1;$i<=$totalPaginas;$i++): ?>
                <a href="?pagina=<?php echo $i; ?>" class="btn <?php echo $i == $pagina ? 'btn-primario' : 'btn-secundario'; ?>" style="min-width: 40px; text-align: center;"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>