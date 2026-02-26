<?php
session_start();
require_once '../includes/functions.php';

// Verificar se está logado e é admin geral
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$user = getCurrentUser();
$error_message = '';
$success_message = '';

$db = getDB();

// Obter estatísticas
$stats = [];

// Total de usuários
$stmt = $db->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$stats['total_users'] = $stmt->fetchColumn();

// Total de perfis
$stmt = $db->prepare("SELECT COUNT(*) FROM profiles");
$stmt->execute();
$stats['total_profiles'] = $stmt->fetchColumn();

// Perfis ativos
$stmt = $db->prepare("SELECT COUNT(*) FROM profiles WHERE is_active = 1");
$stmt->execute();
$stats['active_profiles'] = $stmt->fetchColumn();

// Usuários cadastrados hoje
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$stats['users_today'] = $stmt->fetchColumn();

// Processar criação de usuário admin
if (isset($_POST['create_admin'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $user_type = sanitize($_POST['user_type']);
    
    if (empty($name) || empty($email) || empty($password)) {
        $error_message = 'Todos os campos são obrigatórios.';
    } elseif (!isValidEmail($email)) {
        $error_message = 'Email inválido.';
    } elseif (!in_array($user_type, ['admin_geral', 'admin_perfis'])) {
        $error_message = 'Tipo de usuário inválido.';
    } else {
        // Verificar se email já existe
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetchColumn() > 0) {
            $error_message = 'Este email já está em uso.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password, user_type, created_at) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $hashed_password, $user_type, date('Y-m-d H:i:s')])) {
                $success_message = 'Usuário administrador criado com sucesso!';
            } else {
                $error_message = 'Erro ao criar usuário administrador.';
            }
        }
    }
}

// Obter usuários recentes
$stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$recent_users = $stmt->fetchAll();

// Obter perfis recentes
$stmt = $db->prepare("
    SELECT p.*, u.name as owner_name 
    FROM profiles p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_profiles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - CloudiTag</title>
    <script>(function(){var t=localStorage.getItem('clouditag_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-cloud"></i> Cloudi<span class="brand-accent">Tag</span></h3>
            <p>Admin: <?php echo htmlspecialchars($user['name']); ?></p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Usuários</a></li>
            <li><a href="profiles.php"><i class="fas fa-list"></i> Perfis</a></li>
            <li><a href="field_types.php"><i class="fas fa-sliders-h"></i> Tipos de Campo</a></li>
            <li><a href="#" onclick="showModal('createAdminModal')"><i class="fas fa-user-plus"></i> Criar Admin</a></li>
            <li><a href="../dashboard.php"><i class="fas fa-user"></i> Área do Usuário</a></li>
            <li><a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
        </ul>
        <div class="sidebar-footer">
            <button class="theme-toggle" onclick="toggleTheme()" title="Alternar tema">
                <i class="fas fa-moon"></i>
                <span class="theme-label">Modo Escuro</span>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-header">
                        <div>
                            <h1><i class="fas fa-shield-alt"></i> Dashboard Administrativo</h1>
                            <div class="breadcrumb"><span>Admin</span> / <span>Dashboard</span></div>
                        </div>
                        <button onclick="showModal('createAdminModal')" class="btn btn-primary btn-sm">
                            <i class="fas fa-user-plus"></i> Criar Admin
                        </button>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Estatísticas -->
            <div class="row mb-4">
                <div class="col-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-label">Total de Usuários</div>
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-id-card"></i></div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $stats['total_profiles']; ?></div>
                            <div class="stat-label">Total de Perfis</div>
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-card">
                        <div class="stat-icon cyan"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $stats['active_profiles']; ?></div>
                            <div class="stat-label">Perfis Ativos</div>
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-card">
                        <div class="stat-icon navy"><i class="fas fa-user-plus"></i></div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $stats['users_today']; ?></div>
                            <div class="stat-label">Usuários Hoje</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Usuários Recentes -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-users"></i> Usuários Recentes</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_users)): ?>
                                <p class="text-center text-muted">Nenhum usuário encontrado.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Email</th>
                                                <th>Tipo</th>
                                                <th>Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_users as $recent_user): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($recent_user['photo']): ?>
                                                            <img src="../uploads/users/<?php echo htmlspecialchars($recent_user['photo']); ?>" 
                                                                 style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px; object-fit: cover;">
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($recent_user['name']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($recent_user['email']); ?></td>
                                                    <td>
                                                        <span class="badge" style="background: <?php echo $recent_user['user_type'] === 'admin_geral' ? 'var(--danger-color)' : ($recent_user['user_type'] === 'admin_perfis' ? 'var(--warning-color)' : 'var(--info-color)'); ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                                                            <?php 
                                                            switch($recent_user['user_type']) {
                                                                case 'admin_geral': echo 'Admin Geral'; break;
                                                                case 'admin_perfis': echo 'Admin Perfis'; break;
                                                                default: echo 'Usuário'; break;
                                                            }
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($recent_user['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center">
                                    <a href="users.php" class="btn btn-primary btn-sm">Ver Todos os Usuários</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Perfis Recentes -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-list"></i> Perfis Recentes</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_profiles)): ?>
                                <p class="text-center text-muted">Nenhum perfil encontrado.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Tipo</th>
                                                <th>Proprietário</th>
                                                <th>Data</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_profiles as $recent_profile): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($recent_profile['logo']): ?>
                                                            <img src="../uploads/profiles/<?php echo htmlspecialchars($recent_profile['logo']); ?>" 
                                                                 style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px; object-fit: cover;">
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($recent_profile['name']); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge" style="background: var(--primary-color); color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                                                            <?php echo $recent_profile['profile_type'] === 'empresa' ? 'Empresa' : 'Profissional'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($recent_profile['owner_name']); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($recent_profile['created_at'])); ?></td>
                                                    <td>
                                                        <a href="../profile/<?php echo htmlspecialchars($recent_profile['slug']); ?>" target="_blank" class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center">
                                    <a href="profiles.php" class="btn btn-primary btn-sm">Ver Todos os Perfis</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Criar Admin -->
    <div id="createAdminModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Criar Novo Administrador</h5>
                    <button type="button" class="modal-close" onclick="hideModal('createAdminModal')">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Nome Completo</label>
                            <input type="text" class="form-control" name="name" id="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="email" id="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Senha</label>
                            <input type="password" class="form-control" name="password" id="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="user_type">Tipo de Administrador</label>
                            <select class="form-control" name="user_type" id="user_type" required>
                                <option value="">Selecione o tipo</option>
                                <option value="admin_geral">Admin Geral</option>
                                <option value="admin_perfis">Admin de Perfis</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" onclick="hideModal('createAdminModal')">Cancelar</button>
                        <button type="submit" name="create_admin" class="btn btn-primary">
                            <i class="fas fa-save"></i> Criar Administrador
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    
    <?php if (isset($_GET['logout'])): ?>
        <script>
            setTimeout(() => {
                window.location.href = '../login.php';
            }, 100);
        </script>
        <?php session_destroy(); ?>
    <?php endif; ?>
</body>
</html>