<?php
/**
 * FitxApp - Empleado - Mis Horas
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirEmpleado();

$usuario_id = $_SESSION['usuario_id'];
$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');

$diasMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);

// Obtener horario del usuario
$stmt = $pdo->prepare("SELECT horas_dia FROM horarios WHERE usuario_id = ? AND activo = 1 LIMIT 1");
$stmt->execute([$usuario_id]);
$horario = $stmt->fetch();
$horasDia = $horario ? $horario['horas_dia'] : 8;

// Obtener fichajes del mes
$stmt = $pdo->prepare("SELECT DATE(hora_entrada) as fecha, SUM(horas_trabajadas) as horas
                       FROM fichajes 
                       WHERE usuario_id = ? AND MONTH(hora_entrada) = ? AND YEAR(hora_entrada) = ? AND hora_salida IS NOT NULL
                       GROUP BY DATE(hora_entrada)");
$stmt->execute([$usuario_id, $mes, $anio]);
$fichajesMes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Calcular totales
$horasTotales = array_sum($fichajesMes);
$horasEsperadas = 0;
for($i=1; $i<=$diasMes; $i++) {
    $fecha = "$anio-$mes-".str_pad($i, 2, '0', STR_PAD_LEFT);
    $diaSemana = date('N', strtotime($fecha));
    if($diaSemana <=5) $horasEsperadas += $horasDia;
}
$diferencia = $horasTotales - $horasEsperadas;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Horas - FitxApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .calendario-mes {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            margin: 1rem 0;
        }
        .dia-calendario {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .dia-calendario:hover { transform: scale(1.05); }
        .dia-verde { background: #e8f5e9; color: #2e7d32; }
        .dia-amarillo { background: #fff8e1; color: #f57f17; }
        .dia-rojo { background: #ffebee; color: #c62828; }
        .dia-gris { background: #f5f5f5; color: #9e9e9e; }
        .dia-actual { border: 2px solid #1976d2; font-weight: 700; }
        .dia-nombre { font-weight: 600; padding: 0.5rem; text-align: center; color: #616161; }
    </style>
</head>
<body>

<?php include '../includes/sidebar_empleado.php'; ?>

<div class="main-contenedor">
    <?php include '../includes/header.php'; ?>
    
    <div class="contenido">
        <h1 style="margin-bottom: 2rem; color: #1a237e;">
            <i class="fas fa-calendar-alt"></i> Mis Horas
        </h1>

        <div class="kpi-grid">
            <div class="kpi-card azul">
                <div class="kpi-valor"><?php echo number_format($horasTotales, 1); ?>h</div>
                <div class="kpi-titulo">Horas Trabajadas</div>
                <i class="fas fa-clock kpi-icono"></i>
            </div>
            <div class="kpi-card morado">
                <div class="kpi-valor"><?php echo number_format($horasEsperadas, 1); ?>h</div>
                <div class="kpi-titulo">Horas Esperadas</div>
                <i class="fas fa-briefcase kpi-icono"></i>
            </div>
            <div class="kpi-card <?php echo $diferencia >= 0 ? 'verde' : 'rojo'; ?>">
                <div class="kpi-valor"><?php echo $diferencia >=0 ? '+' : ''; echo number_format($diferencia, 1); ?>h</div>
                <div class="kpi-titulo">Balance</div>
                <i class="fas fa-balance-scale kpi-icono"></i>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-calendar"></i> Calendario <?php echo strftime('%B %Y', mktime(0,0,0,$mes,1,$anio)); ?></h3>
            </div>
            <div class="card-body">
                <div class="calendario-mes">
                    <div class="dia-nombre">Lun</div>
                    <div class="dia-nombre">Mar</div>
                    <div class="dia-nombre">Mié</div>
                    <div class="dia-nombre">Jue</div>
                    <div class="dia-nombre">Vie</div>
                    <div class="dia-nombre">Sáb</div>
                    <div class="dia-nombre">Dom</div>
                    
                    <?php
                    $primerDia = date('N', strtotime("$anio-$mes-01")) - 1;
                    for($i=0; $i<$primerDia; $i++) echo '<div></div>';
                    
                    for($i=1; $i<=$diasMes; $i++) {
                        $fecha = "$anio-$mes-".str_pad($i, 2, '0', STR_PAD_LEFT);
                        $horaDia = $fichajesMes[$fecha] ?? 0;
                        $diaSemana = date('N', strtotime($fecha));
                        
                        if($horaDia >= $horasDia * 0.95) $clase = 'dia-verde';
                        elseif($horaDia > 0) $clase = 'dia-amarillo';
                        elseif($diaSemana >5) $clase = 'dia-gris';
                        else $clase = 'dia-rojo';
                        
                        if($fecha == date('Y-m-d')) $clase .= ' dia-actual';
                        
                        echo "<div class='dia-calendario $clase'>
                            <div style='font-weight: 700;'>$i</div>
                            <div>".number_format($horaDia,1)."h</div>
                        </div>";
                    }
                    ?>
                </div>
                
                <div style="display: flex; justify-content: center; gap: 2rem; margin-top: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 16px; height: 16px; background: #e8f5e9; border-radius: 4px;"></div>
                        <span>Correcto (>=95%)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 16px; height: 16px; background: #fff8e1; border-radius: 4px;"></div>
                        <span>Parcial</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 16px; height: 16px; background: #ffebee; border-radius: 4px;"></div>
                        <span>No fichó</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>