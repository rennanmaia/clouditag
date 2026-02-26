<?php
session_start();
require_once 'includes/functions.php';

// Verificar se está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getCurrentUser();
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verificar se o perfil existe e pertence ao usuário (ou se é admin)
$db = getDB();
$stmt = $db->prepare("SELECT * FROM profiles WHERE id = ?");
$stmt->execute([$profile_id]);
$profile = $stmt->fetch();

if (!$profile || (!userOwnsProfile($user['id'], $profile_id) && !isProfileAdmin())) {
    redirect('dashboard.php');
}

$error_message = '';
$success_message = '';

// Buscar campos atuais do perfil (todos, incluindo invisíveis para administração)
$profile_fields = getAllProfileFields($profile_id);
$field_types = getFieldTypes();

// Processar ações AJAX
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add_field':
            $field_type_id = (int)$_POST['field_type_id'];
            $value = sanitize($_POST['value'] ?? '');

            // Se o tipo for URL, completar protocolo automaticamente
            $stmt = $db->prepare("SELECT input_type FROM field_types WHERE id = ?");
            $stmt->execute([$field_type_id]);
            $ft_row = $stmt->fetch();
            if ($ft_row && $ft_row['input_type'] === 'url') {
                $value = normalizeUrlValue($value);
            }
            
            // Verificar se o campo já existe
            $stmt = $db->prepare("SELECT COUNT(*) FROM profile_fields WHERE profile_id = ? AND field_type_id = ?");
            $stmt->execute([$profile_id, $field_type_id]);
            
            if ($stmt->fetchColumn() == 0) {
                $stmt = $db->prepare("INSERT INTO profile_fields (profile_id, field_type_id, value, created_at) VALUES (?, ?, ?, NOW())");
                if ($stmt->execute([$profile_id, $field_type_id, $value])) {
                    echo json_encode(['success' => true, 'message' => 'Campo adicionado com sucesso!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar campo.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Este campo já existe no perfil.']);
            }
            exit;
            
        case 'update_field':
            $field_id = (int)$_POST['field_id'];
            $value = sanitize($_POST['value']);
            $is_visible = isset($_POST['is_visible']) ? 1 : 0;
            $is_clickable = isset($_POST['is_clickable']) ? 1 : 0;

            // Descobrir tipo do campo para normalizar URLs
            $stmt = $db->prepare("SELECT ft.input_type
                                   FROM profile_fields pf
                                   JOIN field_types ft ON pf.field_type_id = ft.id
                                   WHERE pf.id = ? AND pf.profile_id = ?");
            $stmt->execute([$field_id, $profile_id]);
            $ft_row = $stmt->fetch();
            if ($ft_row && $ft_row['input_type'] === 'url') {
                $value = normalizeUrlValue($value);
            }
            
            $stmt = $db->prepare("UPDATE profile_fields SET value = ?, is_visible = ?, is_clickable = ? WHERE id = ? AND profile_id = ?");
            if ($stmt->execute([$value, $is_visible, $is_clickable, $field_id, $profile_id])) {
                echo json_encode(['success' => true, 'message' => 'Campo atualizado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar campo.']);
            }
            exit;
            
        case 'remove_field':
            $field_id = (int)$_POST['field_id'];
            
            $stmt = $db->prepare("DELETE FROM profile_fields WHERE id = ? AND profile_id = ?");
            if ($stmt->execute([$field_id, $profile_id])) {
                echo json_encode(['success' => true, 'message' => 'Campo removido com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao remover campo.']);
            }
            exit;
    }
}

if ($_POST && !isset($_POST['action'])) {
    $profile_type = sanitize($_POST['profile_type']);
    $name = sanitize($_POST['name']);
    $slug = sanitize($_POST['slug']);
    $description = sanitize($_POST['description']);
    
    // Validações
    if (empty($profile_type) || empty($name) || empty($slug)) {
        $error_message = 'Tipo de perfil, nome e slug são obrigatórios.';
    } elseif (!in_array($profile_type, ['empresa', 'profissional'])) {
        $error_message = 'Tipo de perfil inválido.';
    } else {
        // Verificar se slug já existe (exceto o próprio perfil)
        $stmt = $db->prepare("SELECT COUNT(*) FROM profiles WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $profile_id]);
        
        if ($stmt->fetchColumn() > 0) {
            $error_message = 'Este slug já está em uso. Escolha outro.';
        } else {
            $logo_filename = $profile['logo']; // Manter logo atual
            
            // Upload da nova logo se fornecida
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadFile($_FILES['logo'], 'uploads/profiles');
                if ($upload_result['success']) {
                    // Remover logo anterior se existir
                    if ($profile['logo'] && file_exists('uploads/profiles/' . $profile['logo'])) {
                        unlink('uploads/profiles/' . $profile['logo']);
                    }
                    $logo_filename = $upload_result['filename'];
                } else {
                    $error_message = $upload_result['message'];
                }
            }
            
            if (!$error_message) {
                // Atualizar perfil básico
                $stmt = $db->prepare("
                    UPDATE profiles SET 
                        profile_type = ?, name = ?, slug = ?, description = ?, logo = ?
                    WHERE id = ?
                ");
                
                if ($stmt->execute([
                    $profile_type, $name, $slug, $description, $logo_filename, $profile_id
                ])) {
                    $success_message = 'Perfil atualizado com sucesso!';
                    
                    // Atualizar dados locais
                    $profile['profile_type'] = $profile_type;
                    $profile['name'] = $name;
                    $profile['slug'] = $slug;
                    $profile['description'] = $description;
                    $profile['logo'] = $logo_filename;
                    
                    // Recarregar campos dinâmicos
                    $profile_fields = getAllProfileFields($profile_id);
                } else {
                    $error_message = 'Erro ao atualizar perfil. Tente novamente.';
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
    <title>Editar Perfil - <?php echo htmlspecialchars($profile['name']); ?> - CloudiTag</title>
    <script>(function(){var t=localStorage.getItem('clouditag_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-cloud"></i> Cloudi<span class="brand-accent">Tag</span></h3>
            <p>Editando: <?php echo htmlspecialchars($profile['name']); ?></p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="edit_profile.php?id=<?php echo $profile['id']; ?>" class="active"><i class="fas fa-edit"></i> Editar Perfil</a></li>
            <li><a href="profile/<?php echo $profile['slug']; ?>" target="_blank"><i class="fas fa-eye"></i> Ver Perfil</a></li>
            <li><a href="dashboard.php"><i class="fas fa-arrow-left"></i> Voltar</a></li>
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
                            <h1><i class="fas fa-edit"></i> Editar Perfil</h1>
                            <div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <span><?php echo htmlspecialchars($profile['name']); ?></span></div>
                        </div>
                        <a href="profile/<?php echo $profile['slug']; ?>" target="_blank" class="btn btn-outline btn-sm">
                            <i class="fas fa-external-link-alt"></i> Ver Perfil
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
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="profileForm">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="profile_type">Tipo de Perfil *</label>
                                            <select class="form-control" name="profile_type" id="profile_type" required>
                                                <option value="">Selecione o tipo</option>
                                                <option value="empresa" <?php echo ($profile['profile_type'] === 'empresa') ? 'selected' : ''; ?>>Empresa</option>
                                                <option value="profissional" <?php echo ($profile['profile_type'] === 'profissional') ? 'selected' : ''; ?>>Profissional</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="name">Nome *</label>
                                            <input type="text" class="form-control" name="name" id="name" 
                                                   value="<?php echo htmlspecialchars($profile['name']); ?>" required>
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
                                                       value="<?php echo htmlspecialchars($profile['slug']); ?>" 
                                                       style="border-left: none; border-radius: 0 var(--border-radius) var(--border-radius) 0;" required>
                                            </div>
                                            <small class="text-muted">Apenas letras, números e hífens</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="logo">Logo/Foto</label>
                                            <input type="file" class="form-control" name="logo" accept="image/*" onchange="previewImage(this, 'logo-preview')">
                                            <small class="text-muted">Deixe em branco para manter a logo atual</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Preview da logo atual -->
                                <?php if ($profile['logo']): ?>
                                    <div class="text-center mb-3">
                                        <p><strong>Logo atual:</strong></p>
                                        <img src="uploads/profiles/<?php echo htmlspecialchars($profile['logo']); ?>" 
                                             style="max-width: 200px; max-height: 200px; border-radius: 10px; box-shadow: var(--box-shadow);">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label for="description">Descrição</label>
                                    <textarea class="form-control" name="description" id="description" rows="3" data-auto-resize><?php echo htmlspecialchars($profile['description']); ?></textarea>
                                </div>
                                
                                <!-- Preview da nova logo -->
                                <div id="logo-preview-container" style="display: none; text-align: center; margin: 20px 0;">
                                    <p><strong>Nova logo:</strong></p>
                                    <img id="logo-preview" style="max-width: 200px; max-height: 200px; border-radius: 10px; box-shadow: var(--box-shadow);">
                                </div>
                                
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Salvar Perfil
                                    </button>
                                    <a href="profile/<?php echo $profile['slug']; ?>" target="_blank" class="btn btn-info">
                                        <i class="fas fa-eye"></i> Ver Perfil
                                    </a>
                                    <a href="dashboard.php" class="btn btn-light">
                                        <i class="fas fa-arrow-left"></i> Voltar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Seção de Gerenciamento Dinâmico de Campos -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-cogs"></i> Gerenciamento de Campos</h3>
                            <p class="text-muted mb-0">Adicione ou remova campos dinamicamente para personalizar seu perfil</p>
                        </div>
                        <div class="card-body">
                            <!-- Mensagens AJAX -->
                            <div id="field-messages"></div>
                            
                            <!-- Adicionar Novo Campo -->
                            <div class="add-field-section mb-4 p-3" style="background: var(--gray-50); border-radius: var(--border-radius); border: 2px dashed var(--gray-300);">
                                <h4><i class="fas fa-plus-circle text-success"></i> Adicionar Campo</h4>
                                <div class="row" style="gap:0;">
                                    <div class="col-4">
                                        <label style="font-size:.83em;margin-bottom:4px;display:block;">Tipo</label>
                                        <select id="new-field-type" class="form-control">
                                            <option value="">Selecione um tipo de campo</option>
                                            <?php foreach ($field_types as $field_type): ?>
                                                <?php 
                                                $field_exists = false;
                                                foreach ($profile_fields as $existing_field) {
                                                    if ($existing_field['field_type_id'] == $field_type['id']) {
                                                        $field_exists = true;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <?php if (!$field_exists): ?>
                                                    <option value="<?php echo $field_type['id']; ?>" 
                                                            data-icon="<?php echo $field_type['icon']; ?>"
                                                            data-type="<?php echo $field_type['input_type']; ?>"
                                                            data-name="<?php echo htmlspecialchars($field_type['name']); ?>"
                                                            data-placeholder="<?php echo htmlspecialchars($field_type['placeholder']); ?>">
                                                        <?php echo htmlspecialchars($field_type['label']); ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label style="font-size:.83em;margin-bottom:4px;display:block;">Valor</label>
                                        <input type="text" id="new-field-value" class="form-control" placeholder="Selecione um tipo acima" disabled>
                                        <small id="new-field-hint" style="color:var(--gray-400);font-size:.78em;"></small>
                                    </div>
                                    <div class="col-2" style="display:flex;align-items:flex-end;">
                                        <button type="button" class="btn btn-success" onclick="addField()" style="width:100%">
                                            <i class="fas fa-plus"></i> Adicionar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Lista de Campos Existentes -->
                            <h4><i class="fas fa-list"></i> Campos Atuais</h4>
                            <div id="fields-container">
                                <?php if (empty($profile_fields)): ?>
                                    <div class="empty-fields text-center p-4" id="no-fields-msg" style="color: var(--gray-500);">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>Nenhum campo adicionado ainda. Use o formulário acima para adicionar campos ao seu perfil.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($profile_fields as $field): ?>
                                        <?php
                                            $hints = [
                                                'whatsapp'     => 'Número com DDI+DDD, ex´: 5511999998888',
                                                'phone'        => 'Número com DDD, ex: (11) 99999-8888',
                                                'pix'          => 'CPF, CNPJ, e-mail, telefone ou chave aleatória',
                                                'wifi_password' => 'A senha será exibida publicamente no cartão',
                                                'wifi_ssid'    => 'Nome exato da rede Wi-Fi',
                                                'website'      => 'URL completa incluindo https://',
                                                'google_review' => 'Link direto para avaliação no Google Maps',
                                            ];
                                            $hint = $hints[$field['field_name']] ?? '';
                                        ?>
                                        <div class="field-item" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid var(--gray-300);border-radius:var(--border-radius);background:var(--card-bg);margin-bottom:8px;" data-field-id="<?php echo $field['id']; ?>" data-input-type="<?php echo htmlspecialchars($field['input_type']); ?>">
                                            <span style="width:34px;text-align:center;font-size:1.2em;color:var(--brand-blue);flex-shrink:0;">
                                                <i class="<?php echo htmlspecialchars($field['icon']); ?>"></i>
                                            </span>
                                            <span style="min-width:110px;font-weight:600;font-size:.88em;color:var(--text-secondary);flex-shrink:0;">
                                                <?php echo htmlspecialchars($field['label']); ?>
                                            </span>
                                            <div style="flex:1;">
                                                <?php if ($field['input_type'] === 'textarea'): ?>
                                                    <textarea class="form-control field-value" 
                                                            data-field-id="<?php echo $field['id']; ?>"
                                                            placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                                            rows="2"
                                                            style="margin:0;"><?php echo htmlspecialchars($field['value'] ?? ''); ?></textarea>
                                                <?php else: ?>
                                                    <input type="<?php echo htmlspecialchars($field['input_type']); ?>" 
                                                           class="form-control field-value" 
                                                           data-field-id="<?php echo $field['id']; ?>"
                                                           value="<?php echo htmlspecialchars($field['value'] ?? ''); ?>"
                                                           placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                                           style="margin:0;">
                                                <?php endif; ?>
                                                <?php if ($hint): ?>
                                                    <small style="color:var(--gray-400);font-size:.78em;"><?php echo $hint; ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;">
                                                <label style="display:flex;align-items:center;gap:4px;font-size:.8em;white-space:nowrap;cursor:pointer;">
                                                    <input type="checkbox" class="field-visible" data-field-id="<?php echo $field['id']; ?>" <?php echo $field['is_visible'] ? 'checked' : ''; ?>> Visível
                                                </label>
                                                <label style="display:flex;align-items:center;gap:4px;font-size:.8em;white-space:nowrap;cursor:pointer;">
                                                    <input type="checkbox" class="field-clickable" data-field-id="<?php echo $field['id']; ?>" <?php echo $field['is_clickable'] ? 'checked' : ''; ?>> Clicável
                                                </label>
                                            </div>
                                            <div style="display:flex;gap:6px;flex-shrink:0;">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="updateField(<?php echo $field['id']; ?>)" title="Salvar">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeField(<?php echo $field['id']; ?>)" title="Remover">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
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
        
        // Funções de gerenciamento dinâmico de campos
        function showMessage(message, type = 'success') {
            const messagesDiv = document.getElementById('field-messages');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
            
            messagesDiv.innerHTML = `
                <div class="alert ${alertClass}">
                    <i class="${icon}"></i> ${message}
                </div>
            `;
            
            // Auto-remover mensagem após 3 segundos
            setTimeout(() => {
                messagesDiv.innerHTML = '';
            }, 3000);
        }
        
        function addField() {
            const fieldTypeSelect = document.getElementById('new-field-type');
            const fieldValueInput = document.getElementById('new-field-value');
            
            if (!fieldTypeSelect.value) {
                showMessage('Selecione um tipo de campo!', 'error');
                return;
            }
            
            let value = fieldValueInput.value.trim();
            if (!value) {
                showMessage('Digite um valor para o campo!', 'error');
                return;
            }

            // Se o tipo for URL, completar protocolo automaticamente
            const opt = fieldTypeSelect.options[fieldTypeSelect.selectedIndex];
            const inputType = opt ? (opt.getAttribute('data-type') || '') : '';
            if (inputType === 'url' && value && !/^https?:\/\//i.test(value)) {
                value = 'https://' + value;
                fieldValueInput.value = value;
            }
            
            const formData = new FormData();
            formData.append('action', 'add_field');
            formData.append('field_type_id', fieldTypeSelect.value);
            formData.append('value', value);
            
            fetch('edit_profile.php?id=<?php echo $profile_id; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    // Recarregar a página para atualizar a lista
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erro ao adicionar campo!', 'error');
                console.error('Error:', error);
            });
        }
        
        function updateField(fieldId) {
            const fieldItem = document.querySelector(`[data-field-id="${fieldId}"]`);
            const valueInput = fieldItem.querySelector('.field-value');
            const visibleCheckbox = fieldItem.querySelector('.field-visible');
            const clickableCheckbox = fieldItem.querySelector('.field-clickable');
            
            let value = valueInput.value;

            // Se o campo for URL, completar protocolo automaticamente
            const inputType = fieldItem.getAttribute('data-input-type') || '';
            if (inputType === 'url' && value && !/^https?:\/\//i.test(value)) {
                value = 'https://' + value.trim();
                valueInput.value = value;
            }

            const formData = new FormData();
            formData.append('action', 'update_field');
            formData.append('field_id', fieldId);
            formData.append('value', value);
            if (visibleCheckbox.checked) formData.append('is_visible', '1');
            if (clickableCheckbox.checked) formData.append('is_clickable', '1');
            
            fetch('edit_profile.php?id=<?php echo $profile_id; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    // Animação de sucesso no botão
                    const saveBtn = fieldItem.querySelector('.btn-primary');
                    const originalContent = saveBtn.innerHTML;
                    saveBtn.innerHTML = '<i class="fas fa-check"></i>';
                    saveBtn.classList.remove('btn-primary');
                    saveBtn.classList.add('btn-success');
                    
                    setTimeout(() => {
                        saveBtn.innerHTML = originalContent;
                        saveBtn.classList.remove('btn-success');
                        saveBtn.classList.add('btn-primary');
                    }, 1500);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erro ao atualizar campo!', 'error');
                console.error('Error:', error);
            });
        }
        
        function removeField(fieldId) {
            if (!confirm('Tem certeza que deseja remover este campo?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'remove_field');
            formData.append('field_id', fieldId);
            
            fetch('edit_profile.php?id=<?php echo $profile_id; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    const fieldItem = document.querySelector(`[data-field-id="${fieldId}"]`);
                    fieldItem.style.transition = 'all 0.3s ease';
                    fieldItem.style.opacity = '0';
                    fieldItem.style.transform = 'translateX(-20px)';
                    
                    setTimeout(() => {
                        fieldItem.remove();
                        const remaining = document.querySelectorAll('#fields-container [data-field-id]');
                        if (remaining.length === 0) {
                            const emptyMsg = document.getElementById('no-fields-msg');
                            if (emptyMsg) emptyMsg.style.display = '';
                        }
                    }, 300);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Erro ao remover campo!', 'error');
                console.error('Error:', error);
            });
        }
        
        // ── Atualiza input conforme tipo selecionado ────────────────────────
        const fieldHints = {
            whatsapp:      'Número com DDI+DDD, ex: 5511999998888',
            phone:         'Número com DDD, ex: (11) 99999-8888',
            pix:           'CPF, CNPJ, e-mail, telefone ou chave aleatória',
            wifi_password: 'A senha será exibida publicamente no cartão',
            wifi_ssid:     'Nome exato da rede Wi-Fi',
            website:       'URL completa incluindo https://',
            google_review: 'Link direto para avaliação no Google Maps',
        };

        document.getElementById('new-field-type').addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const valueInput = document.getElementById('new-field-value');
            const hintEl = document.getElementById('new-field-hint');
            if (!opt.value) {
                valueInput.disabled = true;
                valueInput.type = 'text';
                valueInput.placeholder = 'Selecione um tipo acima';
                valueInput.value = '';
                hintEl.textContent = '';
                return;
            }
            const inputType = opt.getAttribute('data-type') || 'text';
            const name = opt.getAttribute('data-name') || '';
            valueInput.type = inputType === 'textarea' ? 'text' : inputType;
            valueInput.placeholder = opt.getAttribute('data-placeholder') || 'Digite o valor';
            valueInput.disabled = false;
            hintEl.textContent = fieldHints[name] || '';
            valueInput.focus();
        });
        
        // Tornar funções globais
        window.previewImage = previewImage;
        window.addField = addField;
        window.updateField = updateField;
        window.removeField = removeField;
    </script>
</body>
</html>