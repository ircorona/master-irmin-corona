function cambiarPorHora() {
    const ahora = new Date();
    const hora = ahora.getHours();
    
    // Obtener elementos
    const imagen = document.getElementById('imagen-hero');
    const mensaje = document.getElementById('mensaje-hora');
    const horaActual = document.getElementById('hora-actual');
    
    // ✅ VERIFICAR que los elementos existen ANTES de modificarlos
    if (!imagen || !mensaje || !horaActual) {
        console.error('Error: No se encontraron los elementos del DOM');
        return; // Salir de la función si no existen
    }
    
    // Mostrar hora actual
    horaActual.innerHTML = ahora.toLocaleTimeString();
    
    // Debug - ver qué hora detecta
    console.log('Hora actual detectada:', hora);
    
    // Cambiar según la hora
    if (hora >= 6 && hora < 12) {
        // Mañana (6 AM - 11:59 AM)
        imagen.src = '/images/morning.webp';
        mensaje.innerHTML = 'Good Morning! ☀️';
        console.log('Mostrando: MAÑANA');
    } 
    else if (hora >= 12 && hora < 19) {
        // Tarde (12 PM - 6:59 PM)
        imagen.src = '/images/afternoon.webp';
        mensaje.innerHTML = 'Good Afternoon! 🌤️';
        console.log('Mostrando: TARDE');
    } 
    else {
        // Noche (7 PM - 5:59 AM)
        imagen.src = '/images/night.webp';
        mensaje.innerHTML = 'Good Evening! 🌙';
        console.log('Mostrando: NOCHE');
    }
}

// ✅ ESPERAR a que TODO el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM cargado, iniciando script...');
    cambiarPorHora();
    setInterval(cambiarPorHora, 1000);
});