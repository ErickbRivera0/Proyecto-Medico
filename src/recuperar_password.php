<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security_helper.php';

$tipo = $_GET['tipo'] ?? ($_POST['tipo'] ?? 'usuario');
$tipo = ($tipo === 'medico') ? 'medico' : 'usuario';

$email = '';
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $nueva_password = $_POST['nueva_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    if ($email === '' || $nueva_password === '' || $confirmar_password === '') {
        $error = 'Completa todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo valido.';
    } elseif ($nueva_password !== $confirmar_password) {
        $error = 'Las contrasenas no coinciden.';
    } else {
        $validacion = function_exists('validarContraseña')
            ? validarContraseña($nueva_password)
            : ['valida' => true, 'errores' => []];

        if (!$validacion['valida']) {
            $error = 'Contrasena debil: ' . implode(' ', $validacion['errores']);
        } else {
            try {
                $hash = password_hash($nueva_password, PASSWORD_DEFAULT);

                if ($tipo === 'medico') {
                    $stmt = $pdo->prepare('UPDATE medicos SET password = ? WHERE email = ? LIMIT 1');
                } else {
                    $stmt = $pdo->prepare('UPDATE usuarios SET password = ? WHERE email = ? LIMIT 1');
                }

                $stmt->bindParam(1, $hash);
                $stmt->bindParam(2, $email);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $destino = ($tipo === 'medico') ? 'medico_login.php?reset=1' : 'login.php';
                    $mensaje = 'Contrasena actualizada correctamente. Ya puedes iniciar sesion.';
                    $_SESSION['registro_exitoso'] = $mensaje;
                    header('Location: ' . $destino);
                    exit();
                }

                $error = 'No se encontro una cuenta con ese correo para el tipo seleccionado.';
            } catch (Exception $e) {
                error_log('Error en recuperacion de contrasena: ' . $e->getMessage());
                $error = 'Ocurrio un error al actualizar la contrasena.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contrasena</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #f0f6ff 0%, #dbeafe 100%);
        }

        .card-recovery {
            width: 100%;
            max-width: 520px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 18px 40px rgba(30, 64, 175, 0.15);
            padding: 28px;
        }

        .title {
            margin-bottom: 6px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e3a8a;
        }

        .subtitle {
            color: #475569;
            margin-bottom: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            font-weight: 600;
        }

        .links {
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .links a {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 600;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="card-recovery">
        <h1 class="title"><i class="fas fa-key"></i> Recuperar contrasena</h1>
        <p class="subtitle">Actualiza tu contrasena ingresando tu correo.</p>

        <?php if ($mensaje !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipo); ?>">

            <div class="mb-3">
                <label class="form-label" for="tipo_visual">Tipo de cuenta</label>
                <select id="tipo_visual" class="form-select" onchange="changeTipo(this.value)">
                    <option value="usuario" <?php echo $tipo === 'usuario' ? 'selected' : ''; ?>>Paciente/Usuario</option>
                    <option value="medico" <?php echo $tipo === 'medico' ? 'selected' : ''; ?>>Medico</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label" for="email">Correo electronico</label>
                <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label" for="nueva_password">Nueva contrasena</label>
                <input type="password" id="nueva_password" name="nueva_password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label" for="confirmar_password">Confirmar nueva contrasena</label>
                <input type="password" id="confirmar_password" name="confirmar_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Actualizar contrasena</button>
        </form>

        <div class="links">
            <a href="login.php">Ir a login de pacientes</a>
            <a href="medico_login.php">Ir a login de medicos</a>
        </div>
    </div>

    <script>
        function changeTipo(tipo) {
            const url = new URL(window.location.href);
            url.searchParams.set('tipo', tipo);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
