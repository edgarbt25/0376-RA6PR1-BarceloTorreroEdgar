-- =============================================
-- FitxApp - Sistema de Control Horario Laboral
-- Base de datos MySQL 8.0+
-- Cumple Estatuto de los Trabajadores Art. 34.9 y RGPD
-- =============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =============================================
-- CREACIÓN DE BASE DE DATOS
-- =============================================
CREATE DATABASE IF NOT EXISTS fitxapp DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE fitxapp;

-- =============================================
-- TABLA: departamentos
-- =============================================
CREATE TABLE departamentos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL UNIQUE,
  descripcion TEXT,
  supervisor_id INT NULL,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- TABLA: usuarios
-- =============================================
CREATE TABLE usuarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol ENUM('admin','supervisor','empleado') DEFAULT 'empleado',
  departamento_id INT NULL,
  cargo VARCHAR(100),
  telefono VARCHAR(20),
  avatar VARCHAR(255) NULL,
  activo TINYINT(1) DEFAULT 1,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- TABLA: proyectos
-- =============================================
CREATE TABLE proyectos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT,
  cliente VARCHAR(100),
  horas_presupuestadas DECIMAL(8,2) DEFAULT 0,
  estado ENUM('activo','pausado','finalizado') DEFAULT 'activo',
  color VARCHAR(7) DEFAULT '#1976d2',
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- TABLA: usuario_proyectos
-- =============================================
CREATE TABLE usuario_proyectos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  proyecto_id INT NOT NULL,
  rol ENUM('responsable','miembro') DEFAULT 'miembro',
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE,
  UNIQUE KEY unique_usuario_proyecto (usuario_id, proyecto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- TABLA: tipos_actividad
-- =============================================
CREATE TABLE tipos_actividad (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  color VARCHAR(7) DEFAULT '#1976d2',
  descripcion TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- TABLA: fichajes
-- =============================================
CREATE TABLE fichajes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  proyecto_id INT NULL,
  actividad_id INT NULL,
  tipo_entrada ENUM('normal','manual','correccion') DEFAULT 'normal',
  hora_entrada DATETIME NOT NULL,
  hora_salida DATETIME NULL,
  horas_trabajadas DECIMAL(6,2) DEFAULT 0,
  descripcion TEXT NULL,
  etiqueta VARCHAR(50) NULL,
  ip_address VARCHAR(45) NOT NULL,
  latitud DECIMAL(10,8) NULL,
  longitud DECIMAL(11,8) NULL,
  validado TINYINT(1) DEFAULT 0,
  validado_por INT NULL,
  fecha_validacion DATETIME NULL,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE SET NULL,
  FOREIGN KEY (actividad_id) REFERENCES tipos_actividad(id) ON DELETE SET NULL,
  FOREIGN KEY (validado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- TABLA: pausas
-- =============================================
CREATE TABLE pausas (
  id INT PRIMARY KEY AUTO_INCREMENT,
  fichaje_id INT NOT NULL,
  tipo_pausa ENUM('comida','descanso','personal') DEFAULT 'descanso',
  hora_inicio DATETIME NOT NULL,
  hora_fin DATETIME NULL,
  duracion_minutos INT DEFAULT 0,
  FOREIGN KEY (fichaje_id) REFERENCES fichajes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- TABLA: horarios
-- =============================================
CREATE TABLE horarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  lunes_inicio TIME NULL,
  lunes_fin TIME NULL,
  martes_inicio TIME NULL,
  martes_fin TIME NULL,
  miercoles_inicio TIME NULL,
  miercoles_fin TIME NULL,
  jueves_inicio TIME NULL,
  jueves_fin TIME NULL,
  viernes_inicio TIME NULL,
  viernes_fin TIME NULL,
  sabado_inicio TIME NULL,
  sabado_fin TIME NULL,
  domingo_inicio TIME NULL,
  domingo_fin TIME NULL,
  horas_dia DECIMAL(4,2) DEFAULT 8.00,
  activo TINYINT(1) DEFAULT 1,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- TABLA: solicitudes_correccion
-- =============================================
CREATE TABLE solicitudes_correccion (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  fichaje_id INT NULL,
  motivo VARCHAR(150) NOT NULL,
  descripcion TEXT NOT NULL,
  hora_correcta_entrada DATETIME NULL,
  hora_correcta_salida DATETIME NULL,
  estado ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
  admin_id INT NULL,
  motivo_resolucion TEXT NULL,
  fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_resolucion DATETIME NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (fichaje_id) REFERENCES fichajes(id) ON DELETE SET NULL,
  FOREIGN KEY (admin_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- TABLA: notificaciones
-- =============================================
CREATE TABLE notificaciones (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  tipo ENUM('info','exito','advertencia','peligro') DEFAULT 'info',
  mensaje VARCHAR(255) NOT NULL,
  leida TINYINT(1) DEFAULT 0,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- TABLA: logs_auditoria
-- =============================================
CREATE TABLE logs_auditoria (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NULL,
  accion VARCHAR(100) NOT NULL,
  tabla_afectada VARCHAR(100) NULL,
  id_registro INT NULL,
  datos_anteriores JSON NULL,
  datos_nuevos JSON NULL,
  ip_address VARCHAR(45) NOT NULL,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- TABLA: horas_extra
-- =============================================
CREATE TABLE horas_extra (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  mes INT NOT NULL,
  anio INT NOT NULL,
  horas_contratadas DECIMAL(8,2) NOT NULL,
  horas_trabajadas DECIMAL(8,2) NOT NULL,
  horas_extra_acumuladas DECIMAL(8,2) DEFAULT 0,
  saldo_horas DECIMAL(8,2) DEFAULT 0,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  UNIQUE KEY unique_usuario_mes (usuario_id, mes, anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- ÍNDICES PARA RENDIMIENTO
-- =============================================
CREATE INDEX idx_fichajes_usuario ON fichajes(usuario_id);
CREATE INDEX idx_fichajes_fecha ON fichajes(fecha_creacion);
CREATE INDEX idx_horarios_usuario ON horarios(usuario_id);
CREATE INDEX idx_logs_fecha ON logs_auditoria(fecha);
CREATE INDEX idx_notificaciones_usuario ON notificaciones(usuario_id);

-- =============================================
-- DATOS DE DEMOSTRACIÓN
-- =============================================

-- Departamentos
INSERT INTO departamentos (nombre, descripcion) VALUES
('Producción', 'Departamento de fabricación y producción'),
('Administración', 'Gestión administrativa, contabilidad y RRHH'),
('Montaje', 'Equipo de montaje e instalación'),
('Logística', 'Gestión de almacén y transporte');

-- Usuarios (contraseñas: admin123 y emp123)
INSERT INTO usuarios (nombre, apellidos, email, password, rol, departamento_id, cargo, telefono, activo) VALUES
('Administrador', 'Sistema', 'admin@fitxapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 2, 'Administrador Sistema', '910000001', 1),
('María', 'González López', 'maria@fitxapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supervisor', 1, 'Jefa Producción', '910000002', 1),
('Joan', 'Martínez Pérez', 'joan@fitxapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado', 1, 'Operario Producción', '910000003', 1),
('Ana', 'Rodríguez Sánchez', 'ana@fitxapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado', 1, 'Operario Producción', '910000004', 1),
('Carlos', 'Fernández Gómez', 'carlos@fitxapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado', 3, 'Montador', '910000005', 1),
('Laura', 'Díaz Ruiz', 'laura@fitxapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado', 3, 'Montador', '910000006', 1),
('Pedro', 'Moreno Álvarez', 'pedro@fitxapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado', 4, 'Logístico', '910000007', 1),
('Sara', 'Jiménez Martín', 'sara@fitxapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado', 4, 'Logístico', '910000008', 1),
('David', 'Gutiérrez Romero', 'david@fitxapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado', 2, 'Administrativo', '910000009', 1),
('Elena', 'Muñoz Herrera', 'elena@fitxapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado', 2, 'Contable', '910000010', 1);

-- Proyectos
INSERT INTO proyectos (nombre, descripcion, cliente, horas_presupuestadas, estado, color) VALUES
('Ampliación Planta Norte', 'Ampliación de la línea de producción en planta norte', 'TecnoIndustrias SL', 1200, 'activo', '#c62828'),
('Sistema Control Calidad', 'Implementación nuevo sistema QC', 'CalidadTotal SA', 650, 'activo', '#f57c00'),
('Mantenimiento Máquinas', 'Mantenimiento preventivo anual maquinaria', 'Equipamientos Industriales', 320, 'activo', '#43a047'),
('Almacén Automatizado', 'Montaje sistema automatizado almacén', 'LogisPro', 890, 'activo', '#1976d2'),
('Formación Seguridad', 'Curso formación seguridad laboral para todo personal', 'PrevenciónPlus', 180, 'activo', '#7b1fa2');

-- Tipos de actividad
INSERT INTO tipos_actividad (nombre, color, descripcion) VALUES
('Fabricación', '#2e7d32', 'Trabajo en línea de producción'),
('Montaje', '#1565c0', 'Montaje e instalación de equipos'),
('Administración', '#6d4c41', 'Tareas administrativas y oficina'),
('Logística', '#ef6c00', 'Gestión de almacén y transporte'),
('Reunión', '#7b1fa2', 'Reuniones y coordinación'),
('Formación', '#0277bd', 'Formación y capacitación');

-- Asignación horarios por defecto (8:00 a 17:00, 1h comida)
INSERT INTO horarios (usuario_id, lunes_inicio, lunes_fin, martes_inicio, martes_fin, miercoles_inicio, miercoles_fin, jueves_inicio, jueves_fin, viernes_inicio, viernes_fin, horas_dia)
SELECT id, '08:00:00', '17:00:00', '08:00:00', '17:00:00', '08:00:00', '17:00:00', '08:00:00', '17:00:00', '08:00:00', '17:00:00', 8.00
FROM usuarios WHERE rol = 'empleado';

COMMIT;

-- =============================================
-- NOTA LEGAL:
-- Los registros de fichajes deben conservarse
-- durante un plazo mínimo de 4 AÑOS según
-- el Estatuto de los Trabajadores Artículo 34.9
-- Cumplimiento RGPD UE 2016/679
-- =============================================