<?php
/**
 * FitxApp - Sidebar para Administrador
 */
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <i class="fas fa-clock" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
        <h2>FitxApp</h2>
        <div style="font-size: 0.7rem; opacity: 0.7;">PANEL ADMINISTRADOR</div>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="empleados.php" <?php echo in_array(basename($_SERVER['PHP_SELF']), ['empleados.php','empleados_añadir.php','empleados_editar.php','empleados_ver.php']) ? 'class="activo"' : ''; ?>>
                <i class="fas fa-users"></i>
                <span>Gestión Empleados</span>
            </a>
        </li>
        <li>
            <a href="proyectos.php" <?php echo in_array(basename($_SERVER['PHP_SELF']), ['proyectos.php','proyectos_añadir.php','proyectos_editar.php']) ? 'class="activo"' : ''; ?>>
                <i class="fas fa-project-diagram"></i>
                <span>Gestión Proyectos</span>
            </a>
        </li>
        <li>
            <a href="fichajes.php" <?php echo basename($_SERVER['PHP_SELF']) == 'fichajes.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-clock"></i>
                <span>Vista Fichajes Tiempo Real</span>
            </a>
        </li>
        <li>
            <a href="fichajes_validar.php" <?php echo basename($_SERVER['PHP_SELF']) == 'fichajes_validar.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-check-circle"></i>
                <span>Validar Fichajes</span>
            </a>
        </li>
        <li>
            <a href="horarios.php" <?php echo basename($_SERVER['PHP_SELF']) == 'horarios.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-calendar-alt"></i>
                <span>Gestión Horarios</span>
            </a>
        </li>
        <li>
            <a href="incidencias.php" <?php echo basename($_SERVER['PHP_SELF']) == 'incidencias.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-exclamation-triangle"></i>
                <span>Incidencias y Correcciones</span>
            </a>
        </li>
        <li>
            <a href="reportes.php" <?php echo in_array(basename($_SERVER['PHP_SELF']), ['reportes.php','reportes_empleado.php','reportes_proyecto.php']) ? 'class="activo"' : ''; ?>>
                <i class="fas fa-chart-bar"></i>
                <span>Reportes Avanzados</span>
            </a>
        </li>
        <li>
            <a href="alertas.php" <?php echo basename($_SERVER['PHP_SELF']) == 'alertas.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-bell"></i>
                <span>Alertas y Cumplimiento</span>
            </a>
        </li>
        <li>
            <a href="auditoria.php" <?php echo basename($_SERVER['PHP_SELF']) == 'auditoria.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-history"></i>
                <span>Log Auditoría</span>
            </a>
        </li>
        <li>
            <a href="configuracion.php" <?php echo basename($_SERVER['PHP_SELF']) == 'configuracion.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
        </li>
        <li style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt" style="color: #ef5350;"></i>
                <span style="color: #ef5350;">Cerrar Sesión</span>
            </a>
        </li>
    </ul>
</div>