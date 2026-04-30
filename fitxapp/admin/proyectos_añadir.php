<?php
/**
 * FitxApp - Administrador - Añadir Nuevo Proyecto
 */

// Habilitar visualización de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $mensaje = 'Error de seguridad. Inténtelo de nuevo.';
    } else {
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $cliente = trim($_POST['cliente']);
        $horas_presupuestadas = (float)$_POST['horas_presupuestadas'];
        $fecha_inicio = trim($_POST['fecha_inicio'] ?? date('Y-m-d'));
        $fecha_fin = trim($_POST['fecha_fin'] ?? '');
        $num_empleados = (int)$_POST['num_empleados'] ?? 1;
        $color = trim($_POST['color']);
        
        $stmt = $pdo->prepare("INSERT INTO proyectos (nombre, descripcion, cliente, horas_presupuestadas, estado, color, fecha_creacion)
                               VALUES (?, ?, ?, ?, 'activo', ?, NOW())");
        $stmt->execute([$nombre, $descripcion, $cliente, $horas_presupuestadas, $color]);
        
        registrarLog($_SESSION['usuario_id'], 'crear_proyecto', 'proyectos', $pdo->lastInsertId());
        header('Location: proyectos.php?mensaje=proyecto_creado');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Proyecto - FitxApp</title>
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
                <i class="fas fa-folder-plus"></i> Añadir Nuevo Proyecto
            </h1>
            <a href="proyectos.php" class="btn btn-secundario">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #43a047, #2e7d32); color: white;">
                <h3 style="color: white;"><i class="fas fa-folder-plus"></i> Datos del Nuevo Proyecto</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo escape(generarTokenCSRF()); ?>">

                    <!-- Pestañas -->
                    <div class="tabs" style="display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 1rem;">
                        <button type="button" class="tab-btn activo" style="border: none; background: transparent; padding: 0.75rem 1.25rem; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                            <i class="fas fa-info-circle"></i> Información General
                        </button>
                        <button type="button" class="tab-btn" style="border: none; background: transparent; padding: 0.75rem 1.25rem; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                            <i class="fas fa-chart-line"></i> Presupuesto
                        </button>
                        <button type="button" class="tab-btn" style="border: none; background: transparent; padding: 0.75rem 1.25rem; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                            <i class="fas fa-palette"></i> Apariencia
                        </button>
                    </div>

                    <div class="tab-content activo">
                        <div class="form-grupo">
                            <label><i class="fas fa-signature"></i> Nombre del Proyecto</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Nombre oficial del proyecto" required>
                        </div>
                        <div class="form-grupo">
                            <label><i class="fas fa-align-left"></i> Descripción Detallada</label>
                            <textarea name="descripcion" class="form-control" rows="4" placeholder="Describe el objetivo y alcance del proyecto..." required></textarea>
                        </div>
                        <div class="form-grupo">
                            <label><i class="fas fa-building"></i> Cliente</label>
                            <input type="text" name="cliente" class="form-control" placeholder="Nombre de la empresa cliente" required>
                        </div>
                    </div>

                    <div class="tab-content" style="display: none;">
                        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-grupo">
                                <label><i class="fas fa-clock"></i> Horas Presupuestadas</label>
                                <input type="number" name="horas_presupuestadas" class="form-control" min="0" step="0.5" placeholder="160" required>
                                <small style="color: #757575; margin-top: 0.25rem; display: block;">
                                    Horas totales disponibles para este proyecto
                                </small>
                            </div>
                            <div class="form-grupo">
                                <label><i class="fas fa-calendar-alt"></i> Fecha de Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-grupo">
                                <label><i class="fas fa-calendar-check"></i> Fecha de Fin Estimada</label>
                                <input type="date" name="fecha_fin" class="form-control">
                            </div>
                            <div class="form-grupo">
                                <label><i class="fas fa-users"></i> Número de empleados asignados</label>
                                <input type="number" name="num_empleados" class="form-control" min="1" placeholder="5">
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" style="display: none;">
                        <div class="form-grupo">
                            <label><i class="fas fa-palette"></i> Color del Proyecto</label>
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <input type="color" name="color" id="color_proyecto" class="form-control" value="#1976d2" style="width: 80px; height: 80px; padding: 0.5rem;" required>
                                <div>
                                    <p style="font-weight: 500;">Color seleccionado:</p>
                                    <div id="color_preview" style="width: 100%; height: 40px; background: #1976d2; border-radius: 8px; margin-top: 0.5rem;"></div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                <button type="button" onclick="document.getElementById('color_proyecto').value='#1976d2'" class="btn btn-secundario" style="background: #1976d2; color: white; padding: 0.5rem;">Azul</button>
                                <button type="button" onclick="document.getElementById('color_proyecto').value='#43a047'" class="btn btn-secundario" style="background: #43a047; color: white; padding: 0.5rem;">Verde</button>
                                <button type="button" onclick="document.getElementById('color_proyecto').value='#f57c00'" class="btn btn-secundario" style="background: #f57c00; color: white; padding: 0.5rem;">Naranja</button>
                                <button type="button" onclick="document.getElementById('color_proyecto').value='#7b1fa2'" class="btn btn-secundario" style="background: #7b1fa2; color: white; padding: 0.5rem;">Morado</button>
                                <button type="button" onclick="document.getElementById('color_proyecto').value='#ef5350'" class="btn btn-secundario" style="background: #ef5350; color: white; padding: 0.5rem;">Rojo</button>
                            </div>
                        </div>
                    </div>

                    <hr style="margin: 2rem 0;">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="proyectos.php" class="btn btn-secundario">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-exito btn-grande">
                            <i class="fas fa-check-circle"></i> Crear Nuevo Proyecto
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Sistema de pestañas
            document.querySelectorAll('.tab-btn').forEach((btn, index) => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.tab-btn').forEach(b => {
                        b.classList.remove('activo');
                        b.style.background = 'transparent';
                        b.style.color = '#757575';
                    });
                    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                    
                    btn.classList.add('activo');
                    btn.style.background = 'linear-gradient(135deg, #43a047, #2e7d32)';
                    btn.style.color = 'white';
                    document.querySelectorAll('.tab-content')[index].style.display = 'block';
                });
            });

            // Inicializar pestaña activa
            document.querySelector('.tab-btn.activo').style.background = 'linear-gradient(135deg, #43a047, #2e7d32)';
            document.querySelector('.tab-btn.activo').style.color = 'white';

            // Previsualización de color
            document.getElementById('color_proyecto').addEventListener('input', function() {
                document.getElementById('color_preview').style.background = this.value;
            });
        </script>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>