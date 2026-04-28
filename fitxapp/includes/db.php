<?php
/**
 * FitxApp - Archivo de conexión a Base de Datos
 * Cumplimiento Estatuto de los Trabajadores Art. 34.9 y RGPD
 * 
 * NOTA LEGAL: Todos los registros de fichajes y auditoría
 * deben conservarse durante un plazo mínimo de 4 AÑOS
 * sin modificaciones ni eliminaciones.
 */

// Configuración base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'fitxapp');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    // Conexión PDO con prepared statements
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true
        ]
    );
} catch (PDOException $e) {
    // No mostrar errores SQL detallados al usuario (seguridad)
    error_log("Error conexión BD: " . $e->getMessage());
    die("<div style='padding:2rem;text-align:center;font-family:Arial'>
            <h3>⚠️ Error de conexión</h3>
            <p>No se pudo conectar al sistema. Inténtelo de nuevo más tarde.</p>
            <p style='color:#999;font-size:0.9rem'>Si el problema persiste contacte con el administrador.</p>
         </div>");
}

// Función para sanitizar salidas y prevenir XSS
function escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

?>