document.getElementById('togglePassword').addEventListener('click', () => {
    const input = document.getElementById('password');
    const btn = document.getElementById('togglePassword');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.innerHTML = show ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
});
