<?php
/**
 * FitxApp - Administrador - Exportar reportes CSV
 * Exporta informes de horas por empleados y por proyectos
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

// Solo administradores pueden exportar
requerirAdmin();

$tipo = trim($_GET['tipo'] ?? '');

if ($tipo == 'empleados') {

    // =============================================
    // EXPORTACIÓN 1: HORAS POR EMPLEADOS
    // =============================================
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="horas_empleados_'.date('Y-m-d').'.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM UTF-8 para compatibilidad con Excel
    echo "\xEF\xBB\xBF";

    $salida = fopen('php://output', 'w');

    // Fila de cabeceras
    fputcsv($salida, [
        'Nombre Empleado',
        'Apellidos',
        'Departamento',
        'Fecha',
        'Hora Entrada',
        'Hora Salida',
        'Horas Trabajadas',
        'Proyecto',
        'Actividad'
    ], ';');

    // Consulta fichajes completos
    $stmt = $pdo->prepare("
        SELECT 
            u.nombre,
            u.apellidos,
            d.nombre as departamento,
            DATE(f.hora_entrada) as fecha,
            TIME(f.hora_entrada) as hora_entrada,
            TIME(f.hora_salida) as hora_salida,
            f.horas_trabajadas,
            p.nombre as proyecto,
            ta.nombre as actividad
        FROM fichajes f
        LEFT JOIN usuarios u ON f.usuario_id = u.id
        LEFT JOIN departamentos d ON u.departamento_id = d.id
        LEFT JOIN proyectos p ON f.proyecto_id = p.id
        LEFT JOIN tipos_actividad ta ON f.actividad_id = ta.id
        WHERE f.hora_salida IS NOT NULL
        ORDER BY f.hora_entrada DESC
    ");
    $stmt->execute();

    while ($fila = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($salida, $fila, ';');
    }

    fclose($salida);
    exit;

} elseif ($tipo == 'proyectos') {

    // =============================================
    // EXPORTACIÓN 2: HORAS POR PROYECTOS
    // =============================================
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="horas_proyectos_'.date('Y-m-d').'.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM UTF-8 para compatibilidad con Excel
    echo "\xEF\xBB\xBF";

    $salida = fopen('php://output', 'w');

    // Fila de cabeceras
    fputcsv($salida, [
        'Nombre Proyecto',
        'Cliente',
        'Horas Presupuestadas',
        'Horas Consumidas',
        '% Progreso',
        'Empleados Asignados',
        'Estado'
    ], ';');

    // Consulta proyectos con estadisticas
    $stmt = $pdo->prepare("
        SELECT 
            p.nombre,
            p.cliente,
            p.horas_presupuestadas,
            COALESCE(SUM(f.horas_trabajadas), 0) as horas_consumidas,
            CASE WHEN p.horas_presupuestadas > 0 
                THEN ROUND((COALESCE(SUM(f.horas_trabajadas), 0) / p.horas_presupuestadas) * 100, 1) 
                ELSE 0 
            END as porcentaje,
            (SELECT COUNT(DISTINCT usuario_id) FROM usuario_proyectos WHERE proyecto_id = p.id) as num_empleados,
            p.estado
        FROM proyectos p
        LEFT JOIN fichajes f ON p.id = f.proyecto_id
        WHERE p.estado = 'activo'
        GROUP BY p.id
        ORDER BY p.nombre ASC
    ");
    $stmt->execute();

    while ($proyecto = $stmt->fetch()) {
        fputcsv($salida, [
            $proyecto['nombre'],
            $proyecto['cliente'],
            $proyecto['horas_presupuestadas'],
            $proyecto['horas_consumidas'],
            $proyecto['porcentaje'] . ' %',
            $proyecto['num_empleados'],
            ucfirst($proyecto['estado'])
        ], ';');
    }

    fclose($salida);
    exit;

} else {
    // Tipo no valido
    header('Location: reportes.php');
    exit;
}

?>