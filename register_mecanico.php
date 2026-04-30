<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirectBasedOnUserType();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiroo - Registro de Mecánico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --kiroo-red: #d32f2f;
            --bg-dark: #0a0a0a;
            --bg-card: #1a1a1a;
            --mech-orange: #ff9800;
        }
        body {
            background-color: var(--bg-dark);
            color: white;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .register-container { width: 100%; max-width: 550px; }
        .register-card {
            background: var(--bg-card);
            border-radius: 30px;
            padding: 3rem 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }
        .register-header { text-align: center; margin-bottom: 2rem; }
        .register-header h2 { font-weight: 700; margin-bottom: 0.3rem; }
        .register-header .badge-mechanic {
            background: rgba(255, 152, 0, 0.2);
            color: #ffb74d;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-block;
            margin-top: 8px;
        }
        .form-control, .form-select {
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            color: #fff;
            border-radius: 12px;
            padding: 12px 16px;
        }
        .form-control:focus, .form-select:focus {
            background: #333;
            border-color: var(--mech-orange);
            box-shadow: 0 0 0 0.2rem rgba(255, 152, 0, 0.15);
            color: #fff;
        }
        .form-select option { background: #2a2a2a; color: #fff; }
        .form-label { color: #ccc; font-size: 0.85rem; margin-bottom: 6px; }
        .btn-register {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-weight: 600;
            width: 100%;
            margin-top: 1.5rem;
            transition: all 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 152, 0, 0.3);
        }
        .login-link { text-align: center; margin-top: 1.5rem; color: #b0b0b0; }
        .login-link a { color: var(--mech-orange); text-decoration: none; }
        .error-message {
            background: rgba(244, 67, 54, 0.15);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #ef5350;
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            display: none;
        }
        .error-message.show { display: block; }
    </style>
</head>
<body>

    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fa-solid fa-wrench fa-3x mb-3" style="color: #ff9800;"></i>
                <h2>Registro de Mecánico</h2>
                <p>Crea tu cuenta para atender clientes</p>
                <span class="badge-mechanic"><i class="fa-solid fa-gear me-1"></i>Cuenta Mecánico</span>
            </div>

            <div class="error-message" id="errorMessage">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <span id="errorText"></span>
            </div>

            <form id="registerForm">
                <div class="mb-3">
                    <label class="form-label">Nombre de usuario *</label>
                    <input type="text" class="form-control" id="username" placeholder="Ej: mecanico_jose" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Correo electrónico *</label>
                    <input type="email" class="form-control" id="email" placeholder="correo@ejemplo.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" class="form-control" id="fullName" placeholder="José Martínez" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Teléfono *</label>
                    <input type="tel" class="form-control" id="phone" placeholder="+503 1234 5678" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Especialidad *</label>
                    <select class="form-select" id="specialty" required>
                        <option value="" disabled selected>Selecciona tu especialidad</option>
                        <option value="general">🔧 Mecánica General</option>
                        <option value="frenos">🛑 Sistema de Frenos</option>
                        <option value="electrico">⚡ Sistema Eléctrico</option>
                        <option value="motor">⚙️ Motor y Transmisión</option>
                        <option value="suspension">🔩 Suspensión y Dirección</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nombre del taller (opcional)</label>
                    <input type="text" class="form-control" id="workshopName" placeholder="Nombre de tu taller">
                </div>
                <div class="mb-3">
                    <label class="form-label">Años de experiencia</label>
                    <input type="number" class="form-control" id="experience" placeholder="Ej: 5" min="0" max="50">
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña *</label>
                    <input type="password" class="form-control" id="password" placeholder="Mínimo 6 caracteres" minlength="6" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar contraseña *</label>
                    <input type="password" class="form-control" id="confirmPassword" placeholder="Repite tu contraseña" required>
                </div>

                <button type="submit" class="btn-register">
                    <i class="fa-solid fa-user-plus me-2"></i> Crear cuenta de Mecánico
                </button>
            </form>

            <div class="login-link">
                ¿Ya tienes cuenta? <a href="login.html">Inicia sesión aquí</a>
            </div>
        </div>
    </div>

    <script>
        const errorDiv = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');

        function showError(msg) {
            errorText.textContent = msg;
            errorDiv.classList.add('show');
        }

        function hideError() {
            errorDiv.classList.remove('show');
        }

        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            hideError();

            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                showError('Las contraseñas no coinciden');
                return;
            }

            if (password.length < 6) {
                showError('La contraseña debe tener al menos 6 caracteres');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Creando cuenta...';
            submitBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'register_mecanico');
                formData.append('username', document.getElementById('username').value.trim());
                formData.append('email', document.getElementById('email').value.trim());
                formData.append('full_name', document.getElementById('fullName').value.trim());
                formData.append('phone', document.getElementById('phone').value.trim());
                formData.append('specialty', document.getElementById('specialty').value);
                formData.append('workshop_name', document.getElementById('workshopName').value.trim());
                formData.append('experience', document.getElementById('experience').value);
                formData.append('password', password);
                formData.append('confirm_password', confirmPassword);

                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error de conexión. Intenta nuevamente.');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>