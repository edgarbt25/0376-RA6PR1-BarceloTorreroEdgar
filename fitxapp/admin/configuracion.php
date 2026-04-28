<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (clave VARCHAR(100) PRIMARY KEY, valor TEXT NOT NULL) COLLATE='utf8mb4_general_ci'");
} catch (Exception $e) {}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $clave => $valor) {
        $stmt = $pdo->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
        $stmt->execute([$clave, $valor, $valor]);
    }
    $mensaje = 'OK';
}

$config = [];
try {
    $stmt = $pdo->query("SELECT clave, valor FROM configuracion");
    if ($stmt) {
        $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
} catch (PDOException $e) {}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Configuración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/sidebar_admin.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="color: #1a237e;"><i class="fas fa-cog"></i> Configuración</h1>
        
        <?php if ($mensaje): ?>
        <div style="background: #d4edda; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; color: #155724;">
            ✅ Configuración guardada correctamente
        </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
            <div class="card-body">
                <div class="form-grupo">
                    <label>Nombre Empresa</label>
                    <input type="text" name="empresa_nombre" class="form-control" value="<?php echo htmlspecialchars($config['empresa_nombre'] ?? ''); ?>">
                </div>
                <div class="form-grupo">
                    <label>Horas jornada</label>
                    <input type="number" name="horas_jornada" class="form-control" value="<?php echo htmlspecialchars($config['horas_jornada'] ?? '8'); ?>">
                </div>
                <div class="form-grupo">
                    <label>Tolerancia retraso (min)</label>
                    <input type="number" name="tolerancia_retraso" class="form-control" value="<?php echo htmlspecialchars($config['tolerancia_retraso'] ?? '5'); ?>">
                </div>
                <button type="submit" class="btn btn-exito">Guardar</button>
            </div>
            </form>
        </div>

    </div>
</div>

</body>
</html>