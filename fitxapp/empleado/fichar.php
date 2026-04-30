<?php
/**
 * FitxApp - Página de Fichaje de Empleado
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

requerirEmpleado();

$usuario_id = $_SESSION['usuario_id'];
$estado = obtenerEstadoEmpleado($usuario_id);
$fichajeAbierto = verificarFichajeAbierto($usuario_id);

$mensaje = '';
$error = '';

// Procesar acciones de fichaje
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Error de seguridad';
    } else {
        $accion = $_POST['accion'] ?? '';
        $ip = obtenerIPCliente();
        
        try {
            $pdo->beginTransaction();
            
            if ($accion == 'entrada' && !$fichajeAbierto) {
                $proyecto_id = $_POST['proyecto_id'];
                $actividad_id = $_POST['actividad_id'];
                $descripcion = trim($_POST['descripcion'] ?? '');
                $latitud = $_POST['latitud'] ?? null;
                $longitud = $_POST['longitud'] ?? null;
                
        $stmt = $pdo->prepare("INSERT INTO fichajes 
            (usuario_id, proyecto_id, tipo_entrada, hora_entrada, descripcion, ip_address, latitud, longitud)
            VALUES (?, ?, 'normal', NOW(), ?, ?, ?, ?)");
        
        $stmt->execute([$usuario_id, $proyecto_id, $descripcion, $ip, $latitud, $longitud]);
                
                registrarLog($usuario_id, 'fichaje_entrada', 'fichajes', $pdo->lastInsertId());
                enviarNotificacion($usuario_id, 'exito', 'Has iniciado tu jornada correctamente');
                
                $mensaje = '¡Entrada registrada correctamente! Buen día de trabajo.';
                
                $pdo->commit();
                
                header('Location: fichar.php?mensaje=entrada_ok');
                exit;
                
            } elseif ($accion == 'salida' && $fichajeAbierto) {
                $horaEntrada = $fichajeAbierto['hora_entrada'];
                $horaSalida = date('Y-m-d H:i:s');
                
                // Obtener pausas
                $stmt = $pdo->prepare("SELECT * FROM pausas WHERE fichaje_id = ?");
                $stmt->execute([$fichajeAbierto['id']]);
                $pausas = $stmt->fetchAll();
                
                $horasTrabajadas = calcularHorasTrabajadas($horaEntrada, $horaSalida, $pausas);
                
                $stmt = $pdo->prepare("UPDATE fichajes SET hora_salida = NOW(), horas_trabajadas = ? WHERE id = ?");
                $stmt->execute([$horasTrabajadas, $fichajeAbierto['id']]);
                
                // Finalizar pausas abiertas
                $stmt = $pdo->prepare("UPDATE pausas SET hora_fin = NOW(), duracion_minutos = TIMESTAMPDIFF(MINUTE, hora_inicio, NOW()) 
                                       WHERE fichaje_id = ? AND hora_fin IS NULL");
                $stmt->execute([$fichajeAbierto['id']]);
                
                registrarLog($usuario_id, 'fichaje_salida', 'fichajes', $fichajeAbierto['id']);
                enviarNotificacion($usuario_id, 'exito', 'Has finalizado tu jornada. ¡Hasta mañana!');
                
                $pdo->commit();
                
                header('Location: fichar.php?mensaje=salida_ok');
                exit;
                
            } elseif ($accion == 'pausa_iniciar' && $estado['estado'] == 'fichado') {
                $tipoPausa = $_POST['tipo_pausa'];
                
                $stmt = $pdo->prepare("INSERT INTO pausas (fichaje_id, tipo_pausa, hora_inicio) VALUES (?, ?, NOW())");
                $stmt->execute([$fichajeAbierto['id'], $tipoPausa]);
                
                registrarLog($usuario_id, 'pausa_iniciar', 'pausas', $pdo->lastInsertId());
                
                $pdo->commit();
                
                header('Location: fichar.php?mensaje=pausa_ok');
                exit;
                
            } elseif ($accion == 'pausa_finalizar' && $estado['estado'] == 'pausa') {
                $stmt = $pdo->prepare("UPDATE pausas SET hora_fin = NOW(), duracion_minutos = TIMESTAMPDIFF(MINUTE, hora_inicio, NOW()) 
                                       WHERE fichaje_id = ? AND hora_fin IS NULL LIMIT 1");
                $stmt->execute([$fichajeAbierto['id']]);
                
                registrarLog($usuario_id, 'pausa_finalizar', 'pausas');
                
                $pdo->commit();
                
                header('Location: fichar.php?mensaje=pausa_fin_ok');
                exit;
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error al procesar el fichaje: ' . $e->getMessage();
        }
    }
}

$token = generarTokenCSRF();

// Obtener proyectos asignados
$stmt = $pdo->prepare("SELECT p.id, p.nombre, p.color FROM proyectos p WHERE p.estado = 'activo' ORDER BY p.nombre ASC");
$stmt->execute();
$proyectos = $stmt->fetchAll();

// Obtener tipos de actividad
$stmt = $pdo->query("SELECT * FROM tipos_actividad ORDER BY nombre");
$actividades = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichar Entrada/Salida - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .reloj-grande {
            font-size: 5rem;
            font-family: 'Courier New', monospace;
            font-weight: 800;
            color: #1a237e;
            text-align: center;
            margin: 1rem 0;
            letter-spacing: 4px;
        }
        
        .estado-fichaje {
            text-align: center;
            padding: 3rem;
            border-radius: 20px;
            margin-bottom: 2rem;
        }
        
        .estado-fichaje.fichado {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border: 2px solid #43a047;
        }
        
        .estado-fichaje.pausa {
            background: linear-gradient(135deg, #fff8e1, #ffe0b2);
            border: 2px solid #f57c00;
        }
        
        .estado-fichaje.fuera {
            background: linear-gradient(135deg, #fafafa, #f5f5f5);
            border: 2px solid #e0e0e0;
        }
        
        .btn-fichaje {
            width: 100%;
            padding: 1.5rem;
            font-size: 1.5rem;
            border-radius: 16px;
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>

<?php include '../includes/sidebar_empleado.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="margin-bottom: 2rem; color: #1a237e;">
            <i class="fas fa-clock"></i> Fichar Entrada/Salida
        </h1>

        <?php if (isset($_GET['mensaje'])): ?>
        <div class="toast exito" id="toastMensaje">
            <i class="fas fa-check-circle"></i> 
            <?php 
                $mensajes = [
                    'entrada_ok' => 'Entrada registrada correctamente',
                    'salida_ok' => 'Salida registrada correctamente',
                    'pausa_ok' => 'Pausa iniciada',
                    'pausa_fin_ok' => 'Pausa finalizada'
                ];
                echo $mensajes[$_GET['mensaje']] ?? '';
            ?>
        </div>
        <script>setTimeout(() => document.getElementById('toastMensaje').remove(), 4000);</script>
        <?php endif; ?>

        <div class="reloj-grande" id="relojPrincipal"></div>

        <div class="card">
            <div class="card-body">
                <div class="estado-fichaje <?php echo $estado['estado']; ?>">
                    <i class="fas <?php echo $estado['icono']; ?>" style="font-size: 5rem; color: <?php echo $estado['color']; ?>; margin-bottom: 1rem;"></i>
                    <h2 style="color: <?php echo $estado['color']; ?>; font-size: 2rem; margin-bottom: 0.5rem;">
                        <?php echo strtoupper($estado['texto']); ?>
                    </h2>
                    
                    <?php if ($fichajeAbierto): ?>
                    <div id="contador-jornada" style="font-size: 3rem; font-family: monospace; font-weight: 700; margin: 1rem 0;"></div>
                    <?php endif; ?>
                    
                    <p style="color: #757575;">
                        IP detectada: <?php echo obtenerIPCliente(); ?>
                    </p>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo escape($token); ?>">
                    <input type="hidden" name="latitud" id="latitud">
                    <input type="hidden" name="longitud" id="longitud">

                    <?php if (!$fichajeAbierto): ?>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label class="form-label">Selecciona Proyecto</label>
                        <select name="proyecto_id" class="form-control" required style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 1rem;">
                            <option value="">-- Seleccionar proyecto --</option>
                            <?php foreach ($proyectos as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo escape($p['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label class="form-label">Tipo de Actividad</label>
                        <select name="actividad_id" class="form-control" required style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 1rem;">
                            <option value="">-- Seleccionar actividad --</option>
                            <?php foreach ($actividades as $a): ?>
                            <option value="<?php echo $a['id']; ?>"><?php echo escape($a['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label class="form-label">Descripción (opcional)</label>
                        <textarea name="descripcion" class="form-control" rows="2" placeholder="¿Qué vas a hacer?" style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 1rem;"></textarea>
                    </div>

                    <button type="button" id="btnGeo" class="btn btn-primario" style="width: 100%; margin-bottom: 1rem;">
                        <i class="fas fa-map-marker-alt"></i> Obtener Ubicación
                    </button>

                    <button type="submit" name="accion" value="entrada" class="btn btn-exito btn-fichaje">
                        <i class="fas fa-play"></i> INICIAR JORNADA
                    </button>

                    <?php elseif ($estado['estado'] == 'fichado'): ?>
                    
                    <?php
                    // Obtener datos del fichaje abierto
                    $stmt = $pdo->prepare("SELECT p.nombre as proyecto, f.hora_entrada 
                                           FROM fichajes f 
                                           LEFT JOIN proyectos p ON f.proyecto_id = p.id 
                                           WHERE f.id = ?");
                    $stmt->execute([$fichajeAbierto['id']]);
                    $datosFichaje = $stmt->fetch();
                    ?>

                    <div style="background: #e8f5e9; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; border-left: 5px solid #43a047;">
                        <h3 style="color: #2e7d32; font-size: 1.8rem; margin-bottom: 1rem;"><i class="fas fa-check-circle"></i> ✅ ESTÁS FICHADO</h3>
                        <p style="font-size: 1.1rem; margin: 0.5rem 0;"><strong>Proyecto:</strong> <?php echo escape($datosFichaje['proyecto']); ?></p>
                        <p style="font-size: 1.1rem; margin: 0.5rem 0;"><strong>Hora de entrada:</strong> <?php echo date('H:i:s', strtotime($datosFichaje['hora_entrada'])); ?></p>
                    </div>

                    <button type="submit" name="accion" value="pausa_iniciar" class="btn btn-advertencia btn-fichaje" style="background: #f57c00; margin-bottom: 0.7rem;">
                        <i class="fas fa-mug-hot"></i> ☕ INICIAR PAUSA COMIDA
                    </button>

                    <button type="submit" name="accion" value="pausa_iniciar" class="btn btn-secundario btn-fichaje" style="margin-bottom: 1rem;">
                        <i class="fas fa-pause"></i> ⏸ INICIAR DESCANSO
                    </button>

                    <button type="submit" name="accion" value="salida" class="btn btn-peligro btn-fichaje" onclick="return confirm('¿Estás seguro que quieres finalizar tu jornada?')" style="margin-top: 1rem;">
                        <i class="fas fa-stop"></i> 🔴 FINALIZAR JORNADA
                    </button>

                    <?php elseif ($estado['estado'] == 'pausa'): ?>

                    <button type="submit" name="accion" value="pausa_finalizar" class="btn btn-exito btn-fichaje">
                        <i class="fas fa-play"></i> REANUDAR JORNADA
                    </button>

                    <?php endif; ?>

                </form>
            </div>
        </div>

    </div>
</div>

<script>
// Reloj principal
function actualizarRelojPrincipal() {
    const ahora = new Date();
    document.getElementById('relojPrincipal').textContent = 
        String(ahora.getHours()).padStart(2, '0') + ':' +
        String(ahora.getMinutes()).padStart(2, '0') + ':' +
        String(ahora.getSeconds()).padStart(2, '0');
}

setInterval(actualizarRelojPrincipal, 1000);
actualizarRelojPrincipal();

<?php if ($fichajeAbierto): ?>
// Contador de jornada
function actualizarContadorJornada() {
    const inicio = new Date('<?php echo $fichajeAbierto['hora_entrada']; ?>');
    const ahora = new Date();
    const diff = ahora - inicio;
    
    const horas = Math.floor(diff / 3600000);
    const minutos = Math.floor((diff % 3600000) / 60000);
    const segundos = Math.floor((diff % 60000) / 1000);
    
    document.getElementById('contador-jornada').textContent = 
        String(horas).padStart(2, '0') + ':' + 
        String(minutos).padStart(2, '0') + ':' + 
        String(segundos).padStart(2, '0');
}

setInterval(actualizarContadorJornada, 1000);
actualizarContadorJornada();
<?php endif; ?>

// Geolocalización
document.getElementById('btnGeo')?.addEventListener('click', function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            document.getElementById('latitud').value = pos.coords.latitude;
            document.getElementById('longitud').value = pos.coords.longitude;
            
            document.getElementById('btnGeo').innerHTML = '<i class="fas fa-check-circle"></i> Ubicación obtenida ✓';
            document.getElementById('btnGeo').classList.remove('btn-primario');
            document.getElementById('btnGeo').classList.add('btn-exito');
        });
    }
});
</script>

<script src="../assets/js/main.js"></script>
</body>
</html>