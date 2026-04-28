<?php
/**
 * FitxApp - Sidebar para Empleados
 */
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <i class="fas fa-clock" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
        <h2>FitxApp</h2>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="fichar.php" <?php echo basename($_SERVER['PHP_SELF']) == 'fichar.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-clock"></i>
                <span>Fichar Entrada/Salida</span>
            </a>
        </li>
        <li>
            <a href="mis_horas.php" <?php echo basename($_SERVER['PHP_SELF']) == 'mis_horas.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-calendar-alt"></i>
                <span>Mis Horas</span>
            </a>
        </li>
        <li>
            <a href="mis_proyectos.php" <?php echo basename($_SERVER['PHP_SELF']) == 'mis_proyectos.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-project-diagram"></i>
                <span>Mis Proyectos</span>
            </a>
        </li>
        <li>
            <a href="mi_historial.php" <?php echo basename($_SERVER['PHP_SELF']) == 'mi_historial.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-history"></i>
                <span>Mi Historial</span>
            </a>
        </li>
        <li>
            <a href="solicitar_correccion.php" <?php echo basename($_SERVER['PHP_SELF']) == 'solicitar_correccion.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-edit"></i>
                <span>Solicitar Corrección</span>
            </a>
        </li>
        <li>
            <a href="mis_reportes.php" <?php echo basename($_SERVER['PHP_SELF']) == 'mis_reportes.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-chart-line"></i>
                <span>Mis Reportes</span>
            </a>
        </li>
        <li>
            <a href="notificaciones.php" <?php echo basename($_SERVER['PHP_SELF']) == 'notificaciones.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-bell"></i>
                <span>Notificaciones</span>
            </a>
        </li>
        <li>
            <a href="perfil.php" <?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'class="activo"' : ''; ?>>
                <i class="fas fa-user"></i>
                <span>Mi Perfil</span>
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