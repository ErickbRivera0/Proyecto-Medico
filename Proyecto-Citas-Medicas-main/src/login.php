<?php

session_start();
require_once 'config.php';


$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {

        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ? AND estado = 'activo'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {

            if (password_verify($password, $user['password'])) {

                // Guardar sesión completa
                $_SESSION['usuario'] = $user['nombre'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_time'] = time();

                // Redirección según el rol
                if ($user['rol'] === 'admin') {
                    header("Location: gestionar.php");
                } else {
                    header("Location: index.php");
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
    <title>Login - Sistema de Citas Médicas</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .login-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group-custom i {
            position: absolute;
            left: 15px;
            color: #999;
            font-size: 16px;
        }

        .form-control-custom {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .register-link a:hover {
            color: #764ba2;
        }

        .alert-custom {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .alert-danger {
            background-color: #fee;
            border-left: 4px solid #f44336;
            color: #d32f2f;
        }

        .alert-success {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            color: #2e7d32;
        }

        .alert i {
            font-size: 18px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
            color: #666;
        }

        .checkbox-label input {
            cursor: pointer;
        }

        .forgot-password {
            font-size: 14px;
            color: #667eea;
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .login-body {
                padding: 30px 20px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="fas fa-calendar-check"></i> Bienvenido</h2>
                <p>Sistema de Citas Médicas</p>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error)) : ?>
                    <div class="alert-custom alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['registro_exitoso'])) : ?>
                    <div class="alert-custom alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $_SESSION['registro_exitoso']; unset($_SESSION['registro_exitoso']); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Correo Electrónico</label>
                        <div class="input-group-custom">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-control-custom" 
                                   placeholder="usuario@ejemplo.com" required 
                                   value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Contraseña</label>
                        <div class="input-group-custom">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" 
                                   class="form-control-custom" placeholder="••••••••" required>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                    </div>

                    <div class="remember-me">
                        <label class="checkbox-label">
                            <input type="checkbox" id="rememberMe">
                            <span>Recordarme</span>
                        </label>
                        <a href="recuperar_password.php" class="forgot-password">
                            <i class="fas fa-key"></i> ¿Olvidaste tu contraseña?
                        </a>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>
                </form>

                <div class="register-link">
                    <span>¿No tienes una cuenta?</span>
                    <a href="registro.php">
                        <i class="fas fa-user-plus"></i> Regístrate aquí
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Remember me functionality
        const rememberMe = document.getElementById('rememberMe');
        const emailInput = document.querySelector('input[name="email"]');
        
        // Load saved email if exists
        if (localStorage.getItem('rememberedEmail')) {
            emailInput.value = localStorage.getItem('rememberedEmail');
            rememberMe.checked = true;
        }
        
        // Save email when form is submitted
        document.getElementById('loginForm').addEventListener('submit', function() {
            if (rememberMe.checked) {
                localStorage.setItem('rememberedEmail', emailInput.value);
            } else {
                localStorage.removeItem('rememberedEmail');
            }
        });

        // Add loading effect on submit
        const form = document.getElementById('loginForm');
        const submitBtn = document.querySelector('.btn-login');
        
        form.addEventListener('submit', function(e) {
            if (form.checkValidity()) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
                submitBtn.disabled = true;
            }
        });

        // Prevent multiple submissions
        let submitted = false;
        form.addEventListener('submit', function(e) {
            if (submitted) {
                e.preventDefault();
            } else {
                submitted = true;
                setTimeout(() => { submitted = false; }, 3000);
            }
        });
    </script>
</body>
</html>