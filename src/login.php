<?php
session_start();
require_once 'config.php';

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND estado = 'activo'");
        $stmt->bindParam(1, $email);
        $stmt->execute();

        if ($user = $stmt->fetch()) {

            if (password_verify($password, $user['password'])) {

                // Guardar sesión completa
                $_SESSION['usuario'] = $user['nombre'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_time'] = time();

                // Redirección según el rol
                if ($user['rol'] === 'admin') {
                    header("Location: admin/index.php");  // Redirige al panel admin
                } elseif ($user['rol'] === 'medico') {
                    header("Location: medico_panel.php");  // Redirige al panel médico
                } else {
                    header("Location: index.php");        // Redirige al panel usuario
                }
                exit();

            } else {
                $error = "❌ Contraseña incorrecta";
            }

        } else {
            $error = "❌ Usuario no encontrado o cuenta inactiva";
        }

    } else {
        $error = "⚠️ Completa todos los campos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Historias Clínicas</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-ink: #0f172a;
            --bg-blue: #1d4ed8;
            --bg-cyan: #0ea5e9;
            --surface: rgba(255, 255, 255, 0.9);
            --border: rgba(226, 232, 240, 0.95);
            --text: #0f172a;
            --muted: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body.login-page {
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(29, 78, 216, 0.16), transparent 26%),
                radial-gradient(circle at top right, rgba(14, 165, 233, 0.12), transparent 24%),
                linear-gradient(180deg, #f8fbff 0%, #edf4ff 100%);
            color: var(--text);
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        .auth-shell {
            width: 100%;
            max-width: 1160px;
            display: grid;
            grid-template-columns: 1.08fr 0.92fr;
            gap: 24px;
            align-items: stretch;
        }

        .auth-hero,
        .auth-card {
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 22px 50px rgba(15, 23, 42, 0.10);
        }

        .auth-hero {
            position: relative;
            padding: 40px;
            color: white;
            background:
                linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(29, 78, 216, 0.96) 56%, rgba(14, 165, 233, 0.92)),
                linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
        }

        .auth-hero::before,
        .auth-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.16) 0%, transparent 72%);
            pointer-events: none;
        }

        .auth-hero::before {
            width: 360px;
            height: 360px;
            top: -120px;
            right: -120px;
        }

        .auth-hero::after {
            width: 300px;
            height: 300px;
            left: -110px;
            bottom: -100px;
            opacity: 0.9;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .brand-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: fit-content;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(10px);
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .hero-copy {
            margin-top: 28px;
            max-width: 560px;
        }

        .hero-copy h1 {
            font-size: clamp(2.2rem, 4vw, 4rem);
            line-height: 1.02;
            letter-spacing: -0.04em;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .hero-copy p {
            font-size: 1.03rem;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.88);
            margin-bottom: 24px;
            max-width: 58ch;
        }

        .hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 28px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.16);
            font-size: 0.88rem;
            font-weight: 600;
        }

        .hero-stat-grid {
            margin-top: auto;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .hero-stat {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 20px;
            padding: 16px;
            backdrop-filter: blur(10px);
        }

        .hero-stat strong {
            display: block;
            font-size: 1.35rem;
            line-height: 1.1;
            margin-bottom: 6px;
        }

        .hero-stat span {
            display: block;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.82);
        }

        .auth-card {
            background: var(--surface);
            border: 1px solid var(--border);
            backdrop-filter: blur(16px);
            padding: 34px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
        }

        .auth-title {
            color: var(--text);
        }

        .auth-title h2 {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 8px;
        }

        .auth-title p {
            color: var(--muted);
            margin: 0;
            line-height: 1.6;
        }

        .role-tag {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
            font-weight: 700;
            font-size: 0.86rem;
        }

        .alert-custom {
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: shake 0.45s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px); }
            75% { transform: translateX(4px); }
        }

        .alert-danger {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #9f1239;
        }

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #166534;
        }

        .alert-custom i {
            margin-top: 2px;
            font-size: 1rem;
        }

        .alert-custom span {
            line-height: 1.5;
        }

        .form-stack {
            display: grid;
            gap: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--text);
            font-size: 0.92rem;
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom i.field-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.95rem;
            pointer-events: none;
        }

        .form-control-custom {
            width: 100%;
            height: 54px;
            padding: 0 48px 0 48px;
            border: 1.5px solid #dbe4f0;
            border-radius: 16px;
            font-size: 0.98rem;
            color: var(--text);
            background: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-control-custom::placeholder {
            color: #94a3b8;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--bg-blue);
            box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.10);
            transform: translateY(-1px);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--bg-blue);
        }

        .form-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.92rem;
            color: var(--muted);
            cursor: pointer;
        }

        .checkbox-label input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .helper-link {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.92rem;
        }

        .helper-link:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            height: 54px;
            border: none;
            border-radius: 16px;
            color: white;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            cursor: pointer;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 58%, #0ea5e9 100%);
            box-shadow: 0 14px 28px rgba(29, 78, 216, 0.22);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px rgba(29, 78, 216, 0.28);
            filter: brightness(1.02);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .bottom-links {
            display: grid;
            gap: 12px;
            margin-top: 22px;
            padding-top: 22px;
            border-top: 1px solid var(--border);
        }

        .register-link {
            text-align: center;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .register-link a {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 700;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .medico-btn {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 50px;
            padding: 0 18px;
            border-radius: 16px;
            background: #0f172a;
            color: white;
            text-decoration: none;
            font-weight: 700;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .medico-btn:hover {
            transform: translateY(-1px);
            background: #111c33;
            color: white;
        }

        .hint-line {
            margin-top: 12px;
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.55;
            text-align: center;
        }

        @media (max-width: 992px) {
            body.login-page {
                padding: 16px;
            }

            .auth-shell {
                grid-template-columns: 1fr;
            }

            .auth-hero {
                padding: 32px;
            }

            .auth-card {
                padding: 28px;
            }
        }

        @media (max-width: 576px) {
            body.login-page {
                padding: 0;
                align-items: stretch;
            }

            .auth-shell {
                gap: 0;
                border-radius: 0;
            }

            .auth-hero,
            .auth-card {
                border-radius: 0;
            }

            .auth-hero {
                padding: 26px 22px 24px;
            }

            .auth-card {
                padding: 24px 18px 28px;
            }

            .hero-stat-grid {
                grid-template-columns: 1fr;
            }

            .auth-card-top {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="auth-shell">
        <section class="auth-hero">
            <div class="hero-content">
                <div class="brand-pill">
                    <i class="fas fa-calendar-check"></i>
                    <span>Sistema de Citas Médicas</span>
                </div>

                <div class="hero-copy">
                    <h1>Accede a tu cuenta</h1>
                    <p>Consulta tus citas, administra tu información.</p>

                    <div class="hero-badges">
                        <span class="hero-badge"><i class="fas fa-user"></i> Pacientes</span>
                        <span class="hero-badge"><i class="fas fa-user-md"></i> Médicos</span>
                        <span class="hero-badge"><i class="fas fa-shield-halved"></i> Acceso seguro</span>
                    </div>
                </div>

                <div class="hero-stat-grid">
                    <div class="hero-stat">
                        <strong>1 clic</strong>
                        <span>para entrar al panel correcto</span>
                    </div>
                    <div class="hero-stat">
                        <strong>Más limpio</strong>
                        <span>sin ruido visual innecesario</span>
                    </div>
                    <div class="hero-stat">
                        <strong>Responsive</strong>
                        <span>funciona bien en móvil y escritorio</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="auth-card">
            <div class="auth-card-top">
                <div class="auth-title">
                    <h2>Iniciar sesión</h2>
                    <p>Ingresa con tu correo y contraseña para continuar.</p>
                </div>
                <div class="role-tag">
                    <i class="fas fa-circle-check"></i>
                    <span>Acceso principal</span>
                </div>
            </div>

            <?php if (!empty($error)) : ?>
                <div class="alert-custom alert-danger">
                    <i class="fas fa-triangle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['registro_exitoso'])) : ?>
                <div class="alert-custom alert-success">
                    <i class="fas fa-circle-check"></i>
                    <span><?php echo htmlspecialchars($_SESSION['registro_exitoso']); unset($_SESSION['registro_exitoso']); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" novalidate>
                <div class="form-stack">
                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <div class="input-group-custom">
                            <i class="fas fa-envelope field-icon"></i>
                            <input type="email" name="email" id="email" class="form-control-custom" placeholder="usuario@ejemplo.com" required value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="input-group-custom">
                            <i class="fas fa-lock field-icon"></i>
                            <input type="password" name="password" id="password" class="form-control-custom" placeholder="••••••••" required>
                            <i class="fas fa-eye password-toggle" id="togglePassword" aria-label="Mostrar u ocultar contraseña"></i>
                        </div>
                    </div>

                    <div class="form-meta">
                        <label class="checkbox-label">
                            <input type="checkbox" id="rememberMe">
                            <span>Recordarme</span>
                        </label>
                        <a class="helper-link" href="recuperar_password.php">Olvidaste tu contraseña</a>
                    </div>

                    <button type="submit" class="btn-login" id="submitBtn">
                        <i class="fas fa-right-to-bracket"></i> Iniciar sesión
                    </button>
                </div>
            </form>

            <div class="bottom-links">
                <div class="register-link">
                    ¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a>
                </div>

                <a href="medico_login.php" class="medico-btn">
                    <i class="fas fa-user-doctor"></i>
                    <span>Ingresar como médico</span>
                </a>
            </div>

            <div class="hint-line">
                Si eres paciente entrarás al panel general. Si tu usuario es médico, el sistema te enviará automáticamente a su panel correspondiente.
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const rememberMe = document.getElementById('rememberMe');
        const emailInput = document.getElementById('email');

        if (localStorage.getItem('rememberedEmail')) {
            emailInput.value = localStorage.getItem('rememberedEmail');
            rememberMe.checked = true;
        }

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        form.addEventListener('submit', function() {
            if (rememberMe.checked) {
                localStorage.setItem('rememberedEmail', emailInput.value);
            } else {
                localStorage.removeItem('rememberedEmail');
            }
        });

        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                return;
            }

            if (submitBtn.disabled) {
                e.preventDefault();
                return;
            }

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>