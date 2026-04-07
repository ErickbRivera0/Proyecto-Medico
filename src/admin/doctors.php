<?php
session_start();
require_once '../config.php';

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Garantizar que la tabla medicos tenga columna password
try {
    $pdo->exec("ALTER TABLE medicos ADD COLUMN password VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {
    // Ignorar si ya existe
}

$mensaje = "";
$error = "";

// Procesar formulario de agregar/editar médico
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $nombre = trim($_POST['nombre']);
            $especialidad = trim($_POST['especialidad']);
            $telefono = trim($_POST['telefono']);
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            
            if (!empty($nombre) && !empty($especialidad) && !empty($telefono) && !empty($email) && !empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO medicos (nombre, especialidad, telefono, email, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->bindParam(1, $nombre);
                $stmt->bindParam(2, $especialidad);
                $stmt->bindParam(3, $telefono);
                $stmt->bindParam(4, $email);
                $stmt->bindParam(5, $hashed_password);
                
                if ($stmt->execute()) {
                    $mensaje = "✅ Médico agregado exitosamente";
                } else {
                    $error = "❌ Error al agregar médico";
                }
            } else {
                $error = "⚠️ Todos los campos son obligatorios, incluida la contraseña";
            }
        } 
        elseif ($action == 'edit') {
            $id = $_POST['id'];
            $nombre = trim($_POST['nombre']);
            $especialidad = trim($_POST['especialidad']);
            $telefono = trim($_POST['telefono']);
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE medicos SET nombre=?, especialidad=?, telefono=?, email=?, password=? WHERE id=?");
                $stmt->bindParam(1, $nombre);
                $stmt->bindParam(2, $especialidad);
                $stmt->bindParam(3, $telefono);
                $stmt->bindParam(4, $email);
                $stmt->bindParam(5, $hashed_password);
                $stmt->bindParam(6, $id, PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare("UPDATE medicos SET nombre=?, especialidad=?, telefono=?, email=? WHERE id=?");
                $stmt->bindParam(1, $nombre);
                $stmt->bindParam(2, $especialidad);
                $stmt->bindParam(3, $telefono);
                $stmt->bindParam(4, $email);
                $stmt->bindParam(5, $id, PDO::PARAM_INT);
            }
            
            if ($stmt->execute()) {
                $mensaje = "✅ Médico actualizado exitosamente";
            } else {
                $error = "❌ Error al actualizar médico";
            }
        }
        elseif ($action == 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM medicos WHERE id=?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $mensaje = "✅ Médico eliminado exitosamente";
            } else {
                $error = "❌ Error al eliminar médico";
            }
        }
    }
}

// Obtener lista de médicos
$medicos = $pdo->query("SELECT * FROM medicos ORDER BY nombre ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Médicos - Admin</title>
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
        
        .btn-add {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close {
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }
        
        .close:hover {
            color: #333;
        }
        
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
    <div style="display: flex; flex-wrap: wrap;">
        <!-- Sidebar -->
        <div class="admin-sidebar" style="width: 260px;">
            <div class="logo">
                <h2><i class="fas fa-calendar-check"></i> Admin Panel</h2>
                <p>Sistema de Citas Médicas</p>
            </div>
            <ul class="admin-menu">
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="doctors.php" class="active"><i class="fas fa-user-md"></i> Médicos</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content" style="flex: 1;">
            <div class="admin-header">
                <h1><i class="fas fa-user-md"></i> Gestión de Médicos</h1>
                <button class="btn-add" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Nuevo Médico
                </button>
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
                                <th>Nombre</th>
                                <th>Especialidad</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($medicos->rowCount() > 0): ?>
                                <?php while($medico = $medicos->fetch()): ?>
                                <tr>
                                    <td><?php echo $medico['id']; ?></td>
                                    <td><?php echo htmlspecialchars($medico['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($medico['especialidad']); ?></td>
                                    <td><?php echo htmlspecialchars($medico['telefono']); ?></td>
                                    <td><?php echo htmlspecialchars($medico['email']); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-small" onclick="editDoctor(<?php echo $medico['id']; ?>, '<?php echo addslashes($medico['nombre']); ?>', '<?php echo addslashes($medico['especialidad']); ?>', '<?php echo addslashes($medico['telefono']); ?>', '<?php echo addslashes($medico['email']); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-small" onclick="deleteDoctor(<?php echo $medico['id']; ?>, '<?php echo addslashes($medico['nombre']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">No hay médicos registrados</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para agregar/editar médico -->
    <div id="doctorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Agregar Médico</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="doctorForm" method="POST" action="">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="id" id="doctorId">
                
                <div class="form-group">
                    <label>Nombre Completo:</label>
                    <input type="text" name="nombre" id="nombre" required>
                </div>
                
                <div class="form-group">
                    <label>Especialidad:</label>
                    <input type="text" name="especialidad" id="especialidad" required>
                </div>
                
                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="tel" name="telefono" id="telefono" required>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label>Contraseña:</label>
                    <input type="password" name="password" id="password" required>
                    <small id="passwordHelp" style="color: #666; display: block; margin-top: 5px;">Deja en blanco al editar para mantener la contraseña actual.</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(action) {
            document.getElementById('doctorModal').style.display = 'flex';
            if (action === 'add') {
                document.getElementById('modalTitle').innerText = 'Agregar Médico';
                document.getElementById('formAction').value = 'add';
                document.getElementById('doctorId').value = '';
                document.getElementById('nombre').value = '';
                document.getElementById('especialidad').value = '';
                document.getElementById('telefono').value = '';
                document.getElementById('email').value = '';
                document.getElementById('password').value = '';
                document.getElementById('password').required = true;
                document.getElementById('passwordHelp').innerText = 'La contraseña es obligatoria para nuevos médicos.';
            }
        }
        
        function editDoctor(id, nombre, especialidad, telefono, email) {
            openModal('edit');
            document.getElementById('modalTitle').innerText = 'Editar Médico';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('doctorId').value = id;
            document.getElementById('nombre').value = nombre;
            document.getElementById('especialidad').value = especialidad;
            document.getElementById('telefono').value = telefono;
            document.getElementById('email').value = email;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('passwordHelp').innerText = 'Deja en blanco para mantener la contraseña actual.';
        }
        
        function deleteDoctor(id, nombre) {
            if (confirm('¿Estás seguro de eliminar al médico: ' + nombre + '?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'action';
                input.value = 'delete';
                form.appendChild(input);
                var input2 = document.createElement('input');
                input2.type = 'hidden';
                input2.name = 'id';
                input2.value = id;
                form.appendChild(input2);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal() {
            document.getElementById('doctorModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('doctorModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>