<?php
session_start();
require_once '../config.php';

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Obtener estadísticas
$total_medicos = $pdo->query("SELECT COUNT(*) as total FROM medicos")->fetch()['total'];
$total_usuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'usuario'")->fetch()['total'];
$total_admin = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'admin'")->fetch()['total'];
$total_citas = $pdo->query("SELECT COUNT(*) as total FROM citas")->fetch()['total'];
$citas_hoy = $pdo->query("SELECT COUNT(*) as total FROM citas WHERE fecha = CURDATE() AND estado != 'cancelada'")->fetch()['total'];
$citas_pendientes = $pdo->query("SELECT COUNT(*) as total FROM citas WHERE estado = 'pendiente'")->fetch()['total'];

// Obtener próximas citas (próximos 7 días)
$proximas_citas = $pdo->query("
    SELECT c.*, m.nombre as medico_nombre, u.nombre as paciente_nombre 
    FROM citas c 
    JOIN medicos m ON c.medico_id = m.id 
    JOIN usuarios u ON c.paciente_email = u.email
    WHERE c.fecha >= CURDATE() AND c.fecha <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY c.fecha ASC, c.hora ASC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Sistema de Citas Médicas</title>
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

        * {
            box-sizing: border-box;
        }

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
        .admin-sidebar .logo p {
            margin: 0;
            color: #fff;
        }

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

        .admin-menu li {
            margin-bottom: 6px;
        }

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

        .admin-menu a i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
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

        .user-info {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 999px;
            color: #1d4ed8;
            font-weight: 700;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card-admin {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card-admin:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 34px rgba(29, 78, 216, 0.12);
        }

        .stat-info h3 {
            margin: 0;
            font-size: 0.86rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--ink);
            margin: 6px 0 0;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--blue), var(--cyan));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 12px 24px rgba(29, 78, 216, 0.22);
        }

        .recent-table {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .recent-table h3 {
            margin: 0 0 16px;
            color: var(--ink);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table.table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        table.table thead {
            background: #eff6ff;
        }

        table.table th,
        table.table td {
            padding: 14px 12px;
            border-color: #e2e8f0;
            vertical-align: middle;
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

        @media (max-width: 768px) {
            .admin-sidebar {
                min-height: auto;
            }

            .admin-content {
                padding: 16px;
            }

            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body class="admin-page">
    <div class="container-fluid" style="display: flex; flex-wrap: wrap;">
        <!-- Sidebar -->
        <div class="admin-sidebar" style="width: 260px;">
            <div class="logo">
                <h2><i class="fas fa-calendar-check"></i> Admin Panel</h2>
                <p>Sistema de Citas Médicas</p>
            </div>
            <ul class="admin-menu">
                <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="doctors.php"><i class="fas fa-user-md"></i> Médicos</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content" style="flex: 1;">
            <div class="admin-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div class="user-info">
                    <i class="fas fa-user-circle" style="font-size: 1.5rem;"></i>
                    <span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="stat-grid">
                <div class="stat-card-admin">
                    <div class="stat-info">
                        <h3>Total Médicos</h3>
                        <p class="stat-number"><?php echo $total_medicos; ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                </div>
                
                <div class="stat-card-admin">
                    <div class="stat-info">
                        <h3>Usuarios Registrados</h3>
                        <p class="stat-number"><?php echo $total_usuarios; ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                
                <div class="stat-card-admin">
                    <div class="stat-info">
                        <h3>Total Citas</h3>
                        <p class="stat-number"><?php echo $total_citas; ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                
                <div class="stat-card-admin">
                    <div class="stat-info">
                        <h3>Citas Pendientes</h3>
                        <p class="stat-number"><?php echo $citas_pendientes; ?></p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            
            <!-- Próximas Citas -->
            <div class="recent-table">
                <h3><i class="fas fa-calendar-week"></i> Próximas Citas (7 días)</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                             <tr>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($proximas_citas->rowCount() > 0): ?>
                                <?php while($cita = $proximas_citas->fetch()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cita['paciente_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['medico_nombre']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></td>
                                    <td><?php echo $cita['hora']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $cita['estado']; ?>">
                                            <?php echo ucfirst($cita['estado']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No hay citas programadas en los próximos 7 días</td>
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