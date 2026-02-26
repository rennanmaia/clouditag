<?php
session_start();
require_once 'includes/functions.php';

// Verificar se está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getCurrentUser();
$error_message = '';
$success_message = '';

// Obter perfis do usuário
$db = getDB();
$stmt = $db->prepare("SELECT * FROM profiles WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$profiles = $stmt->fetchAll();

// Processar upload de foto do usuário
if (isset($_POST['upload_photo'])) {
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['photo'], 'uploads/users');
        
        if ($upload_result['success']) {
            // Remover foto antiga se existir
            if ($user['photo'] && file_exists('uploads/users/' . $user['photo'])) {
                unlink('uploads/users/' . $user['photo']);
            }
            
            $stmt = $db->prepare("UPDATE users SET photo = ? WHERE id = ?");
            if ($stmt->execute([$upload_result['filename'], $user['id']])) {
                $success_message = 'Foto atualizada com sucesso!';
                $user['photo'] = $upload_result['filename'];
            } else {
                $error_message = 'Erro ao atualizar foto no banco de dados.';
            }
        } else {
            $error_message = $upload_result['message'];
        }
    }
}

// Processar atualização de dados pessoais
if (isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    
    if (empty($name) || empty($email)) {
        $error_message = 'Nome e email são obrigatórios.';
    } elseif (!isValidEmail($email)) {
        $error_message = 'Email inválido.';
    } else {
        // Verificar se email já existe (exceto o próprio usuário)
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user['id']]);
        
        if ($stmt->fetchColumn() > 0) {
            $error_message = 'Este email já está em uso.';
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$name, $email, $user['id']])) {
                $success_message = 'Dados pessoais atualizados com sucesso!';
                $user['name'] = $name;
                $user['email'] = $email;
            } else {
                $error_message = 'Erro ao atualizar dados pessoais.';
            }
        }
    }
}

// Processar logout
if (isset($_GET['logout'])) {
    logout();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CloudiTag</title>
    <script>(function(){var t=localStorage.getItem('clouditag_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-cloud"></i> Cloudi<span class="brand-accent">Tag</span></h3>
            <p>Olá, <?php echo htmlspecialchars($user['name']); ?>!</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="create_profile.php"><i class="fas fa-plus"></i> Criar Perfil</a></li>
            <li><a href="#" onclick="showModal('profileModal')"><i class="fas fa-user"></i> Meus Dados</a></li>
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
                            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                            <div class="breadcrumb"><span>Inicio</span> / <span>Dashboard</span></div>
                        </div>
                        <a href="create_profile.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Novo Perfil
                        </a>
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
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header justify-content-between align-items-center" style="display:flex;">
                            <span><i class="fas fa-th-large"></i> Meus Perfis</span>
                            <a href="create_profile.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Novo Perfil
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($profiles)): ?>
                                <div class="text-center p-4">
                                    <i class="fas fa-inbox fa-3x" style="color: var(--gray-400); margin-bottom: 20px;"></i>
                                    <h4>Nenhum perfil criado ainda</h4>
                                    <p>Crie seu primeiro perfil empresarial ou profissional para começar!</p>
                                    <a href="create_profile.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Criar Primeiro Perfil
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($profiles as $profile): ?>
                                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center" style="gap:14px;">
                                        <?php if ($profile['logo']): ?>
                                            <img src="uploads/profiles/<?php echo htmlspecialchars($profile['logo']); ?>" 
                                                 alt="Logo" style="width:54px;height:54px;object-fit:cover;border-radius:50%;border:2px solid var(--border);flex-shrink:0;">
                                        <?php else: ?>
                                            <div style="width:54px;height:54px;background:var(--grad-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <i class="fas fa-<?php echo $profile['profile_type'] === 'empresa' ? 'building' : 'user'; ?>" style="color:white;font-size:20px;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div style="min-width:0;">
                                            <h6 style="margin-bottom:4px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($profile['name']); ?></h6>
                                            <span class="badge badge-primary"><?php echo $profile['profile_type'] === 'empresa' ? 'Empresa' : 'Profissional'; ?></span>
                                        </div>
                                    </div>
                                    <p style="margin:12px 0 8px;font-size:13px;color:var(--text-muted);"><i class="fas fa-link" style="margin-right:5px;"></i><?php echo htmlspecialchars($profile['slug']); ?></p>
                                    <div class="d-flex gap-2">
                                        <a href="profile/<?php echo htmlspecialchars($profile['slug']); ?>" target="_blank" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                        <a href="edit_profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Dados Pessoais -->
    <div id="profileModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Meus Dados Pessoais</h5>
                    <button type="button" class="modal-close" onclick="hideModal('profileModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <!-- Upload de Foto -->
                    <form method="POST" enctype="multipart/form-data" class="mb-4">
                        <div class="text-center mb-3">
                            <?php if ($user['photo']): ?>
                                <img src="uploads/users/<?php echo htmlspecialchars($user['photo']); ?>" 
                                     alt="Foto do usuário" class="profile-logo" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <div style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                    <i class="fas fa-user" style="color: white; font-size: 40px;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="photo">Alterar Foto</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
                        </div>
                        <button type="submit" name="upload_photo" class="btn btn-primary btn-sm">
                            <i class="fas fa-upload"></i> Atualizar Foto
                        </button>
                    </form>
                    
                    <hr>
                    
                    <!-- Dados Pessoais -->
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Nome Completo</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" onclick="hideModal('profileModal')">Cancelar</button>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>