<?php
session_start();
require_once 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {

        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {

            if (password_verify($password, $user['password'])) {

                // Guardar sesión
                $_SESSION['usuario'] = $user['nombre'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['id'] = $user['id'];

                header("Location: index.php");
                exit();

            } else {
                $error = "❌ Contraseña incorrecta";
            }

        } else {
            $error = "❌ Usuario no encontrado";
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
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/global.css">
</head>
<body>

<div class="container">
    <h2>Iniciar Sesión</h2>

    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="form-cita">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" required>
        </div>

        <button class="btn btn-primary">Ingresar</button>
    </form>

    <br>
    <a href="registro.php">Crear cuenta</a>
</div>

</body>
</html>