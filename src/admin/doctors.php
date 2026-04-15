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
                $check = $pdo->prepare("SELECT id FROM medicos WHERE email = ? LIMIT 1");
                $check->bindParam(1, $email);
                $check->execute();

                if ($check->fetch()) {
                    $error = "⚠️ Ya existe un médico con ese correo";
                } else {
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

            $check = $pdo->prepare("SELECT id FROM medicos WHERE email = ? AND id <> ? LIMIT 1");
            $check->bindParam(1, $email);
            $check->bindParam(2, $id, PDO::PARAM_INT);
            $check->execute();

            if ($check->fetch()) {
                $error = "⚠️ Ya existe otro médico con ese correo";
            } elseif (!empty($password)) {
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
            
            if (empty($error)) {
                if ($stmt->execute()) {
                    $mensaje = "✅ Médico actualizado exitosamente";
                } else {
                    $error = "❌ Error al actualizar médico";
                }
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
        elseif ($action == 'cleanup_duplicates') {
            try {
                $sqlDup = "SELECT LOWER(TRIM(email)) AS email_norm, COUNT(*) AS total
                           FROM medicos
                           WHERE email IS NOT NULL AND TRIM(email) <> ''
                           GROUP BY LOWER(TRIM(email))
                           HAVING COUNT(*) > 1";
                $dupStmt = $pdo->query($sqlDup);
                $dupGroups = $dupStmt ? $dupStmt->fetchAll(PDO::FETCH_ASSOC) : [];

                if (empty($dupGroups)) {
                    $mensaje = "ℹ️ No se encontraron médicos duplicados por correo";
                } else {
                    $totalEliminados = 0;

                    foreach ($dupGroups as $group) {
                        $emailNorm = $group['email_norm'];
                        $rowsStmt = $pdo->prepare("SELECT id FROM medicos WHERE LOWER(TRIM(email)) = ? ORDER BY id ASC");
                        $rowsStmt->bindParam(1, $emailNorm);
                        $rowsStmt->execute();
                        $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($rows) <= 1) {
                            continue;
                        }

                        $idPrincipal = (int)$rows[0]['id'];

                        for ($i = 1; $i < count($rows); $i++) {
                            $idDuplicado = (int)$rows[$i]['id'];

                            try {
                                $upCitas = $pdo->prepare("UPDATE citas SET medico_id = ? WHERE medico_id = ?");
                                $upCitas->bindParam(1, $idPrincipal, PDO::PARAM_INT);
                                $upCitas->bindParam(2, $idDuplicado, PDO::PARAM_INT);
                                $upCitas->execute();
                            } catch (Exception $e) {
                                // Ignorar si la tabla no existe en esta instalacion
                            }

                            try {
                                $upConsultas = $pdo->prepare("UPDATE expediente_consultas SET medico_id = ? WHERE medico_id = ?");
                                $upConsultas->bindParam(1, $idPrincipal, PDO::PARAM_INT);
                                $upConsultas->bindParam(2, $idDuplicado, PDO::PARAM_INT);
                                $upConsultas->execute();
                            } catch (Exception $e) {
                                // Ignorar si la tabla no existe en esta instalacion
                            }

                            $del = $pdo->prepare("DELETE FROM medicos WHERE id = ?");
                            $del->bindParam(1, $idDuplicado, PDO::PARAM_INT);
                            if ($del->execute()) {
                                $totalEliminados++;
                            }
                        }
                    }

                    if ($totalEliminados > 0) {
                        $mensaje = "✅ Limpieza completada: se eliminaron {$totalEliminados} médicos duplicados";
                    } else {
                        $mensaje = "ℹ️ No hubo registros para eliminar";
                    }
                }
            } catch (Exception $e) {
                error_log('Error limpiando médicos duplicados: ' . $e->getMessage());
                $error = "❌ Ocurrió un error al limpiar duplicados";
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
        :root {
            --ink: #0f172a;
            --blue: #1d4ed8;
            --cyan: #0ea5e9;
            --surface: rgba(255, 255, 255, 0.92);
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

        .btn-add {
            background: linear-gradient(135deg, var(--blue) 0%, var(--cyan) 100%);
            color: white;
            border: none;
            padding: 11px 18px;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 700;
            box-shadow: 0 12px 24px rgba(29, 78, 216, 0.18);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(29, 78, 216, 0.24);
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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            justify-content: center;
            align-items: center;
            padding: 18px;
            backdrop-filter: blur(6px);
        }

        .modal-content {
            background: var(--surface);
            padding: 24px;
            border-radius: 24px;
            max-width: 560px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--border);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .close {
            font-size: 30px;
            cursor: pointer;
            color: var(--muted);
            line-height: 1;
        }

        .close:hover { color: var(--ink); }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--ink);
        }

        .form-group input {
            width: 100%;
            min-height: 46px;
            padding: 10px 14px;
            border-radius: 14px;
            border: 1.5px solid #dbe4f0;
            background: #fff;
        }

        .btn,
        .btn-small {
            border-radius: 12px;
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
                <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
                    <button class="btn btn-outline-secondary" type="button" onclick="cleanupDuplicates()" style="padding: 11px 16px; border-radius: 14px; font-weight: 700;">
                        <i class="fas fa-broom"></i> Limpiar duplicados
                    </button>
                    <button class="btn-add" onclick="openModal('add')">
                        <i class="fas fa-plus"></i> Nuevo Médico
                    </button>
                </div>
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

        function cleanupDuplicates() {
            if (confirm('Esto unificará médicos duplicados por correo y eliminará repetidos. ¿Deseas continuar?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'action';
                input.value = 'cleanup_duplicates';
                form.appendChild(input);

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