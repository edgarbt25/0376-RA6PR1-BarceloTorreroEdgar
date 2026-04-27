# Vinewood Vice - Sistema de Seguimiento de Horas

Sistema web profesional para el registro y control de horas de trabajo por empleado y proyecto.

## 🎯 Características Principales

- ✅ **Autenticación segura** con registro e inicio de sesión
- ✅ **Tres roles de usuario**: Administrador, Jefe de Equipo y Empleado
- ✅ **Cifrado de contraseñas** con `password_hash()` (bcrypt)
- ✅ **Protección CSRF** en todos los formularios
- ✅ **Prevención de SQL Injection** usando PDO con Prepared Statements
- ✅ **Diseño responsive profesional** adaptado a todos los dispositivos
- ✅ **Pantallas específicas** según el rol del usuario
- ✅ **Sidebar de navegación** para administradores y jefes

## 🏗️ Arquitectura del Sistema

### Roles de Usuario

| Rol | Descripción | Permisos |
|-----|-------------|----------|
| **Administrador** | Gestión completa del sistema | Todo el sistema, usuarios, proyectos, configuración |
| **Jefe de Equipo** | Supervisión de empleados | Ver equipo, informes, estadísticas del equipo |
| **Empleado** | Registro de horas | Ver su propio panel, registrar tiempo (próximamente) |

### Paneles por Rol

#### 📊 Panel de Empleado
- Estadísticas personales (horas hoy, semana, mes)
- Estado actual de fichaje
- Historial de registros personales
- Próximamente: Botón clock in/out, asignación a proyectos

#### 👥 Panel de Jefe de Equipo
- Vista completa del equipo
- Estadísticas del equipo (activos, horas totales)
- Lista de empleados con estado en tiempo real
- Detección de empleados por debajo de 8h
- Próximamente: Informes detallados, exportación

#### ⚙️ Panel de Administrador
- Visión global del sistema
- Gestión de usuarios, proyectos y registros
- Estadísticas generales
- Próximamente: CRUD completo, alertas, informes

## 🛠️ Requisitos Técnicos

- **PHP** 7.4 o superior
- **MySQL** 5.7+ o **MariaDB** 10.2+
- **Apache** con mod_rewrite habilitado
- **Extensión PDO MySQL** activada en PHP

## 📦 Instalación

### 1. Clonar o descargar el proyecto

```bash
cd /var/www/html/0376-RA6PR1-BarceloTorreroEdgar
```

### 2. Configurar la base de datos

```bash
# Acceder a MySQL
mysql -u root -p

# Ejecutar el script SQL
source sql/database.sql
```

O desde línea de comandos:
```bash
mysql -u root -p < sql/database.sql
```

### 3. Configurar la conexión

Editar `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'vinewood_vice');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_CHARSET', 'utf8mb4');
```

### 4. Permisos de archivos

```bash
chmod -R 755 /var/www/html/0376-RA6PR1-BarceloTorreroEdgar
```

### 5. Acceder a la aplicación

```
http://localhost/0376-RA6PR1-BarceloTorreroEdgar/
```

## 🔑 Credenciales por Defecto

### Administrador
- **Email**: admin@vinewoodvice.com
- **Contraseña**: admin123

### Jefe de Equipo
- **Email**: manager@vinewoodvice.com
- **Contraseña**: manager123

⚠️ **Importante**: Cambia estas contraseñas después del primer inicio de sesión.

## 📁 Estructura del Proyecto

```
0376-RA6PR1-BarceloTorreroEdgar/
├── admin/
│   └── index.php              # Panel de administrador
├── manager/
│   └── team.php               # Panel de jefe de equipo
├── config/
│   └── database.php           # Configuración de base de datos
├── includes/
│   ├── auth.php               # Funciones de autenticación
│   ├── header.php             # Encabezado común
│   └── footer.php             # Pie de página común
├── sql/
│   └── database.sql           # Script DDL completo
├── .htaccess                  # Configuración de seguridad Apache
├── index.php                  # Landing page
├── login.php                  # Inicio de sesión
├── register.php               # Registro de usuarios
├── logout.php                 # Cierre de sesión
├── dashboard.php              # Panel principal (por rol)
├── style.css                  # Diseño profesional responsive
└── README.md                  # Este archivo
```

## 🗄️ Base de Datos

### Tabla `users`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT UNSIGNED | ID único (autoincremental) |
| name | VARCHAR(100) | Nombre del usuario |
| email | VARCHAR(150) | Email único |
| password_hash | VARCHAR(255) | Contraseña cifrada (bcrypt) |
| role | ENUM('admin','manager','employee') | Rol del usuario |
| created_at | TIMESTAMP | Fecha de creación |
| updated_at | TIMESTAMP | Fecha de actualización |

### Tabla `projects`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT UNSIGNED | ID único |
| name | VARCHAR(150) | Nombre del proyecto |
| client | VARCHAR(150) | Cliente asociado |
| budgeted_hours | DECIMAL(10,2) | Horas presupuestadas |
| created_at | TIMESTAMP | Fecha de creación |

### Tabla `time_logs`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT UNSIGNED | ID único |
| user_id | INT UNSIGNED | ID del usuario |
| project_id | INT UNSIGNED | ID del proyecto |
| clock_in | DATETIME | Hora de entrada |
| clock_out | DATETIME | Hora de salida |
| notes | TEXT | Notas adicionales |
| created_at | TIMESTAMP | Fecha de creación |

## 🧪 Pruebas Manuales

### 1. Verificar la instalación
1. Accede a `http://localhost/0376-RA6PR1-BarceloTorreroEdgar/`
2. Deberías ver la landing page profesional

### 2. Probar inicio de sesión como Admin
1. Haz clic en "Iniciar Sesión"
2. Usa: `admin@vinewoodvice.com` / `admin123`
3. Deberías ver el panel de administrador con sidebar

### 3. Probar inicio de sesión como Manager
1. Cierra sesión
2. Inicia con: `manager@vinewoodvice.com` / `manager123`
3. Deberías ver el panel de jefe con opción "Equipo"

### 4. Probar registro de empleado
1. Cierra sesión
2. Haz clic en "Registrarse"
3. Completa el formulario
4. Serás redirigido a login

### 5. Probar panel de empleado
1. Inicia sesión con el empleado creado
2. Deberías ver el panel básico de empleado
3. Sin acceso a administración o equipo

### 6. Verificar seguridad
1. Intenta acceder a `/admin/` como empleado → Error/Redirección
2. Intenta acceder a `/manager/team.php` como empleado → Error/Redirección
3. Verifica contraseñas cifradas en BD

## 🔒 Seguridad Implementada

1. **Contraseñas cifradas** con `password_hash()` (bcrypt, coste 10)
2. **Prepared Statements** en todas las consultas SQL
3. **Protección CSRF** con tokens únicos por sesión
4. **Sanitización** con `htmlspecialchars()` y `filter_var()`
5. **Sesiones seguras** con regeneración de ID al login
6. **Cabeceras de seguridad** en `.htaccess`
7. **Protección de directorios** sensibles (config, includes, sql)
8. **Validación de roles** en cada página protegida

## 📱 Diseño Responsive

El CSS incluye breakpoints para:
- **Móviles** (< 480px): Layout vertical, menús simplificados
- **Tablets** (< 768px): Navegación adaptada, grids de 1-2 columnas
- **Desktop** (> 1024px): Layout completo con sidebar

## 🗺️ Roadmap

### ✅ ITERACIÓN 1 - Sistema de Autenticación (COMPLETADA)
- [x] Registro e inicio de sesión
- [x] Cifrado de contraseñas
- [x] Gestión de sesiones
- [x] Tres roles: admin, manager, employee
- [x] Diseño profesional moderno
- [x] Paneles específicos por rol

### 🔄 ITERACIÓN 2 - Control de Horas
- [ ] Botón clock in / clock out
- [ ] Asociación a proyectos
- [ ] Guardado de timestamps
- [ ] Notas de actividad

### 📋 ITERACIÓN 3 - Alertas de Incumplimiento
- [ ] Detección de horas insuficientes (< 8h/día)
- [ ] Alertas de llegadas tarde
- [ ] Panel de "lista roja"
- [ ] Notificaciones automáticas

### 📋 ITERACIÓN 4 - Informes de Proyectos
- [ ] Total de horas por proyecto
- [ ] Gráficos con Chart.js
- [ ] Comparativa presupuestado vs real
- [ ] Exportación a PDF/Excel

### 📋 ITERACIÓN 5 - Panel de Administración
- [ ] CRUD de empleados
- [ ] CRUD de proyectos
- [ ] Vista de todos los registros
- [ ] Configuración del sistema

## 🎨 Paleta de Colores

| Color | Hex | Uso |
|-------|-----|-----|
| Primary | `#2563eb` | Botones, enlaces, elementos destacados |
| Primary Dark | `#1d4ed8` | Hover states |
| Success | `#10b981` | Estados positivos, completado |
| Warning | `#f59e0b` | Advertencias, jefe de equipo |
| Danger | `#ef4444` | Errores, alertas |
| Gray 900 | `#111827` | Texto principal |
| Gray 500 | `#6b7280` | Texto secundario |

## 📄 Licencia

Proyecto desarrollado para Vinewood Vice.

---

**Versión**: 2.0.0 (ITERACIÓN 1 - Rediseño Profesional)  
**Última actualización**: 27/04/2026  
**Desarrollado con**: PHP 8, MySQL, CSS3 Moderno