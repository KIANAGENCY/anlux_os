const adminDestino = document.getElementById('adminDestino');

if (adminDestino) {
    adminDestino.addEventListener('change', () => {
        if (adminDestino.value) {
            window.location.href = adminDestino.value;
        }
    });
}
