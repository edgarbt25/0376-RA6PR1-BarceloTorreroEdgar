<?php
/**
 * FitxApp - Funciones Core del Sistema
 * Todas las funciones de lógica de negocio
 */

require_once 'db.php';
require_once 'auth.php';

/**
 * Calcular horas trabajadas entre dos fechas, restando pausas
 */
function calcularHorasTrabajadas($entrada, $salida, $pausas = []) {
    $tiempoEntrada = strtotime($entrada);
    $tiempoSalida = strtotime($salida);
    
    if ($tiempoSalida <= $tiempoEntrada) return 0;
    
    $segundosTotal = $tiempoSalida - $tiempoEntrada;
    
    // Restar pausas
    foreach ($pausas as $pausa) {
        if ($pausa['hora_fin']) {
            $segundosTotal -= (strtotime($pausa['hora_fin']) - strtotime($pausa['hora_inicio']));
        }
    }
    
    return round($segundosTotal / 3600, 2);
}

/**
 * Calcular porcentaje de cumplimiento horario de un usuario
 * Retorna array con porcentaje y color correspondiente
 */
function calcularCumplimiento($usuario_id, $fecha = null) {
    global $pdo;
    
    if (!$fecha) $fecha = date('Y-m-d');
    
    // Obtener horario del usuario
    $stmt = $pdo->prepare("SELECT horas_dia FROM horarios WHERE usuario_id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$usuario_id]);
    $horario = $stmt->fetch();
    
    $horasEsperadas = $horario ? $horario['horas_dia'] : 8;
    
    // Obtener fichajes del día
    $stmt = $pdo->prepare("SELECT SUM(horas_trabajadas) as total FROM fichajes 
                           WHERE usuario_id = ? AND DATE(hora_entrada) = ? AND hora_salida IS NOT NULL");
    $stmt->execute([$usuario_id, $fecha]);
    $fichaje = $stmt->fetch();
    
    $horasTrabajadas = $fichaje['total'] ?? 0;
    
    if ($horasEsperadas == 0) {
        $porcentaje = 100;
    } else {
        $porcentaje = min(100, round(($horasTrabajadas / $horasEsperadas) * 100, 1));
    }
    
    // Determinar color según cumplimiento
    if ($porcentaje >= 95) {
        $color = '#43a047'; // Verde - cumple
    } elseif ($porcentaje >= 75) {
        $color = '#f9a825'; // Amarillo - desviación leve
    } else {
        $color = '#c62828'; // Rojo - incumplimiento grave
    }
    
    return [
        'porcentaje' => $porcentaje,
        'color' => $color,
        'horas_trabajadas' => $horasTrabajadas,
        'horas_esperadas' => $horasEsperadas
    ];
}

/**
 * Verificar si usuario tiene un fichaje abierto sin cerrar
 */
function verificarFichajeAbierto($usuario_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, hora_entrada, proyecto_id FROM fichajes 
                           WHERE usuario_id = ? AND hora_salida IS NULL ORDER BY hora_entrada DESC LIMIT 1");
    $stmt->execute([$usuario_id]);
    return $stmt->fetch();
}

/**
 * Obtener estado actual del empleado: fichado, pausa o fuera
 */
function obtenerEstadoEmpleado($usuario_id) {
    global $pdo;
    
    $fichajeAbierto = verificarFichajeAbierto($usuario_id);
    
    if (!$fichajeAbierto) {
        return ['estado' => 'fuera', 'texto' => 'Fuera de jornada', 'color' => '#c62828', 'icono' => 'fa-door-open'];
    }
    
    // Verificar si está en pausa
    $stmt = $pdo->prepare("SELECT id FROM pausas WHERE fichaje_id = ? AND hora_fin IS NULL LIMIT 1");
    $stmt->execute([$fichajeAbierto['id']]);
    
    if ($stmt->rowCount() > 0) {
        return ['estado' => 'pausa', 'texto' => 'En pausa', 'color' => '#f57c00', 'icono' => 'fa-pause-circle', 'fichaje' => $fichajeAbierto];
    }
    
    return ['estado' => 'fichado', 'texto' => 'Trabajando', 'color' => '#43a047', 'icono' => 'fa-play-circle', 'fichaje' => $fichajeAbierto];
}

/**
 * Calcular horas extra de un usuario en un mes determinado
 */
function calcularHorasExtra($usuario_id, $mes, $anio) {
    global $pdo;
    
    $diasMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
    $horasContratadas = 0;
    $horasTrabajadas = 0;
    
    // Obtener horario
    $stmt = $pdo->prepare("SELECT horas_dia FROM horarios WHERE usuario_id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$usuario_id]);
    $horario = $stmt->fetch();
    $horasDia = $horario ? $horario['horas_dia'] : 8;
    
    // Contar días laborables del mes
    for ($i=1; $i<=$diasMes; $i++) {
        $fecha = "$anio-$mes-$i";
        $diaSemana = date('N', strtotime($fecha));
        if ($diaSemana <=5) $horasContratadas += $horasDia;
    }
    
    // Obtener horas trabajadas en el mes
    $stmt = $pdo->prepare("SELECT SUM(horas_trabajadas) as total FROM fichajes 
                           WHERE usuario_id = ? AND MONTH(hora_entrada) = ? AND YEAR(hora_entrada) = ? AND hora_salida IS NOT NULL");
    $stmt->execute([$usuario_id, $mes, $anio]);
    $res = $stmt->fetch();
    $horasTrabajadas = $res['total'] ?? 0;
    
    $horasExtra = max(0, $horasTrabajadas - $horasContratadas);
    
    return [
        'contratadas' => $horasContratadas,
        'trabajadas' => $horasTrabajadas,
        'extra' => $horasExtra,
        'saldo' => $horasTrabajadas - $horasContratadas
    ];
}

/**
 * Registrar acción en log de auditoría (cumplimiento RGPD)
 */
function registrarLog($usuario_id, $accion, $tabla = null, $id_registro = null, $datos_ant = null, $datos_new = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO logs_auditoria 
        (usuario_id, accion, tabla_afectada, id_registro, datos_anteriores, datos_nuevos, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $usuario_id,
        $accion,
        $tabla,
        $id_registro,
        $datos_ant ? json_encode($datos_ant) : null,
        $datos_new ? json_encode($datos_new) : null,
        obtenerIPCliente()
    ]);
}

/**
 * Enviar notificación a usuario
 */
function enviarNotificacion($usuario_id, $tipo, $mensaje) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje) VALUES (?, ?, ?)");
    $stmt->execute([$usuario_id, $tipo, $mensaje]);
}

/**
 * Exportar datos a CSV
 */
function exportarCSV($datos, $columnas, $nombre_archivo) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $salida = fopen('php://output', 'w');
    
    // BOM UTF-8 para compatibilidad con Excel español
    fputs($salida, "\xEF\xBB\xBF");
    
    // Encabezados
    fputcsv($salida, $columnas, ';');
    
    // Datos
    foreach ($datos as $fila) {
        fputcsv($salida, $fila, ';');
    }
    
    fclose($salida);
    exit;
}

/**
 * Formatear duración en horas y minutos
 */
function formatearDuracion($horasDecimales) {
    $horas = floor($horasDecimales);
    $minutos = round(($horasDecimales - $horas) * 60);
    return sprintf('%dh %02dm', $horas, $minutos);
}

/**
 * Obtener saldo de horas de un usuario
 */
function obtenerBalanceHoras($usuario_id, $periodo = 'mes') {
    $hoy = new DateTime();
    
    if ($periodo == 'mes') {
        $mes = $hoy->format('m');
        $anio = $hoy->format('Y');
        return calcularHorasExtra($usuario_id, $mes, $anio)['saldo'];
    }
    
    return 0;
}

?>