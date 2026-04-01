<?php
session_start();
require_once '../config.php';

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Obtener estadísticas
$total_medicos = $conn->query("SELECT COUNT(*) as total FROM medicos")->fetch_assoc()['total'];
$total_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'usuario'")->fetch_assoc()['total'];
$total_admin = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'admin'")->fetch_assoc()['total'];
$total_citas = $conn->query("SELECT COUNT(*) as total FROM citas")->fetch_assoc()['total'];
$citas_hoy = $conn->query("SELECT COUNT(*) as total FROM citas WHERE fecha = CURDATE() AND estado != 'cancelada'")->fetch_assoc()['total'];
$citas_pendientes = $conn->query("SELECT COUNT(*) as total FROM citas WHERE estado = 'pendiente'")->fetch_assoc()['total'];

// Obtener próximas citas (próximos 7 días)
$proximas_citas = $conn->query("
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
        .admin-sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .admin-sidebar .logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .admin-sidebar .logo h2 {
            color: white;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .admin-sidebar .logo p {
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
            margin: 5px 0 0;
        }
        
        .admin-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .admin-menu li {
            margin-bottom: 5px;
        }
        
        .admin-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .admin-menu a:hover,
        .admin-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #3498db;
        }
        
        .admin-menu a i {
            width: 20px;
            font-size: 1.1rem;
        }
        
        .admin-content {
            padding: 20px 30px;
            background: #f5f6fa;
            min-height: 100vh;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e1e8ed;
        }
        
        .admin-header h1 {
            margin: 0;
            font-size: 1.8rem;
            color: #2c3e50;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info span {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card-admin {
            background: white;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .stat-card-admin:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-info h3 {
            margin: 0;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 5px 0 0;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .recent-table {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .recent-table h3 {
            margin: 0 0 20px;
            color: #2c3e50;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-pendiente { background: #fff3cd; color: #856404; }
        .badge-confirmada { background: #d4edda; color: #155724; }
        .badge-completada { background: #d1ecf1; color: #0c5460; }
        .badge-cancelada { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                min-height: auto;
            }
            .admin-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
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
                            <?php if($proximas_citas->num_rows > 0): ?>
                                <?php while($cita = $proximas_citas->fetch_assoc()): ?>
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