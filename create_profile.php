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

if ($_POST) {
    $profile_type = sanitize($_POST['profile_type']);
    $name = sanitize($_POST['name']);
    $slug = sanitize($_POST['slug']);
    $description = sanitize($_POST['description']);
    
    // Validações básicas
    if (empty($profile_type) || empty($name) || empty($slug)) {
        $error_message = 'Tipo de perfil, nome e slug são obrigatórios.';
    } elseif (!in_array($profile_type, ['empresa', 'profissional'])) {
        $error_message = 'Tipo de perfil inválido.';
    } else {
        $db = getDB();
        
        // Verificar se slug já existe
        $stmt = $db->prepare("SELECT COUNT(*) FROM profiles WHERE slug = ?");
        $stmt->execute([$slug]);
        
        if ($stmt->fetchColumn() > 0) {
            $error_message = 'Este slug já está em uso. Escolha outro.';
        } else {
            // Upload da logo se fornecida
            $logo_filename = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadFile($_FILES['logo'], 'uploads/profiles');
                if ($upload_result['success']) {
                    $logo_filename = $upload_result['filename'];
                } else {
                    $error_message = $upload_result['message'];
                }
            }
            
            if (!$error_message) {
                // Inserir perfil básico
                $stmt = $db->prepare("
                    INSERT INTO profiles (user_id, profile_type, name, slug, description, logo, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$user['id'], $profile_type, $name, $slug, $description, $logo_filename, date('Y-m-d H:i:s')])) {
                    $profile_id = $db->lastInsertId();
                    
                    // Processar campos dinâmicos
                    $field_types = getFieldTypes();
                    foreach ($field_types as $field_type) {
                        $field_value = isset($_POST['field_' . $field_type['name']]) ? sanitize($_POST['field_' . $field_type['name']]) : '';
                        
                        if (!empty($field_value)) {
                            updateProfileField($profile_id, $field_type['id'], $field_value);
                        }
                    }
                    
                    $success_message = 'Perfil criado com sucesso!';
                    // Redirecionar para gerenciar campos
                    redirect("edit_profile.php?id=$profile_id");
                } else {
                    $error_message = 'Erro ao criar perfil. Tente novamente.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Perfil - CloudiTag</title>
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
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="create_profile.php" class="active"><i class="fas fa-plus"></i> Criar Perfil</a></li>
            <li><a href="dashboard.php"><i class="fas fa-user"></i> Meus Dados</a></li>
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
                            <h1><i class="fas fa-plus-circle"></i> Criar Novo Perfil</h1>
                            <div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <span>Criar Perfil</span></div>
                        </div>
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
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="profileForm">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="profile_type">Tipo de Perfil *</label>
                                            <select class="form-control" name="profile_type" id="profile_type" required>
                                                <option value="">Selecione o tipo</option>
                                                <option value="empresa" <?php echo (isset($_POST['profile_type']) && $_POST['profile_type'] === 'empresa') ? 'selected' : ''; ?>>Empresa</option>
                                                <option value="profissional" <?php echo (isset($_POST['profile_type']) && $_POST['profile_type'] === 'profissional') ? 'selected' : ''; ?>>Profissional</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="name">Nome *</label>
                                            <input type="text" class="form-control" name="name" id="name" 
                                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="slug">Slug (URL personalizada) *</label>
                                            <div class="input-group">
                                                <span style="padding: 12px; background: var(--gray-100); border: 2px solid var(--gray-300); border-right: none; border-radius: var(--border-radius) 0 0 var(--border-radius);">
                                                    <?php echo SITE_URL; ?>/profile/
                                                </span>
                                                <input type="text" class="form-control" name="slug" id="slug" 
                                                       value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : ''; ?>" 
                                                       style="border-left: none; border-radius: 0 var(--border-radius) var(--border-radius) 0;" required>
                                            </div>
                                            <small class="text-muted">Apenas letras, números e hífens</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="logo">Logo/Foto</label>
                                            <input type="file" class="form-control" name="logo" accept="image/*" onchange="previewImage(this, 'logo-preview')">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Descrição</label>
                                    <textarea class="form-control" name="description" id="description" rows="3" data-auto-resize><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>
                                
                                <h4>Campos Disponíveis</h4>
                                <hr>
                                <p class="text-muted">Preencha apenas os campos que deseja incluir no seu perfil. Você poderá adicionar, editar ou remover campos depois.</p>
                                
                                <?php
                                $field_types = getFieldTypes();
                                $cols_per_row = 0;
                                echo '<div class="row">';
                                foreach ($field_types as $field_type):
                                    if ($cols_per_row == 0) echo '<div class="row">';
                                ?>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="field_<?php echo $field_type['name']; ?>">
                                                <?php if ($field_type['icon']): ?>
                                                    <i class="<?php echo htmlspecialchars($field_type['icon']); ?>"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($field_type['label']); ?>
                                            </label>
                                            <?php if ($field_type['input_type'] === 'textarea'): ?>
                                                <textarea class="form-control" name="field_<?php echo $field_type['name']; ?>" 
                                                         id="field_<?php echo $field_type['name']; ?>" rows="2"
                                                         placeholder="<?php echo htmlspecialchars($field_type['placeholder']); ?>"><?php echo isset($_POST['field_' . $field_type['name']]) ? htmlspecialchars($_POST['field_' . $field_type['name']]) : ''; ?></textarea>
                                            <?php else: ?>
                                                <input type="<?php echo $field_type['input_type']; ?>" 
                                                       class="form-control" 
                                                       name="field_<?php echo $field_type['name']; ?>" 
                                                       id="field_<?php echo $field_type['name']; ?>"
                                                       placeholder="<?php echo htmlspecialchars($field_type['placeholder']); ?>"
                                                       value="<?php echo isset($_POST['field_' . $field_type['name']]) ? htmlspecialchars($_POST['field_' . $field_type['name']]) : ''; ?>"
                                                       <?php if ($field_type['validation_pattern']): ?>
                                                           pattern="<?php echo htmlspecialchars($field_type['validation_pattern']); ?>"
                                                       <?php endif; ?>>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php
                                    $cols_per_row++;
                                    if ($cols_per_row == 2) {
                                        echo '</div>';
                                        $cols_per_row = 0;
                                    }
                                endforeach;
                                if ($cols_per_row > 0) echo '</div>';
                                ?>
                                
                                <!-- Preview da logo -->
                                <div id="logo-preview-container" style="display: none; text-align: center; margin: 20px 0;">
                                    <img id="logo-preview" style="max-width: 200px; max-height: 200px; border-radius: 10px; box-shadow: var(--box-shadow);">
                                </div>
                                
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Criar Perfil
                                    </button>
                                    <a href="dashboard.php" class="btn btn-light">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Configurar geração automática de slug
        document.addEventListener('DOMContentLoaded', function() {
            setupSlugGeneration('name', 'slug');
            
            // Preview da logo
            function previewImage(input, previewId) {
                const file = input.files[0];
                const preview = document.getElementById(previewId);
                const container = document.getElementById(previewId + '-container');
                
                if (file && preview) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        container.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            }
            
            // Tornar função global
            window.previewImage = previewImage;
        });
    </script>
</body>
</html>