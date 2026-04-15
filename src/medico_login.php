<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Medico.php';

// Verificar que la clase existe
if (!class_exists('Medico')) {
    die('Error: Clase Medico no encontrada');
}

// Verificar si la tabla medicos tiene el campo password y crear médico de prueba si no existe
try {
    // Intentar agregar la columna password (si ya existe, ignorar el error)
    $pdo->exec("ALTER TABLE medicos ADD COLUMN password VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {
    // La columna ya existe o hay otro error, continuar
}

try {
    // Crear médico de prueba solo si no existe; evita duplicados en cada carga del login
    $nombreMedico = 'Dr. Carlos Gonzalez';
    $especialidadMedico = 'Cardiologia';
    $telefonoMedico = '504-2234-5678';
    $emailMedico = 'carlos@clinica.com';
    $hash = password_hash('123456', PASSWORD_DEFAULT);

    $check = $pdo->prepare("SELECT id, password FROM medicos WHERE email = ? ORDER BY id ASC");
    $check->bindParam(1, $emailMedico);
    $check->execute();
    $medicosExistentes = $check->fetchAll(PDO::FETCH_ASSOC);
    $medicoExistente = $medicosExistentes[0] ?? null;

    if (!$medicoExistente) {
        $stmt = $pdo->prepare("INSERT INTO medicos (nombre, especialidad, telefono, email, password)
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->bindParam(1, $nombreMedico);
        $stmt->bindParam(2, $especialidadMedico);
        $stmt->bindParam(3, $telefonoMedico);
        $stmt->bindParam(4, $emailMedico);
        $stmt->bindParam(5, $hash);
        $stmt->execute();
    } elseif (empty($medicoExistente['password'])) {
        // Si el registro existe sin password, establecer una para no romper el acceso
        $upd = $pdo->prepare("UPDATE medicos SET password = ? WHERE id = ?");
        $upd->bindParam(1, $hash);
        $upd->bindParam(2, $medicoExistente['id'], PDO::PARAM_INT);
        $upd->execute();
    }

    // Limpieza dirigida de duplicados del médico de prueba
    if (count($medicosExistentes) > 1) {
        $idPrincipal = (int)$medicosExistentes[0]['id'];
        $idsDuplicados = [];
        for ($i = 1; $i < count($medicosExistentes); $i++) {
            $idsDuplicados[] = (int)$medicosExistentes[$i]['id'];
        }

        foreach ($idsDuplicados as $idDuplicado) {
            try {
                $qCitas = $pdo->prepare("UPDATE citas SET medico_id = ? WHERE medico_id = ?");
                $qCitas->bindParam(1, $idPrincipal, PDO::PARAM_INT);
                $qCitas->bindParam(2, $idDuplicado, PDO::PARAM_INT);
                $qCitas->execute();
            } catch (Exception $e) {
                // Ignorar si la tabla aún no existe o no aplica
            }

            try {
                $qConsultas = $pdo->prepare("UPDATE expediente_consultas SET medico_id = ? WHERE medico_id = ?");
                $qConsultas->bindParam(1, $idPrincipal, PDO::PARAM_INT);
                $qConsultas->bindParam(2, $idDuplicado, PDO::PARAM_INT);
                $qConsultas->execute();
            } catch (Exception $e) {
                // Ignorar si la tabla aún no existe o no aplica
            }

            $qDelete = $pdo->prepare("DELETE FROM medicos WHERE id = ?");
            $qDelete->bindParam(1, $idDuplicado, PDO::PARAM_INT);
            $qDelete->execute();
        }
    }

} catch (Exception $e) {
    // Log del error pero continuar
    error_log("Error configurando médico de prueba: " . $e->getMessage());
}

$medico_model = new Medico($pdo);

$error = "";
$mensaje = isset($_GET['reset']) && $_GET['reset'] === '1'
    ? 'Contrasena actualizada correctamente. Inicia sesion con tu nueva clave.'
    : '';
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $user = $medico_model->login($email, $password);

            if ($user) {
                // Guardar sesión
                $_SESSION['usuario'] = $user['nombre'];
                $_SESSION['rol'] = 'medico';
                $_SESSION['id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_time'] = time();

                header("Location: medico_panel.php");
                exit();
            } else {
                $error = "❌ Email o contraseña incorrecta";
            }
        } catch (Exception $e) {
            error_log("Error en login médico: " . $e->getMessage());
            $error = "❌ Error interno del servidor";
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
    <title>Login Médico - Sistema de Citas Médicas</title>
    
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
            color: #667eea;
            font-size: 18px;
        }

        .input-group-custom input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .input-group-custom input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .login-footer {
            text-align: center;
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #e1e5e9;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="fas fa-user-md"></i> Login Médico</h2>
                <p>Accede a tu panel de control</p>
            </div>
            <div class="login-body">
                <?php if ($mensaje): ?>
                    <div class="alert" style="background: #ecfdf3; color: #0f5132; border: 1px solid #b7f0cc;"><?php echo htmlspecialchars($mensaje); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <div class="input-group-custom">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="tu@email.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="input-group-custom">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Tu contraseña" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-login">Iniciar Sesión</button>
                    <div style="margin-top: 12px; text-align: right;">
                        <a href="recuperar_password.php?tipo=medico" style="color: #667eea; text-decoration: none; font-weight: 500;">Recuperar contraseña</a>
                    </div>
                </form>
            </div>
            <div class="login-footer">
                <p><a href="login.php">¿Eres paciente? Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>
</body>
</html>