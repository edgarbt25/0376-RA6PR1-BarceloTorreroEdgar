<?php
/**
 * FitxApp - Exportar CSV de horas
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

requerirEmpleado();
$usuario_id = $_SESSION['usuario_id'];

// Cabeceras para descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="horas_empleado_'.date('Y_m_d').'.csv"');

// Abrir salida
$output = fopen('php://output', 'w');

// Columnas
fputcsv($output, ['fecha', 'entrada', 'salida', 'horas', 'proyecto', 'actividad'], ';');

// Obtener todos los fichajes del empleado
$stmt = $pdo->prepare("SELECT DATE(f.hora_entrada) as fecha,
                               TIME(f.hora_entrada) as entrada,
                               TIME(f.hora_salida) as salida,
                               f.horas_trabajadas,
                               p.nombre as proyecto,
                               ta.nombre as actividad
                        FROM fichajes f
                        LEFT JOIN proyectos p ON f.proyecto_id = p.id
                        LEFT JOIN tipos_actividad ta ON f.actividad_id = ta.id
                        WHERE f.usuario_id = ?
                        ORDER BY f.hora_entrada DESC");
$stmt->execute([$usuario_id]);

while ($fila = $stmt->fetch(PDO::FETCH_NUM)) {
    fputcsv($output, $fila, ';');
}

fclose($output);
exit;