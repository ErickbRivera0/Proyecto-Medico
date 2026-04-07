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

// Procesar formulario de agregar/editar usuario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $nombre = trim($_POST['nombre']);
            $email = trim($_POST['email']);
            $telefono = trim($_POST['telefono']);
            $password = trim($_POST['password']);
            $rol = trim($_POST['rol']);
            $estado = trim($_POST['estado']);
            
            // Verificar si el email ya existe
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $check->bindParam(1, $email);
            $check->execute();
            
            if ($check->rowCount() > 0) {
                $error = "❌ Este email ya está registrado";
            } elseif (!empty($nombre) && !empty($email) && !empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $fecha_registro = date('Y-m-d H:i:s');
                
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, telefono, password, rol, estado, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bindParam(1, $nombre);
                $stmt->bindParam(2, $email);
                $stmt->bindParam(3, $telefono);
                $stmt->bindParam(4, $hashed_password);
                $stmt->bindParam(5, $rol);
                $stmt->bindParam(6, $estado);
                $stmt->bindParam(7, $fecha_registro);
                
                if ($stmt->execute()) {
                    $mensaje = "✅ Usuario agregado exitosamente";
                } else {
                    $error = "❌ Error al agregar usuario";
                }
            } else {
                $error = "⚠️ Nombre, email y contraseña son obligatorios";
            }
        } 
        elseif ($action == 'edit') {
            $id = $_POST['id'];
            $nombre = trim($_POST['nombre']);
            $email = trim($_POST['email']);
            $telefono = trim($_POST['telefono']);
            $rol = trim($_POST['rol']);
            $estado = trim($_POST['estado']);
            $password = trim($_POST['password']);
            
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, email=?, telefono=?, password=?, rol=?, estado=? WHERE id=?");
                $stmt->bindParam(1, $nombre);
                $stmt->bindParam(2, $email);
                $stmt->bindParam(3, $telefono);
                $stmt->bindParam(4, $hashed_password);
                $stmt->bindParam(5, $rol);
                $stmt->bindParam(6, $estado);
                $stmt->bindParam(7, $id, PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, email=?, telefono=?, rol=?, estado=? WHERE id=?");
                $stmt->bindParam(1, $nombre);
                $stmt->bindParam(2, $email);
                $stmt->bindParam(3, $telefono);
                $stmt->bindParam(4, $rol);
                $stmt->bindParam(5, $estado);
                $stmt->bindParam(6, $id, PDO::PARAM_INT);
            }
            
            if ($stmt->execute()) {
                $mensaje = "✅ Usuario actualizado exitosamente";
            } else {
                $error = "❌ Error al actualizar usuario";
            }
        }
        elseif ($action == 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id=?");
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $mensaje = "✅ Usuario eliminado exitosamente";
            } else {
                $error = "❌ Error al eliminar usuario";
            }
        }
    }
}

// Obtener lista de usuarios
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY fecha_registro DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin</title>
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
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-admin { background: #dc3545; color: white; }
        .badge-doctor { background: #17a2b8; color: white; }
        .badge-usuario { background: #28a745; color: white; }
        .badge-activo { background: #28a745; color: white; }
        .badge-inactivo { background: #dc3545; color: white; }
        
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
                <li><a href="doctors.php"><i class="fas fa-user-md"></i> Médicos</a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content" style="flex: 1;">
            <div class="admin-header">
                <h1><i class="fas fa-users"></i> Gestión de Usuarios</h1>
                <button class="btn-add" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Nuevo Usuario
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
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($usuarios->rowCount() > 0): ?>
                                <?php while($usuario = $usuarios->fetch()): ?>
                                <tr>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['telefono']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $usuario['rol']; ?>">
                                            <?php echo ucfirst($usuario['rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $usuario['estado']; ?>">
                                            <?php echo ucfirst($usuario['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-small" onclick="editUser(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['nombre']); ?>', '<?php echo addslashes($usuario['email']); ?>', '<?php echo addslashes($usuario['telefono']); ?>', '<?php echo $usuario['rol']; ?>', '<?php echo $usuario['estado']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-small" onclick="deleteUser(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['nombre']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No hay usuarios registrados</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para agregar/editar usuario -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Agregar Usuario</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="userForm" method="POST" action="">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="id" id="userId">
                
                <div class="form-group">
                    <label>Nombre Completo:</label>
                    <input type="text" name="nombre" id="nombre" required>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="tel" name="telefono" id="telefono">
                </div>
                
                <div class="form-group">
                    <label>Contraseña:</label>
                    <input type="password" name="password" id="password" <?php echo isset($_GET['edit']) ? '' : 'required'; ?>>
                    <small style="color: #666;"><?php echo isset($_GET['edit']) ? 'Dejar en blanco para mantener la actual' : ''; ?></small>
                </div>
                
                <div class="form-group">
                    <label>Rol:</label>
                    <select name="rol" id="rol" required>
                        <option value="usuario">Usuario</option>
                        <option value="admin">Administrador</option>
                        <option value="medico">Médico</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Estado:</label>
                    <select name="estado" id="estado" required>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(action) {
            document.getElementById('userModal').style.display = 'flex';
            if (action === 'add') {
                document.getElementById('modalTitle').innerText = 'Agregar Usuario';
                document.getElementById('formAction').value = 'add';
                document.getElementById('userId').value = '';
                document.getElementById('nombre').value = '';
                document.getElementById('email').value = '';
                document.getElementById('telefono').value = '';
                document.getElementById('password').value = '';
                document.getElementById('password').required = true;
                document.getElementById('rol').value = 'usuario';
                document.getElementById('estado').value = 'activo';
            }
        }
        
        function editUser(id, nombre, email, telefono, rol, estado) {
            openModal('edit');
            document.getElementById('modalTitle').innerText = 'Editar Usuario';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('userId').value = id;
            document.getElementById('nombre').value = nombre;
            document.getElementById('email').value = email;
            document.getElementById('telefono').value = telefono;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('rol').value = rol;
            document.getElementById('estado').value = estado;
        }
        
        function deleteUser(id, nombre) {
            if (confirm('¿Estás seguro de eliminar al usuario: ' + nombre + '?')) {
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
            document.getElementById('userModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('userModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>