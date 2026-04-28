<?php
/**
 * FitxApp - Administrador - Añadir Nuevo Empleado
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirAdmin();

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $departamento_id = $_POST['departamento_id'];
    $cargo = $_POST['cargo'];
    $rol = $_POST['rol'];
    
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellidos, email, password, departamento_id, cargo, rol, activo, fecha_creacion)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
    $stmt->execute([$nombre, $apellidos, $email, $password, $departamento_id, $cargo, $rol]);
    
    registrarLog($_SESSION['usuario_id'], 'crear_empleado', 'usuarios', $pdo->lastInsertId());
    header('Location: empleados.php?mensaje=empleado_creado');
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
    <title>Añadir Empleado - FitxApp</title>
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
                <i class="fas fa-user-plus"></i> Añadir Nuevo Empleado
            </h1>
            <a href="empleados.php" class="btn btn-secundario">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #1a237e, #1976d2); color: white;">
                <h3 style="color: white;"><i class="fas fa-user-plus"></i> Datos del Nuevo Empleado</h3>
            </div>
            <div class="card-body">
                <form method="POST">

                    <!-- Pestañas -->
                    <div class="tabs" style="display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 1rem;">
                        <button type="button" class="tab-btn activo" style="border: none; background: transparent; padding: 0.75rem 1.25rem; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                            <i class="fas fa-user"></i> Datos Personales
                        </button>
                        <button type="button" class="tab-btn" style="border: none; background: transparent; padding: 0.75rem 1.25rem; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                            <i class="fas fa-briefcase"></i> Datos Laborales
                        </button>
                        <button type="button" class="tab-btn" style="border: none; background: transparent; padding: 0.75rem 1.25rem; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                            <i class="fas fa-key"></i> Seguridad
                        </button>
                    </div>

                    <div class="tab-content activo">
                        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-grupo">
                                <label><i class="fas fa-signature"></i> Nombre</label>
                                <input type="text" name="nombre" class="form-control" placeholder="Nombre del empleado" required>
                            </div>
                            <div class="form-grupo">
                                <label><i class="fas fa-signature"></i> Apellidos</label>
                                <input type="text" name="apellidos" class="form-control" placeholder="Apellidos" required>
                            </div>
                            <div class="form-grupo" style="grid-column: 1 / -1;">
                                <label><i class="fas fa-envelope"></i> Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" placeholder="correo@empresa.com" required>
                            </div>
                            <div class="form-grupo">
                                <label><i class="fas fa-phone"></i> Teléfono</label>
                                <input type="tel" name="telefono" class="form-control" placeholder="+34 600 000 000">
                            </div>
                            <div class="form-grupo">
                                <label><i class="fas fa-id-card"></i> DNI</label>
                                <input type="text" name="dni" class="form-control" placeholder="00000000X">
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" style="display: none;">
                        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-grupo">
                                <label><i class="fas fa-building"></i> Departamento</label>
                                <select name="departamento_id" class="form-control" required>
                                    <option value="">Seleccionar departamento</option>
                                    <?php foreach ($departamentos as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo escape($d['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-grupo">
                                <label><i class="fas fa-user-tie"></i> Cargo</label>
                                <input type="text" name="cargo" class="form-control" placeholder="Operario, Administrativo..." required>
                            </div>
                            <div class="form-grupo">
                                <label><i class="fas fa-user-shield"></i> Rol en el sistema</label>
                                <select name="rol" class="form-control" required>
                                    <option value="empleado">✅ Empleado</option>
                                    <option value="supervisor">👮 Supervisor</option>
                                    <option value="admin">⚙️ Administrador</option>
                                </select>
                            </div>
                            <div class="form-grupo">
                                <label><i class="fas fa-calendar-plus"></i> Fecha de Alta</label>
                                <input type="date" name="fecha_alta" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" style="display: none;">
                        <div class="form-grupo">
                            <label><i class="fas fa-lock"></i> Contraseña temporal</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text" name="password" id="password_input" class="form-control" placeholder="Contraseña segura" required>
                                <button type="button" onclick="generarPassword()" class="btn btn-secundario" title="Generar contraseña aleatoria">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <div id="fuerza_password" style="height: 4px; margin-top: 0.5rem; border-radius: 2px; background: #e0e0e0;"></div>
                        </div>
                    </div>

                    <hr style="margin: 2rem 0;">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="empleados.php" class="btn btn-secundario">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-exito btn-grande">
                            <i class="fas fa-check-circle"></i> Crear Nuevo Empleado
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
                    btn.style.background = 'linear-gradient(135deg, #1976d2, #1a237e)';
                    btn.style.color = 'white';
                    document.querySelectorAll('.tab-content')[index].style.display = 'block';
                });
            });

            // Inicializar pestaña activa
            document.querySelector('.tab-btn.activo').style.background = 'linear-gradient(135deg, #1976d2, #1a237e)';
            document.querySelector('.tab-btn.activo').style.color = 'white';

            // Generar contraseña
            function generarPassword() {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
                let pass = '';
                for(let i=0; i<10; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
                document.getElementById('password_input').value = pass;
                document.getElementById('fuerza_password').style.background = '#43a047';
            }
        </script>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>