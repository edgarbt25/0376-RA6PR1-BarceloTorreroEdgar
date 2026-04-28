-- Vinewood Vice - Sistema de Seguimiento de Horas
-- Script DDL para crear la base de datos y tablas
-- Ejecutar este script en MySQL para inicializar el sistema

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS vinewood_vice
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Usar la base de datos
USE vinewood_vice;

-- Tabla de usuarios (empleados, jefes y administradores)
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'employee') NOT NULL DEFAULT 'employee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de proyectos
CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    client VARCHAR(150) NOT NULL,
    budgeted_hours DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_client (client)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de registros de tiempo (time logs)
CREATE TABLE IF NOT EXISTS time_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED DEFAULT NULL,
    clock_in DATETIME NOT NULL,
    clock_out DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    
    INDEX idx_user_clock (user_id, clock_in),
    INDEX idx_project (project_id),
    INDEX idx_clock_in (clock_in)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuarios por defecto
-- Administrador (contraseña: admin123)
INSERT INTO users (name, email, password_hash, role) 
VALUES (
    'Administrador Sistema', 
    'admin@vinewoodvice.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'admin'
) ON DUPLICATE KEY UPDATE name = name;

-- Jefe de equipo (contraseña: manager123)
INSERT INTO users (name, email, password_hash, role) 
VALUES (
    'Jefe de Equipo', 
    'manager@vinewoodvice.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'manager'
) ON DUPLICATE KEY UPDATE name = name;

-- Insertar algunos proyectos de ejemplo
INSERT INTO projects (name, client, budgeted_hours) VALUES
    ('Desarrollo Web', 'Cliente A', 160.00),
    ('Aplicación Móvil', 'Cliente B', 240.00),
    ('Mantenimiento', 'Cliente C', 80.00)
ON DUPLICATE KEY UPDATE name = name;

-- Mostrar las tablas creadas
SHOW TABLES;