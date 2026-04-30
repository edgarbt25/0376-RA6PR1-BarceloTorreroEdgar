<?php
/**
 * FitxApp - Página de Login y Bienvenida
 */

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/funciones.php';

// Si ya está autenticado redirigir al panel correspondiente
if (estaAutenticado()) {
    redirigirPorRol();
}

$error = '';
$exito = '';

// Verificar mensajes de notificación
if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'sesion_cerrada') {
    $exito = 'Sesión cerrada correctamente. Hasta pronto!';
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Error de seguridad. Inténtelo de nuevo.';
    } 
    // Procesar Registro
    elseif (isset($_POST['accion']) && $_POST['accion'] === 'registro') {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email = trim($_POST['email_registro'] ?? '');
        $password = $_POST['password_registro'] ?? '';
        
        if (empty($nombre) || empty($apellidos) || empty($email) || empty($password)) {
            $error = 'Todos los campos son obligatorios';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Introduzca un email válido';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres';
        } else {
            // Verificar si el email ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = 'Este correo electrónico ya está registrado';
            } else {
                // Crear usuario nuevo (rol empleado, desactivado por defecto)
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellidos, email, password, rol, activo, fecha_creacion) 
                                       VALUES (?, ?, ?, ?, 'empleado', 0, NOW())");
                $stmt->execute([
                    $nombre,
                    $apellidos,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT)
                ]);
                
                registrarLog(null, 'registro_usuario', 'usuarios', $pdo->lastInsertId(), null, [
                    'email' => $email,
                    'nombre' => $nombre
                ]);
                
                $exito = 'Cuenta creada correctamente! Un administrador deberá activar tu cuenta antes de que puedas acceder.';
            }
        }
    }
    // Procesar Login
    else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Introduzca un email válido';
        } else {
            $stmt = $pdo->prepare("SELECT id, nombre, apellidos, password, rol, activo FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($password, $usuario['password'])) {
                if (!$usuario['activo']) {
                    $error = 'Esta cuenta se encuentra desactivada';
                } else {
                    // Iniciar sesión
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['nombre'] = $usuario['nombre'];
                    $_SESSION['apellidos'] = $usuario['apellidos'];
                    $_SESSION['email'] = $email;
                    $_SESSION['rol'] = $usuario['rol'];
                    
                    registrarLog($usuario['id'], 'login', 'usuarios', $usuario['id']);
                    
                    // Cookie recordarme
                    if (isset($_POST['recordarme']) && $_POST['recordarme'] == 1) {
                        setcookie('fitxapp_email', $email, time() + (86400 * 30), "/", "", isset($_SERVER['HTTPS']), true);
                    }
                    
                    redirigirPorRol();
                }
            } else {
                $error = 'Email o contraseña incorrectos';
                registrarLog(null, 'login_fallido', 'usuarios', null, null, ['email' => $email]);
            }
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
    <title>FitxApp - Sistema Control Horario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a237e 0%, #1976d2 50%, #0d47a1 100%);
            background-attachment: fixed;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Partículas animadas de fondo */
        .particulas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        
        .particula {
            position: absolute;
            width: 10px;
            height: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: flotar 15s infinite;
        }
        
        @keyframes flotar {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }
        
        .contenedor-principal {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .panel-login {
            width: 100%;
            max-width: 1100px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            animation: aparecer 0.8s ease-out;
        }
        
        @keyframes aparecer {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .panel-izquierdo {
            padding: 3rem;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo i {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .logo h1 {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -1px;
        }
        
        .empresa {
            text-align: center;
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }
        
        .tagline {
            text-align: center;
            font-size: 1rem;
            opacity: 0.75;
            margin-bottom: 2rem;
        }
        
        .caracteristicas {
            list-style: none;
            margin-top: 1rem;
        }
        
        .caracteristicas li {
            padding: 0.75rem 0;
            font-size: 0.95rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .caracteristicas li i {
            color: #43a047;
        }
        
        .panel-derecho {
            background: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .panel-derecho h2 {
            color: #1a237e;
            font-size: 1.75rem;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 700;
        }
        
        .grupo-formulario {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .grupo-formulario input {
            width: 100%;
            padding: 1.25rem 1rem 0.5rem 3rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .grupo-formulario input:focus {
            outline: none;
            border-color: #1976d2;
            background: white;
            box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
        }
        
        .grupo-formulario label {
            position: absolute;
            left: 3rem;
            top: 1rem;
            font-size: 1rem;
            color: #9e9e9e;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .grupo-formulario input:focus + label,
        .grupo-formulario input:not(:placeholder-shown) + label {
            top: 0.35rem;
            font-size: 0.75rem;
            color: #1976d2;
            font-weight: 600;
        }
        
        .grupo-formulario i {
            position: absolute;
            left: 1rem;
            top: 1rem;
            color: #9e9e9e;
            transition: all 0.3s ease;
        }
        
        .grupo-formulario input:focus ~ i {
            color: #1976d2;
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #1976d2, #1a237e);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(25, 118, 210, 0.3);
        }
        
        .credenciales-demo {
            margin-top: 2rem;
            padding: 1.25rem;
            background: #f5f7fa;
            border-radius: 12px;
            border-left: 4px solid #1976d2;
        }
        
        .credenciales-demo h4 {
            color: #1a237e;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }
        
        .credenciales-demo p {
            font-size: 0.85rem;
            color: #616161;
            margin: 0.35rem 0;
            font-family: monospace;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #c62828;
            font-size: 0.9rem;
        }
        
        .exito {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #2e7d32;
            font-size: 0.9rem;
        }
        
        .pestanas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .pestana {
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            background: #f5f5f5;
            color: #757575;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .pestana.activa {
            background: linear-gradient(135deg, #1976d2, #1a237e);
            color: white;
        }
        
        .pestana:hover {
            transform: translateY(-1px);
        }
        
        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 1rem;
            color: rgba(255,255,255,0.7);
            font-size: 0.8rem;
            z-index: 10;
        }
        
        @media (max-width: 900px) {
            .panel-login {
                grid-template-columns: 1fr;
            }
            .panel-izquierdo {
                padding: 2rem 2rem 0;
            }
            .caracteristicas {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="particulas">
        <?php for($i=0;$i<20;$i++): ?>
        <div class="particula" style="left: <?php echo rand(0,100); ?>%; animation-delay: <?php echo rand(0,15); ?>s; width: <?php echo rand(5,25); ?>px; height: <?php echo rand(5,25); ?>px;"></div>
        <?php endfor; ?>
    </div>

    <div class="contenedor-principal">
        <div class="panel-login">
            <div class="panel-izquierdo">
                <div class="logo">
                    <i class="fas fa-clock"></i>
                    <h1>FitxApp</h1>
                </div>
                <div class="empresa">Vinewood Vice Corp.</div>
                <div class="tagline">Sistema Profesional de Control Horario</div>
                
                <p style="text-align:center; opacity:0.85; line-height:1.6;">
                    Gestión completa del tiempo laboral de tu equipo.<br>
                    Cumple con el Estatuto de los Trabajadores y el RGPD.
                </p>
                
                <ul class="caracteristicas">
                    <li><i class="fas fa-check-circle"></i> Registro automático de jornada</li>
                    <li><i class="fas fa-check-circle"></i> Cumplimiento legal garantizado</li>
                    <li><i class="fas fa-check-circle"></i> Reportes en tiempo real</li>
                    <li><i class="fas fa-check-circle"></i> Protección de datos RGPD</li>
                </ul>
            </div>
            
            <div class="panel-derecho">
                <h2>Acceso al Sistema</h2>
                
                <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($exito): ?>
                <div class="exito">
                    <i class="fas fa-check-circle"></i> <?php echo $exito; ?>
                </div>
                <?php endif; ?>
                
                <!-- Pestañas de selección -->
                <div class="pestanas">
                    <button class="pestana activa" id="pestana-login" onclick="cambiarPestana('login')">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>
                    <button class="pestana" id="pestana-register" onclick="cambiarPestana('register')">
                        <i class="fas fa-user-plus"></i> Registrarse
                    </button>
                </div>
                
                <!-- Formulario Login -->
                <div id="form-login">
                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo escape($token); ?>">
                        
                        <div class="grupo-formulario">
                            <input type="email" name="email" id="email" placeholder=" " value="<?php echo escape($_COOKIE['fitxapp_email'] ?? ''); ?>" required>
                            <label for="email">Correo electrónico</label>
                            <i class="fas fa-envelope"></i>
                        </div>
                        
                        <div class="grupo-formulario">
                            <input type="password" name="password" id="password" placeholder=" " required>
                            <label for="password">Contraseña</label>
                            <i class="fas fa-lock"></i>
                        </div>
                        
                        <div style="margin: 1rem 0;">
                            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                                <input type="checkbox" name="recordarme" value="1">
                                <span style="color:#616161; font-size:0.9rem;">Recordarme 30 días</span>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-login">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </button>
                    </form>
                </div>
                
                <!-- Formulario Registro -->
                <div id="form-register" style="display: none;">
                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="accion" value="registro">
                        <input type="hidden" name="csrf_token" value="<?php echo escape($token); ?>">
                        
                        <div class="grupo-formulario">
                            <input type="text" name="nombre" id="nombre" placeholder=" " required>
                            <label for="nombre">Nombre</label>
                            <i class="fas fa-user"></i>
                        </div>
                        
                        <div class="grupo-formulario">
                            <input type="text" name="apellidos" id="apellidos" placeholder=" " required>
                            <label for="apellidos">Apellidos</label>
                            <i class="fas fa-user"></i>
                        </div>
                        
                        <div class="grupo-formulario">
                            <input type="email" name="email_registro" id="email_registro" placeholder=" " required>
                            <label for="email_registro">Correo electrónico</label>
                            <i class="fas fa-envelope"></i>
                        </div>
                        
                        <div class="grupo-formulario">
                            <input type="password" name="password_registro" id="password_registro" placeholder=" " required>
                            <label for="password_registro">Contraseña</label>
                            <i class="fas fa-lock"></i>
                        </div>
                        
                        <button type="submit" class="btn-login">
                            <i class="fas fa-user-plus"></i> Crear Cuenta
                        </button>
                    </form>
                </div>
                
                <div class="credenciales-demo">
                    <h4><i class="fas fa-info-circle"></i> Credenciales de demostración:</h4>
                    <p>✅ Administrador: admin@fitxapp.com / admin123</p>
                    <p>👤 Empleado: joan@fitxapp.com / emp123</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function cambiarPestana(tipo) {
            if(tipo === 'login') {
                document.getElementById('form-login').style.display = 'block';
                document.getElementById('form-register').style.display = 'none';
                document.getElementById('pestana-login').classList.add('activa');
                document.getElementById('pestana-register').classList.remove('activa');
            } else {
                document.getElementById('form-login').style.display = 'none';
                document.getElementById('form-register').style.display = 'block';
                document.getElementById('pestana-login').classList.remove('activa');
                document.getElementById('pestana-register').classList.add('activa');
            }
        }
    </script>

    <footer>
        © <?php echo date('Y'); ?> FitxApp - Conforme al Estatuto de los Trabajadores Art. 34.9 y RGPD
    </footer>
</body>
</html>