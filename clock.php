<?php
/**
 * Endpoint para acciones de fichaje Clock In / Clock Out
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

require_once __DIR__ . '/includes/auth.php';

// Requerir autenticación
requireAuth('login.php');

$user = getCurrentUser();
$response = ['success' => false, 'message' => '', 'data' => []];

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $response['message'] = 'Token de seguridad inválido';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo = getDBConnection();

        if ($_POST['action'] === 'clock_in') {
            // Verificar que no tenga ya un fichaje activo
            $stmt = $pdo->prepare("
                SELECT id FROM time_logs 
                WHERE user_id = :user_id 
                    AND clock_out IS NULL 
                    AND DATE(clock_in) = CURDATE()
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $user['id']]);
            
            if ($stmt->fetch()) {
                $response['message'] = 'Ya tienes un fichaje activo abierto';
                echo json_encode($response);
                exit;
            }

            $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
            
            if (!$projectId) {
                $response['message'] = 'Debes seleccionar un proyecto';
                echo json_encode($response);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO time_logs (user_id, project_id, clock_in) 
                VALUES (:user_id, :project_id, NOW())
            ");
            $stmt->execute([
                ':user_id' => $user['id'],
                ':project_id' => $projectId
            ]);

            $logId = $pdo->lastInsertId();
            
            // Obtener datos actualizados
            $stmt = $pdo->prepare("
                SELECT 
                    tl.id,
                    tl.clock_in,
                    p.name as project_name
                FROM time_logs tl
                LEFT JOIN projects p ON tl.project_id = p.id
                WHERE tl.id = :id
            ");
            $stmt->execute([':id' => $logId]);
            $log = $stmt->fetch();

            $response['success'] = true;
            $response['message'] = '✅ Entrada registrada correctamente';
            $response['data'] = $log;

        } elseif ($_POST['action'] === 'clock_out') {
            // Buscar fichaje activo
            $stmt = $pdo->prepare("
                SELECT id, clock_in FROM time_logs 
                WHERE user_id = :user_id 
                    AND clock_out IS NULL 
                    AND DATE(clock_in) = CURDATE()
                ORDER BY clock_in DESC 
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $user['id']]);
            $activeLog = $stmt->fetch();

            if (!$activeLog) {
                $response['message'] = 'No tienes ningún fichaje activo';
                echo json_encode($response);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE time_logs 
                SET clock_out = NOW(),
                    total_minutes = TIMESTAMPDIFF(MINUTE, clock_in, NOW())
                WHERE id = :id
            ");
            $stmt->execute([':id' => $activeLog['id']]);

            // Calcular horas trabajadas
            $totalMinutes = time() - strtotime($activeLog['clock_in']);
            $hours = floor($totalMinutes / 3600);
            $minutes = floor(($totalMinutes % 3600) / 60);

            $response['success'] = true;
            $response['message'] = "✅ Salida registrada. Total: {$hours}h {$minutes}m";
            $response['data'] = [
                'total_minutes' => $totalMinutes / 60,
                'formatted' => "{$hours}h {$minutes}m"
            ];
        }

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    }
}

echo json_encode($response);
exit;