<?php
/**
 * FitxApp - Header común para todas las páginas
 */
?>
<header class="header">
    <div style="display: flex; align-items: center; gap: 1.5rem;">
        <button id="btnToggleSidebar" class="btn btn-primario" style="padding: 0.5rem 0.75rem;">
            <i class="fas fa-bars"></i>
        </button>
        <div id="reloj-header" class="header-reloj"></div>
    </div>

    <div class="header-usuario">
        <div style="text-align: right; margin-right: 1rem;">
            <div style="font-weight: 600;"><?php echo escape($_SESSION['nombre'] . ' ' . $_SESSION['apellidos']); ?></div>
            <div style="font-size: 0.8rem; color: #757575; text-transform: uppercase;"><?php echo escape($_SESSION['rol']); ?></div>
        </div>
        <div style="width: 40px; height: 40px; border-radius: 50%; background: #1976d2; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;">
            <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1) . substr($_SESSION['apellidos'], 0, 1)); ?>
        </div>
    </div>
</header>

<script>
// Reloj en tiempo real
function actualizarReloj() {
    const ahora = new Date();
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    const segundos = String(ahora.getSeconds()).padStart(2, '0');
    document.getElementById('reloj-header').textContent = `${horas}:${minutos}:${segundos}`;
}

setInterval(actualizarReloj, 1000);
actualizarReloj();

// Botón toggle sidebar
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('btnToggleSidebar');
    if(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('cerrado');
        });
    }
});
</script>