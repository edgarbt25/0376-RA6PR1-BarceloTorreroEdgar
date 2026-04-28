<?php
/**
 * Panel de Administrador - Control de Asistencia
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

require_once __DIR__ . '/../includes/auth.php';

// Requerir ser administrador
requireAdmin('../index.php');

$user = getCurrentUser();
$pageTitle = 'Panel de Administración';
$showBackButton = true;

try {
    $pdo = getDBConnection();

    // Configuración de horario laboral
    $workDayMinutes = 8 * 60; // 8 horas = 480 minutos
    $startHour = 9; // 09:00 entrada
    $endHour = 18;  // 18:00 salida

    // Obtener registros de hoy
    $stmt = $pdo->prepare("
        SELECT 
            u.id as user_id,
            u.name,
            u.email,
            tl.id as log_id,
            tl.clock_in,
            tl.clock_out,
            tl.total_minutes,
            p.name as project_name
        FROM users u
        LEFT JOIN time_logs tl ON u.id = tl.user_id AND DATE(tl.clock_in) = CURDATE()
        WHERE u.role = 'employee'
        ORDER BY u.name ASC
    ");
    $stmt->execute();
    $employees = $stmt->fetchAll();

    $redList = [];
    $stats = [
        'total_employees' => count($employees),
        'active_now' => 0,
        'incidences_today' => 0,
        'late_arrivals' => 0,
        'early_departures' => 0,
        'insufficient_hours' => 0,
        'average_hours' => 0
    ];

    $totalHoursToday = 0;

    foreach ($employees as $emp) {
        $incidences = [];
        $minutesWorked = 0;
        $minutesMissing = 0;

        if ($emp['clock_in']) {
            // Comprobar llegada tarde
            $clockInHour = (int)date('H', strtotime($emp['clock_in']));
            $clockInMinute = (int)date('i', strtotime($emp['clock_in']));
            
            if ($clockInHour > $startHour || ($clockInHour == $startHour && $clockInMinute > 0)) {
                $lateMinutes = ($clockInHour - $startHour) * 60 + $clockInMinute;
                $incidences[] = [
                    'type' => 'late',
                    'label' => 'Llegada Tarde',
                    'value' => "+{$lateMinutes} min",
                    'minutes' => $lateMinutes
                ];
                $stats['late_arrivals']++;
            }

            // Si ya ha salido
            if ($emp['clock_out']) {
                $minutesWorked = $emp['total_minutes'];
                $totalHoursToday += $minutesWorked;

                // Comprobar salida anticipada
                $clockOutHour = (int)date('H', strtotime($emp['clock_out']));
                $clockOutMinute = (int)date('i', strtotime($emp['clock_out']));
                
                if ($clockOutHour < $endHour || ($clockOutHour == $endHour && $clockOutMinute < 0)) {
                    $earlyMinutes = (($endHour - $clockOutHour) * 60) - $clockOutMinute;
                    $incidences[] = [
                        'type' => 'early',
                        'label' => 'Salida Anticipada',
                        'value' => "-{$earlyMinutes} min",
                        'minutes' => $earlyMinutes
                    ];
                    $stats['early_departures']++;
                }

                // Comprobar horas insuficientes
                if ($minutesWorked < $workDayMinutes) {
                    $minutesMissing = $workDayMinutes - $minutesWorked;
                    $incidences[] = [
                        'type' => 'hours',
                        'label' => 'Horas Insuficientes',
                        'value' => "Faltan " . floor($minutesMissing/60) . "h " . ($minutesMissing%60) . "m",
                        'minutes' => $minutesMissing
                    ];
                    $stats['insufficient_hours']++;
                }

            } else {
                // Todavia trabajando
                $stats['active_now']++;
                $minutesWorked = time() - strtotime($emp['clock_in']);
                $minutesWorked = floor($minutesWorked / 60);
            }

        } else {
            $incidences[] = [
                'type' => 'missing',
                'label' => 'NO HA FICHADO HOY',
                'value' => 'Sin registro',
                'minutes' => 480
            ];
        }

        if (!empty($incidences)) {
            $stats['incidences_today']++;
            $redList[] = [
                'employee' => $emp,
                'incidences' => $incidences,
                'minutes_worked' => $minutesWorked,
                'minutes_missing' => $minutesMissing
            ];
        }
    }

    if ($stats['total_employees'] > 0) {
        $stats['average_hours'] = $totalHoursToday / $stats['total_employees'];
    }

} catch (PDOException $e) {
    $employees = [];
    $redList = [];
}

function formatMinutesToHours($minutes) {
    if ($minutes === null || $minutes == 0) return '0h 0m';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h ' . $mins . 'm';
}

include '../includes/header.php';
?>

<div class="container-lg">
    <div class="dashboard-header">
        <h1 class="dashboard-welcome">🔴 Panel de Control de Asistencia</h1>
        <p class="dashboard-subtitle">Vista global de incumplimientos y registro horario</p>
    </div>

    <!-- Estadísticas globales -->
    <div class="stats-grid mb-xl">
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue">👥</div>
            <div class="stat-content">
                <span class="stat-value"><?php echo $stats['total_employees']; ?></span>
                <span class="stat-label">Empleados Totales</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-green">✅</div>
            <div class="stat-content">
                <span class="stat-value"><?php echo $stats['active_now']; ?></span>
                <span class="stat-label">Trabajando Ahora</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-red">⚠️</div>
            <div class="stat-content">
                <span class="stat-value" style="color: var(--danger); font-weight: 800;"><?php echo $stats['incidences_today']; ?></span>
                <span class="stat-label">Incidencias Hoy</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-orange">⏱️</div>
            <div class="stat-content">
                <span class="stat-value"><?php echo formatMinutesToHours($stats['average_hours']); ?></span>
                <span class="stat-label">Promedio Horas</span>
            </div>
        </div>
    </div>

    <!-- Lista Roja de Incumplidores -->
    <div class="card">
        <div class="card-header" style="background: var(--danger-bg); border-bottom: 3px solid var(--danger);">
            <h3 class="card-title" style="color: var(--danger);">🔴 LISTA ROJA - EMPLEADOS INCUMPLIDORES HOY</h3>
            <span class="badge badge-danger" style="font-size: 1rem;"><?php echo count($redList); ?> incumplimientos</span>
        </div>
        <div class="card-body">

            <?php if (!empty($redList)): ?>
            <div class="red-list-container">
                <?php foreach ($redList as $item): ?>
                <div class="red-list-item">
                    <div class="red-list-header">
                        <div class="employee-info">
                            <div class="employee-avatar">
                                <?php echo strtoupper(substr($item['employee']['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h4 class="employee-name"><?php echo htmlspecialchars($item['employee']['name']); ?></h4>
                                <span class="employee-email"><?php echo htmlspecialchars($item['employee']['email']); ?></span>
                            </div>
                        </div>
                        <div class="employee-hours">
                            <div class="hours-worked"><?php echo formatMinutesToHours($item['minutes_worked']); ?></div>
                            <span class="hours-label">Trabajado hoy</span>
                        </div>
                    </div>

                    <div class="incidences-list">
                        <?php foreach ($item['incidences'] as $inc): ?>
                        <div class="incidence-badge incidence-<?php echo $inc['type']; ?>">
                            <span class="incidence-icon">
                                <?php 
                                    if ($inc['type'] === 'late') echo '⏰';
                                    elseif ($inc['type'] === 'early') echo '🏃';
                                    elseif ($inc['type'] === 'hours') echo '⏱️';
                                    else echo '❌';
                                ?>
                            </span>
                            <span class="incidence-text">
                                <strong><?php echo $inc['label']; ?></strong>
                                <span class="incidence-value"><?php echo $inc['value']; ?></span>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center" style="padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
                <h3 style="color: var(--success); margin-bottom: 0.5rem;">¡TODO CORRECTO!</h3>
                <p style="color: var(--gray-500);">Hoy no hay ningún empleado con incidencias o incumplimientos.</p>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Resumen de estadisticas -->
    <div class="stats-grid mt-xl" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
        <div class="stat-card" style="border-left: 4px solid var(--warning);">
            <div class="stat-content">
                <span class="stat-value" style="color: var(--warning);"><?php echo $stats['late_arrivals']; ?></span>
                <span class="stat-label">Llegadas Tarde</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--danger);">
            <div class="stat-content">
                <span class="stat-value" style="color: var(--danger);"><?php echo $stats['early_departures']; ?></span>
                <span class="stat-label">Salidas Anticipadas</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #f97316;">
            <div class="stat-content">
                <span class="stat-value" style="color: #f97316;"><?php echo $stats['insufficient_hours']; ?></span>
                <span class="stat-label">Horas Insuficientes</span>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>