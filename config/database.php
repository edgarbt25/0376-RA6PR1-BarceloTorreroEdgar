<?php
/**
 * Configuración de la base de datos
 * Vinewood Vice - Sistema de Seguimiento de Horas
 */

// Parámetros de conexión a MySQL
// Configuración por defecto para funcionar con servidor local
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'vinewood_vice');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Obtiene una instancia de conexión PDO a la base de datos
 * @return PDO Conexión a la base de datos
 * @throws PDOException Si falla la conexión
 */
function getDBConnection(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            throw new PDOException("Error de conexión a la base de datos: " . $e->getMessage(), (int)$e->getCode());
        }
    }
    
    return $pdo;
}

/**
 * Verifica si la base de datos está conectada
 * @return bool
 */
function isDBConnected(): bool {
    try {
        getDBConnection();
        return true;
    } catch (PDOException $e) {
        return false;
    }
}