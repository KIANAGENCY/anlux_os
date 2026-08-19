// JavaScript de registro.

function contrasenaCumplePolitica(c) {
        if (!c || c.length < 8) return false;
        const tieneLetra = /[a-zA-Z\u00C0-\u024F\u1E00-\u1EFF]/.test(c);
        const tieneDigito = /[0-9]/.test(c);
        const tieneEspecial = /[^a-zA-Z0-9\u00C0-\u024F\u1E00-\u1EFF]/.test(c);
        return tieneLetra && (tieneDigito || tieneEspecial);
      }

      const togglePassword = document.getElementById('togglePassword');
      const passwordInput = document.getElementById('password');
      const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
      const confirmPasswordInput = document.getElementById('confirm_password');
      const formRegistro = document.querySelector('form[action="registro.php"]');
      const celularInput = document.getElementById('celular');
      if (celularInput) {
        celularInput.addEventListener('input', function () {
          this.value = String(this.value).replace(/\D/g, '').slice(0, 15);
        });
      }

      formRegistro.addEventListener('submit', function (e) {
        const p = passwordInput.value;
        const c = confirmPasswordInput.value;
        if (p !== c) {
          e.preventDefault();
          alert('Las contraseñas no coinciden.');
          return;
        }
        if (!contrasenaCumplePolitica(p)) {
          e.preventDefault();
          alert('La contraseña debe tener al menos 8 caracteres, incluir letras y al menos un número o un carácter especial (por ejemplo: @ # $ % &).');
          passwordInput.focus();
        }
      });

      togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.firstElementChild.classList.toggle('fa-eye');
        this.firstElementChild.classList.toggle('fa-eye-slash');
      });

      toggleConfirmPassword.addEventListener('click', function () {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
        this.firstElementChild.classList.toggle('fa-eye');
        this.firstElementChild.classList.toggle('fa-eye-slash');
      });
