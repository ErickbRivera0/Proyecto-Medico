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
$acceso_restringido_expediente = isset($_GET['error']) && $_GET['error'] === 'solo_medico_expediente';

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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            overflow-x: hidden;
        }
        
        /* Navbar */
        .navbar-modern {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            box-shadow: 0 8px 32px rgba(29, 78, 216, 0.15);
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .navbar-modern .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
        }
        
        .navbar-modern .nav-link {
            color: rgba(255,255,255,0.85) !important;
            transition: color 0.3s, transform 0.2s;
            font-weight: 500;
            margin: 0 8px;
        }
        
        .navbar-modern .nav-link:hover {
            color: #fff !important;
            transform: translateY(-2px);
        }
        
        .navbar-modern .nav-link.active {
            color: #fff !important;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 8px;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 50%, #0284c7 100%);
            position: relative;
            overflow: hidden;
            padding: 100px 0;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: floatAnim 6s ease-in-out infinite;
        }
        
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
            animation: floatAnim 8s ease-in-out infinite reverse;
        }
        
        @keyframes floatAnim {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(30px); }
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            animation: slideDown 0.7s ease-out;
        }
        
        .hero-content p {
            font-size: 1.25rem;
            margin-bottom: 30px;
            opacity: 0.95;
            animation: fadeIn 0.9s ease-out 0.2s both;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .btn-hero {
            background: white;
            color: #0f172a;
            border: none;
            padding: 14px 40px;
            font-weight: 700;
            border-radius: 50px;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            animation: slideUp 0.9s ease-out 0.4s both;
        }
        
        .btn-hero:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.25);
            background: #f0f0f0;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Stats Cards */
        .stats-section {
            margin: -60px auto 60px;
            position: relative;
            z-index: 10;
            padding: 0 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 35px 25px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0,0,0,0.05);
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(29, 78, 216, 0.15);
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: white;
            transition: transform 0.3s;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #0f172a;
            margin: 15px 0;
        }
        
        .stat-label {
            font-size: 0.95rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Next Appointment */
        .appointment-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.5) 0%, rgba(29, 78, 216, 0.3) 100%);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 50px;
            color: #0f172a;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .appointment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(29, 78, 216, 0.15);
            border-color: rgba(2, 132, 199, 0.3);
        }
        
        .appointment-icon {
            font-size: 3rem;
            color: #0284c7;
            margin-bottom: 15px;
        }
        
        .appointment-title {
            font-size: 1.1rem;
            color: #0284c7;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .appointment-doctor {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 15px;
        }
        
        .appointment-detail {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 12px 0;
            color: #475569;
            font-weight: 500;
        }
        
        /* Specialties Section */
        .specialties-section {
            padding: 80px 0;
            background: linear-gradient(180deg, #f8f9fa 0%, #fff 100%);
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
            font-size: 2.5rem;
            font-weight: 800;
            color: #0f172a;
        }
        
        .section-title i {
            color: #0284c7;
            margin-right: 12px;
        }
        
        .specialty-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .specialty-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 50px rgba(29, 78, 216, 0.2);
        }
        
        .specialty-card img {
            height: 220px;
            object-fit: cover;
            transition: transform 0.4s;
        }
        
        .specialty-card:hover img {
            transform: scale(1.08);
        }
        
        .specialty-card-body {
            padding: 28px;
        }
        
        .specialty-card-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        
        .specialty-card-text {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .btn-specialty {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-specialty:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(29, 78, 216, 0.4);
            color: white;
        }
        
        /* Footer */
        .footer-modern {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            color: white;
            padding: 60px 0 20px;
            margin-top: 80px;
        }
        
        .footer-section h5 {
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .footer-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        
        .footer-link:hover {
            color: white;
            padding-left: 5px;
        }
        
        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            color: white;
            transition: all 0.3s;
            margin-right: 12px;
        }
        
        .social-icons a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 30px;
            margin-top: 40px;
            text-align: center;
            color: rgba(255,255,255,0.7);
        }
        
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.2rem;
            }
            .hero-content p {
                font-size: 1rem;
            }
            .hero-section {
                padding: 60px 0;
                min-height: 350px;
            }
            .section-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-modern sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-clinic-medical"></i> ClinicApp
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="agendar-cita.php">
                            <i class="fas fa-calendar-plus"></i> Agendar
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
                            <i class="fas fa-user-circle"></i> <?php echo substr(htmlspecialchars($_SESSION['usuario']), 0, 15); ?>
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

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container hero-content">
            <h1><i class="fas fa-heart-pulse"></i> ¡Hola, <?php echo htmlspecialchars($_SESSION['usuario']); ?>!</h1>
            <p>Tu bienestar es nuestra prioridad. Accede a atención médica de calidad con un solo click.</p>
            <a href="agendar-cita.php" class="btn btn-hero">
                <i class="fas fa-calendar-plus"></i> Agendar Mi Cita Ahora
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-label">Citas Activas</div>
                        <div class="stat-number"><?php echo $citas_activas; ?></div>
                        <p style="color: #94a3b8; font-size: 0.9rem; margin: 0;">Próximas programadas</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="stat-label">Total de Citas</div>
                        <div class="stat-number"><?php echo $total_citas; ?></div>
                        <p style="color: #94a3b8; font-size: 0.9rem; margin: 0;">Historial completo</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                        <div class="stat-label">Médicos Disponibles</div>
                        <div class="stat-number"><?php echo $total_medicos; ?></div>
                        <p style="color: #94a3b8; font-size: 0.9rem; margin: 0;">Profesionales listos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container" style="padding-top: 40px;">
        <?php if ($acceso_restringido_expediente): ?>
        <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 16px; padding: 16px 20px; margin-bottom: 30px; color: #92400e; display: flex; gap: 12px; align-items: start;">
            <i class="fas fa-lock" style="margin-top: 4px; font-size: 1.2rem;"></i>
            <div>
                <strong>Acceso restringido:</strong> solo el personal médico puede crear o editar expedientes clínicos.
            </div>
        </div>
        <?php endif; ?>

        <?php if ($proxima_cita): ?>
        <div class="appointment-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="appointment-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="appointment-title">Próxima Cita</div>
                    <div class="appointment-doctor"><?php echo htmlspecialchars($proxima_cita['medico_nombre']); ?></div>
                    
                    <div class="appointment-detail">
                        <i class="fas fa-stethoscope"></i>
                        <span><?php echo htmlspecialchars($proxima_cita['especialidad']); ?></span>
                    </div>
                    <div class="appointment-detail">
                        <i class="fas fa-note-medical"></i>
                        <span><?php echo htmlspecialchars($proxima_cita['motivo']); ?></span>
                    </div>
                    <div class="appointment-detail">
                        <i class="fas fa-calendar-day"></i>
                        <span><?php echo date('d/m/Y', strtotime($proxima_cita['fecha'])); ?> a las <?php echo $proxima_cita['hora']; ?></span>
                    </div>
                </div>
                <div class="col-md-4 text-md-end" style="margin-top: 20px;">
                    <a href="mis-citas.php" class="btn btn-specialty">
                        Ver Detalles <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div style="background: #dbeafe; border: 1px solid #93c5fd; border-radius: 16px; padding: 20px; margin-bottom: 40px; color: #1e40af;">
            <i class="fas fa-info-circle me-2"></i>
            No tienes citas programadas. <a href="agendar-cita.php" style="color: #0284c7; text-decoration: underline; font-weight: 600;">Agenda tu primera cita aquí</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Specialties Section -->
    <section class="specialties-section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-hospital"></i> Especialidades Médicas
            </h2>
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="specialty-card">
                        <img src="assets/img/cardiologia.jpg" alt="Cardiología">
                        <div class="specialty-card-body">
                            <h5 class="specialty-card-title">Cardiología</h5>
                            <p class="specialty-card-text">Cuidado integral del corazón y sistema circulatorio.</p>
                            <a href="agendar-cita.php?especialidad=Cardiologia" class="btn btn-specialty btn-sm">
                                Agendar <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="specialty-card">
                        <img src="assets/img/pediatria.jpg" alt="Pediatría">
                        <div class="specialty-card-body">
                            <h5 class="specialty-card-title">Pediatría</h5>
                            <p class="specialty-card-text">Atención especializada para niños y adolescentes.</p>
                            <a href="agendar-cita.php?especialidad=Pediatría" class="btn btn-specialty btn-sm">
                                Agendar <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="specialty-card">
                        <img src="assets/img/ginecologia.jpg" alt="Ginecología">
                        <div class="specialty-card-body">
                            <h5 class="specialty-card-title">Ginecología</h5>
                            <p class="specialty-card-text">Salud integral de la mujer en todas las etapas.</p>
                            <a href="agendar-cita.php?especialidad=Ginecología" class="btn btn-specialty btn-sm">
                                Agendar <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="specialty-card">
                        <img src="assets/img/medicina-general.jpg" alt="Medicina General">
                        <div class="specialty-card-body">
                            <h5 class="specialty-card-title">Medicina General</h5>
                            <p class="specialty-card-text">Consulta médica primaria y atención preventiva.</p>
                            <a href="agendar-cita.php?especialidad=Medicina General" class="btn btn-specialty btn-sm">
                                Agendar <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-modern">
        <div class="container">
            <div class="row">
                <div class="col-md-4 footer-section mb-4">
                    <h5><i class="fas fa-hospital me-2"></i> Contacto</h5>
                    <p><i class="fas fa-map-marker-alt me-2"></i> Tegucigalpa, Honduras</p>
                    <p><i class="fas fa-phone me-2"></i> +504 9999-9999</p>
                    <p><i class="fas fa-envelope me-2"></i> contacto@citasmedicas.com</p>
                </div>

                <div class="col-md-4 footer-section mb-4">
                    <h5><i class="fas fa-link me-2"></i> Enlaces Rápidos</h5>
                    <a href="agendar-cita.php" class="footer-link"><i class="fas fa-angle-right me-2"></i> Agendar Cita</a>
                    <a href="mis-citas.php" class="footer-link"><i class="fas fa-angle-right me-2"></i> Mis Citas</a>
                    <a href="medicos.php" class="footer-link"><i class="fas fa-angle-right me-2"></i> Médicos</a>
                    <a href="perfil.php" class="footer-link"><i class="fas fa-angle-right me-2"></i> Mi Perfil</a>
                </div>

                <div class="col-md-4 footer-section mb-4">
                    <h5><i class="fas fa-share-alt me-2"></i> Síguenos</h5>
                    <p style="font-size: 0.95rem;">Síguenos en redes sociales para promociones y novedades.</p>
                    <div class="social-icons">
                        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Sistema de Citas Médicas. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>