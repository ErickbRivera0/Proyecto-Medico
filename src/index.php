<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Verificar tiempo de sesión (8 horas máximo)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 28800)) {
    session_destroy();
    header("Location: login.php?error=session_expired");
    exit();
}

// Obtener estadísticas del usuario desde la base de datos
require_once 'config.php';
$user_email = $_SESSION['email'];
$user_nombre = $_SESSION['usuario'];

// Contar citas activas del usuario
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM citas WHERE paciente_email = ? AND fecha >= CURDATE() AND estado != 'cancelada'");
$stmt->bindParam(1, $user_email);
$stmt->execute();
$citas_activas = $stmt->fetch()['total'];

// Contar total de citas
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM citas WHERE paciente_email = ?");
$stmt->bindParam(1, $user_email);
$stmt->execute();
$total_citas = $stmt->fetch()['total'];

// Obtener próxima cita
$stmt = $pdo->prepare("SELECT c.fecha, c.hora, c.motivo, m.nombre as medico_nombre, m.especialidad 
                        FROM citas c 
                        JOIN medicos m ON c.medico_id = m.id 
                        WHERE c.paciente_email = ? AND c.fecha >= CURDATE() AND c.estado != 'cancelada' 
                        ORDER BY c.fecha ASC, c.hora ASC LIMIT 1");
$stmt->bindParam(1, $user_email);
$stmt->execute();
$proxima_cita = $stmt->fetch();

// Contar médicos activos
$result = $pdo->query("SELECT COUNT(*) as total FROM medicos");
$total_medicos = $result->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Citas Médicas - Bienvenido <?php echo htmlspecialchars($_SESSION['usuario']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Hero con imagen de fondo */
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://www.shutterstock.com/image-vector/diverse-group-medical-professionals-including-260nw-2740671255.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 120px 0;
            margin-bottom: 50px;
            position: relative;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.3) 0%, rgba(118, 75, 162, 0.3) 100%);
        }
        
        .hero .container {
            position: relative;
            z-index: 2;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }
        
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            color: #0d6efd;
            margin: 15px 0;
        }
        
        .stat-icon {
            font-size: 40px;
            color: #0d6efd;
            margin-bottom: 15px;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            color: white;
        }
        
        .next-appointment-card {
            border-left: 4px solid #0d6efd;
            transition: all 0.3s;
            background: white;
            border-radius: 10px;
        }
        
        .next-appointment-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .footer-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-link:hover {
            color: white;
            text-decoration: underline;
        }
        
        .logo-navbar {
            height: 40px;
            border-radius: 50%;
        }
        
        .specialty-card {
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            overflow: hidden;
        }
        
        .specialty-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .specialty-card img {
            transition: transform 0.3s;
        }
        
        .specialty-card:hover img {
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .hero {
                padding: 60px 0;
                background-attachment: scroll;
            }
            .stat-number {
                font-size: 32px;
            }
        }
        
        /* Animaciones */
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
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/img/Logo1.jpg" alt="Logo" class="logo-navbar">
                <span class="ms-2">Citas Médicas</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="agendar-cita.php">
                            <i class="fas fa-calendar-plus"></i> Agendar Cita
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="mis-citas.php">
                            <i class="fas fa-calendar-check"></i> Mis Citas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="medicos.php">
                            <i class="fas fa-user-md"></i> Médicos
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['usuario']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section con imagen mejorada -->
    <div class="hero text-white text-center">
        <div class="container">
            <h1 class="display-4 fw-bold fade-in-up">¡Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?>!</h1>
            <p class="lead mt-3 fade-in-up" style="animation-delay: 0.2s;">Tu salud es nuestra prioridad. Agenda tu cita médica de forma rápida y segura.</p>
            <a href="agendar-cita.php" class="btn btn-light btn-lg mt-4 fade-in-up" style="animation-delay: 0.4s;">
                <i class="fas fa-calendar-plus"></i> Agendar Cita Ahora
            </a>
        </div>
    </div>

    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-banner">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><i class="fas fa-heartbeat"></i> Tu bienestar es lo más importante</h3>
                            <p class="mb-0">En nuestro sistema, te ofrecemos atención médica de calidad con los mejores profesionales.</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="medicos.php" class="btn btn-light">
                                <i class="fas fa-user-md"></i> Ver Médicos Disponibles
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-md-4 mb-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h5>Citas Activas</h5>
                    <div class="stat-number"><?php echo $citas_activas; ?></div>
                    <p class="text-muted mb-0">Próximas citas programadas</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h5>Total de Citas</h5>
                    <div class="stat-number"><?php echo $total_citas; ?></div>
                    <p class="text-muted mb-0">Historial completo</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h5>Médicos Disponibles</h5>
                    <div class="stat-number"><?php echo $total_medicos; ?></div>
                    <p class="text-muted mb-0">Profesionales listos para atenderte</p>
                </div>
            </div>
        </div>

        <?php if ($proxima_cita): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card next-appointment-card shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h5 class="card-title text-primary">
                                    <i class="fas fa-clock"></i> Próxima Cita
                                </h5>
                                <h4><?php echo htmlspecialchars($proxima_cita['medico_nombre']); ?></h4>
                                <p class="mb-1">
                                    <i class="fas fa-stethoscope"></i> 
                                    Especialidad: <?php echo htmlspecialchars($proxima_cita['especialidad']); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-notes-medical"></i> 
                                    Motivo: <?php echo htmlspecialchars($proxima_cita['motivo']); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-calendar-day"></i> 
                                    Fecha: <?php echo date('d/m/Y', strtotime($proxima_cita['fecha'])); ?> - 
                                    <i class="fas fa-clock"></i> Hora: <?php echo $proxima_cita['hora']; ?>
                                </p>
                            </div>
                            <div class="mt-3 mt-md-0">
                                <a href="mis-citas.php" class="btn btn-outline-primary">
                                    Ver Detalles <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No tienes citas programadas. 
                    <a href="agendar-cita.php" class="alert-link">Agenda tu primera cita aquí</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Especialidades Médicas con imágenes locales -->
    <section class="container my-5">
        <h2 class="text-center mb-4">
            <i class="fas fa-stethoscope"></i> Especialidades Médicas
        </h2>
        <div class="row g-4">
            <div class="col-md-3 col-sm-6">
                <div class="card specialty-card text-center h-100 shadow-sm">
                    <img src="assets/img/cardiologia.jpg" class="card-img-top" alt="Cardiología" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title">Cardiología</h5>
                        <p class="card-text">Cuidado integral del corazón y sistema circulatorio.</p>
                        <a href="agendar-cita.php?especialidad=Cardiología" class="btn btn-outline-primary btn-sm">
                            Agendar Cita <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card specialty-card text-center h-100 shadow-sm">
                    <img src="assets/img/pediatria.jpg" class="card-img-top" alt="Pediatría" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title">Pediatría</h5>
                        <p class="card-text">Atención médica especializada para niños y adolescentes.</p>
                        <a href="agendar-cita.php?especialidad=Pediatría" class="btn btn-outline-primary btn-sm">
                            Agendar Cita <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card specialty-card text-center h-100 shadow-sm">
                    <img src="assets/img/ginecologia.jpg" class="card-img-top" alt="Ginecología" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title">Ginecología</h5>
                        <p class="card-text">Salud integral de la mujer en todas las etapas.</p>
                        <a href="agendar-cita.php?especialidad=Ginecología" class="btn btn-outline-primary btn-sm">
                            Agendar Cita <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card specialty-card text-center h-100 shadow-sm">
                    <img src="assets/img/medicina-general.jpg" class="card-img-top" alt="Medicina General" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title">Medicina General</h5>
                        <p class="card-text">Consulta médica primaria y atención preventiva.</p>
                        <a href="agendar-cita.php?especialidad=Medicina General" class="btn btn-outline-primary btn-sm">
                            Agendar Cita <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white pt-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-hospital"></i> Contacto</h5>
                    <p><i class="fas fa-map-marker-alt"></i> Tegucigalpa, Honduras</p>
                    <p><i class="fas fa-phone"></i> +504 9999-9999</p>
                    <p><i class="fas fa-envelope"></i> contacto@citasmedicas.com</p>
                </div>

                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-link"></i> Enlaces Rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="agendar-cita.php" class="footer-link"><i class="fas fa-angle-right"></i> Agendar Cita</a></li>
                        <li><a href="mis-citas.php" class="footer-link"><i class="fas fa-angle-right"></i> Mis Citas</a></li>
                        <li><a href="medicos.php" class="footer-link"><i class="fas fa-angle-right"></i> Médicos</a></li>
                        <li><a href="perfil.php" class="footer-link"><i class="fas fa-angle-right"></i> Mi Perfil</a></li>
                    </ul>
                </div>

                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-share-alt"></i> Síguenos</h5>
                    <p>Síguenos en nuestras redes sociales para estar al tanto de promociones y novedades.</p>
                    <div class="fs-4">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-whatsapp"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>

            <hr class="border-light">
            <div class="text-center pb-3">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Sistema de Citas Médicas. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>