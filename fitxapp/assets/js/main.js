/**
 * FitxApp - Archivo JavaScript Principal
 * Funciones comunes para toda la aplicación
 */

// Toast notifications
function mostrarToast(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast ${tipo}`;
    toast.innerHTML = `<i class="fas fa-info-circle"></i> ${mensaje}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Confirmación antes de acciones
function confirmarAccion(mensaje) {
    return confirm(mensaje);
}

// Formatear fechas
function formatearFecha(fecha) {
    const d = new Date(fecha);
    return String(d.getDate()).padStart(2, '0') + '/' + 
           String(d.getMonth() + 1).padStart(2, '0') + '/' + 
           d.getFullYear();
}

// Formatear horas
function formatearHora(fecha) {
    const d = new Date(fecha);
    return String(d.getHours()).padStart(2, '0') + ':' + 
           String(d.getMinutes()).padStart(2, '0');
}

// Auto cerrar mensajes flash
document.addEventListener('DOMContentLoaded', function() {
    // Efecto ripple en botones
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            ripple.style.cssText = `
                position: absolute;
                background: rgba(255,255,255,0.4);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s linear;
                left: ${e.clientX - rect.left}px;
                top: ${e.clientY - rect.top}px;
                width: ${Math.max(rect.width, rect.height)}px;
                height: ${Math.max(rect.width, rect.height)}px;
            `;
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });
});

// Animación ripple
const style = document.createElement('style');
style.textContent = `
@keyframes ripple {
    to {
        transform: scale(4);
        opacity: 0;
    }
}
.btn {
    position: relative;
    overflow: hidden;
}
`;
document.head.appendChild(style);