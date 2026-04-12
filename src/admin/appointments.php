<?php
session_start();
require_once '../config.php';

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$mensaje = "";
$error = "";

// Procesar cambio de estado de cita
if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $id = $_POST['id'];
    $estado = $_POST['estado'];
    
    $stmt = $pdo->prepare("UPDATE citas SET estado=? WHERE id=?");
    $stmt->bindParam(1, $estado);
    $stmt->bindParam(2, $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Estado de la cita actualizado";
    } else {
        $error = "❌ Error al actualizar estado";
    }
}

// Obtener todas las citas
$citas = $pdo->query("
    SELECT c.*, m.nombre as medico_nombre, m.especialidad 
    FROM citas c 
    JOIN medicos m ON c.medico_id = m.id 
    ORDER BY c.fecha DESC, c.hora DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Citas - Admin</title>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --ink: #0f172a;
            --blue: #1d4ed8;
            --cyan: #0ea5e9;
            --surface: rgba(255, 255, 255, 0.9);
            --border: rgba(226, 232, 240, 0.95);
            --muted: #64748b;
        }

        * { box-sizing: border-box; }

        body.admin-page {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(29, 78, 216, 0.10), transparent 28%),
                radial-gradient(circle at top right, rgba(14, 165, 233, 0.10), transparent 24%),
                linear-gradient(180deg, #f8fbff 0%, #edf4ff 100%);
            color: var(--ink);
        }

        .admin-sidebar {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 58%, #0ea5e9 100%);
            min-height: 100vh;
            padding: 18px 0;
            box-shadow: 16px 0 40px rgba(15, 23, 42, 0.12);
        }

        .admin-sidebar .logo {
            text-align: center;
            padding: 18px 20px 22px;
            margin-bottom: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        .admin-sidebar .logo h2,
        .admin-sidebar .logo p { color: #fff; margin: 0; }

        .admin-sidebar .logo h2 {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .admin-sidebar .logo p {
            margin-top: 6px;
            color: rgba(255,255,255,0.8);
            font-size: 0.85rem;
        }

        .admin-menu {
            list-style: none;
            padding: 0 10px;
            margin: 0;
        }

        .admin-menu li { margin-bottom: 6px; }

        .admin-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255,255,255,0.82);
            text-decoration: none;
            border-radius: 16px;
            transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease;
        }

        .admin-menu a:hover,
        .admin-menu a.active {
            background: rgba(255,255,255,0.12);
            color: #fff;
            transform: translateX(2px);
        }

        .admin-content {
            padding: 28px;
            background: transparent;
            min-height: 100vh;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding: 18px 22px;
            background: rgba(255,255,255,0.76);
            backdrop-filter: blur(14px);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .admin-header h1 {
            margin: 0;
            font-size: 1.7rem;
            color: var(--ink);
            letter-spacing: -0.03em;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .badge-pendiente { background: #fef3c7; color: #92400e; }
        .badge-confirmada { background: #dbeafe; color: #1d4ed8; }
        .badge-completada { background: #dcfce7; color: #166534; }
        .badge-cancelada { background: #fee2e2; color: #991b1b; }

        .status-select {
            min-height: 38px;
            padding: 8px 12px;
            border-radius: 12px;
            border: 1px solid #dbe4f0;
            background: #fff;
            cursor: pointer;
        }

        .recent-table {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: #eff6ff;
        }

        .table th,
        .table td {
            vertical-align: middle;
            padding: 14px 12px;
            border-color: #e2e8f0;
        }

        .alert {
            border-radius: 16px;
            border: 1px solid transparent;
        }

        @media (max-width: 768px) {
            .admin-sidebar { min-height: auto; }
            .admin-content { padding: 16px; }
            .admin-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body class="admin-page">
    <div style="display: flex; flex-wrap: wrap;">
        <!-- Sidebar -->
        <div class="admin-sidebar" style="width: 260px;">
            <div class="logo">
                <h2><i class="fas fa-calendar-check"></i> Admin Panel</h2>
                <p>Sistema de Citas Médicas</p>
            </div>
            <ul class="admin-menu">
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="doctors.php"><i class="fas fa-user-md"></i> Médicos</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="appointments.php" class="active"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content" style="flex: 1;">
            <div class="admin-header">
                <h1><i class="fas fa-calendar-alt"></i> Gestión de Citas</h1>
            </div>
            
            <?php if($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="recent-table">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                             <tr>
                                <th>ID</th>
                                <th>Paciente</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Médico</th>
                                <th>Especialidad</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($citas->rowCount() > 0): ?>
                                <?php while($cita = $citas->fetch()): ?>
                                <tr>
                                    <td><?php echo $cita['id']; ?></td>
                                    <td><?php echo htmlspecialchars($cita['paciente_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['paciente_email']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['paciente_telefono']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['medico_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['especialidad']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></td>
                                    <td><?php echo $cita['hora']; ?></td>
                                    <td><?php echo htmlspecialchars(substr($cita['motivo'], 0, 30)); ?>...</td>
                                    <td>
                                        <span class="badge badge-<?php echo $cita['estado']; ?>">
                                            <?php echo ucfirst($cita['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="" style="display: inline-block;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo $cita['id']; ?>">
                                            <select name="estado" class="status-select" onchange="this.form.submit()">
                                                <option value="pendiente" <?php echo $cita['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                <option value="confirmada" <?php echo $cita['estado'] == 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                                                <option value="completada" <?php echo $cita['estado'] == 'completada' ? 'selected' : ''; ?>>Completada</option>
                                                <option value="cancelada" <?php echo $cita['estado'] == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" style="text-align: center;">No hay citas registradas</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>