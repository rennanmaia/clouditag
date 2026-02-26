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
                    $posted_type_ids = isset($_POST['field_type_ids']) && is_array($_POST['field_type_ids']) ? $_POST['field_type_ids'] : [];
                    $posted_values   = isset($_POST['field_values'])   && is_array($_POST['field_values'])   ? $_POST['field_values']   : [];

                    // Mapa de tipos para saber quais são URLs
                    $all_types = getFieldTypes(true);
                    $types_by_id = [];
                    foreach ($all_types as $t) {
                        $types_by_id[$t['id']] = $t;
                    }

                    foreach ($posted_type_ids as $idx => $ftype_id) {
                        $fvalue = isset($posted_values[$idx]) ? sanitize($posted_values[$idx]) : '';
                        if ($fvalue === '' || !is_numeric($ftype_id)) {
                            continue;
                        }
                        $ftype_id = (int)$ftype_id;
                        if (isset($types_by_id[$ftype_id]) && $types_by_id[$ftype_id]['input_type'] === 'url') {
                            $fvalue = normalizeUrlValue($fvalue);
                        }
                        updateProfileField($profile_id, $ftype_id, $fvalue);
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
$field_types = getFieldTypes();
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
                                                <span class="input-group-prefix"><?php echo SITE_URL; ?>/profile/</span>
                                                <input type="text" class="form-control slug-input" name="slug" id="slug" 
                                                       value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : ''; ?>" 
                                                       required>
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
                                
                                <!-- ===== CAMPOS DINÂMICOS ===== -->
                                <div style="display:flex;align-items:center;justify-content:space-between;margin:24px 0 8px;">
                                    <div>
                                        <h4 style="margin:0;"><i class="fas fa-list-ul"></i> Campos do Perfil</h4>
                                        <small class="text-muted">Adicione os campos que aparecerão no seu cartão digital</small>
                                    </div>
                                </div>

                                <!-- Linha de adição -->
                                <div id="add-field-bar" style="display:flex;gap:10px;align-items:flex-end;background:var(--gray-50);border:2px dashed var(--gray-300);border-radius:var(--border-radius);padding:16px;margin-bottom:12px;">
                                    <div style="flex:0 0 210px;">
                                        <label style="font-size:.85em;margin-bottom:4px;display:block;">Tipo de Campo</label>
                                        <select id="new-field-type" class="form-control" onchange="onCreateTypeChange(this)">
                                            <option value="">Selecione o tipo&hellip;</option>
                                            <?php foreach ($field_types as $ft): ?>
                                            <option value="<?php echo $ft['id']; ?>"
                                                    data-icon="<?php echo htmlspecialchars($ft['icon']); ?>"
                                                    data-label="<?php echo htmlspecialchars($ft['label']); ?>"
                                                    data-input-type="<?php echo htmlspecialchars($ft['input_type']); ?>"
                                                    data-placeholder="<?php echo htmlspecialchars($ft['placeholder'] ?? ''); ?>"
                                                    data-name="<?php echo htmlspecialchars($ft['name']); ?>">
                                                <?php echo htmlspecialchars($ft['label']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div style="flex:1;">
                                        <label style="font-size:.85em;margin-bottom:4px;display:block;">Valor</label>
                                        <input type="text" id="new-field-value" class="form-control" placeholder="Selecione um tipo acima" disabled>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-success" onclick="addFieldRowCreate()" style="white-space:nowrap;">
                                            <i class="fas fa-plus"></i> Adicionar
                                        </button>
                                    </div>
                                </div>
                                <div id="create-field-messages"></div>

                                <!-- Lista de campos adicionados -->
                                <div id="fields-list">
                                    <div id="fields-empty" class="text-center p-4" style="color:var(--gray-400);border:1px dashed var(--gray-300);border-radius:var(--border-radius);">
                                        <i class="fas fa-info-circle"></i> Nenhum campo adicionado ainda.
                                    </div>
                                </div>
                                
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
    // ── Mapa de tipos de campo (injetado pelo PHP) ──────────────────────────────
    const fieldTypesMeta = <?php
        $ft_map = [];
        foreach ($field_types as $ft) {
            $ft_map[$ft['id']] = [
                'id'          => $ft['id'],
                'name'        => $ft['name'],
                'label'       => $ft['label'],
                'icon'        => $ft['icon'],
                'input_type'  => $ft['input_type'],
                'placeholder' => $ft['placeholder'] ?? '',
            ];
        }
        echo json_encode($ft_map, JSON_UNESCAPED_UNICODE);
    ?>;

    // ── Atualiza o input de valor conforme o tipo selecionado ──────────────────
    function onCreateTypeChange(select) {
        const valueInput = document.getElementById('new-field-value');
        const opt = select.options[select.selectedIndex];
        if (!opt.value) {
            valueInput.value = '';
            valueInput.type = 'text';
            valueInput.placeholder = 'Selecione um tipo acima';
            valueInput.disabled = true;
            return;
        }
        const inputType = opt.getAttribute('data-input-type') || 'text';
        valueInput.type   = inputType === 'textarea' ? 'text' : inputType;
        valueInput.placeholder = opt.getAttribute('data-placeholder') || 'Digite o valor';
        valueInput.disabled = false;
        valueInput.focus();
    }

    // ── Adiciona linha de campo ao formulário ──────────────────────────────────
    let _createFieldIdx = 0;
    function addFieldRowCreate() {
        const typeSelect  = document.getElementById('new-field-type');
        const valueInput  = document.getElementById('new-field-value');
        const typeId      = typeSelect.value;
        let   value       = valueInput.value.trim();

        if (!typeId) { showCreateMsg('Selecione um tipo de campo.', 'error'); return; }
        if (!value)  { showCreateMsg('Digite o valor do campo.', 'error');    return; }

        const opt  = typeSelect.options[typeSelect.selectedIndex];
        const meta = fieldTypesMeta[typeId];
        const icon = opt.getAttribute('data-icon');
        const label = opt.getAttribute('data-label');
        const inputType = opt.getAttribute('data-input-type');
        const hint = meta ? getFieldHint(meta.name) : '';
        const idx  = _createFieldIdx++;

        // Se for campo de URL, completar protocolo automaticamente
        if (inputType === 'url' && value && !/^https?:\/\//i.test(value)) {
            value = 'https://' + value;
        }

        document.getElementById('fields-empty').style.display = 'none';

        const row = document.createElement('div');
        row.className = 'field-row-item';
        row.setAttribute('data-create-type-id', typeId);
        row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:10px 14px;border:1px solid var(--gray-300);border-radius:var(--border-radius);background:var(--card-bg);margin-bottom:8px;';
        row.innerHTML = `
            <span style="width:34px;text-align:center;font-size:1.2em;color:var(--brand-blue);">
                <i class="${icon}"></i>
            </span>
            <span style="min-width:120px;font-weight:600;font-size:.88em;color:var(--text-secondary);">${label}</span>
            <div style="flex:1;">
                <input type="${inputType === 'textarea' ? 'text' : inputType}"
                       name="field_values[]"
                       class="form-control"
                       value="${value.replace(/"/g,'&quot;')}"
                       style="margin:0;">
                ${hint ? `<small style="color:var(--gray-400);font-size:.78em;">${hint}</small>` : ''}
            </div>
            <input type="hidden" name="field_type_ids[]" value="${typeId}">
            <button type="button" class="btn btn-sm btn-danger"
                    onclick="removeFieldRowCreate(this,'${typeId}')"
                    title="Remover campo">
                <i class="fas fa-trash"></i>
            </button>`;
        document.getElementById('fields-list').appendChild(row);

        // Reset add-bar
        typeSelect.value = '';
        valueInput.value = '';
        valueInput.type  = 'text';
        valueInput.placeholder = 'Selecione um tipo acima';
        valueInput.disabled = true;
    }

    function removeFieldRowCreate(btn, typeId) {
        btn.closest('[data-create-type-id]').remove();
        if (!document.querySelector('[data-create-type-id]')) {
            document.getElementById('fields-empty').style.display = '';
        }
    }

    function showCreateMsg(msg, type) {
        const wrap = document.getElementById('create-field-messages');
        wrap.innerHTML = `<div class="alert alert-${type === 'error' ? 'error' : 'success'}" style="margin-bottom:8px;">
            <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i> ${msg}</div>`;
        setTimeout(() => { wrap.innerHTML = ''; }, 3000);
    }

    // ── Dicas contextuais por tipo de campo ────────────────────────────────────
    function getFieldHint(name) {
        const hints = {
            whatsapp:      'Número com DDI+DDD, ex: 5511999998888',
            phone:         'Número com DDD, ex: (11) 99999-8888',
            pix:           'CPF, CNPJ, e-mail, telefone ou chave aleatória',
            wifi_password: 'A senha será exibida publicamente no cartão',
            wifi_ssid:     'Nome exato da rede Wi-Fi',
            website:       'URL completa incluindo https://',
            google_review: 'Link direto para avaliação no Google Maps',
        };
        return hints[name] || '';
    }

    // ── Preview de logo e slug ─────────────────────────────────────────────────
    function previewImage(input, previewId) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById(previewId).src = e.target.result;
            document.getElementById(previewId + '-container').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    document.addEventListener('DOMContentLoaded', function() {
        setupSlugGeneration('name', 'slug');
        window.previewImage = previewImage;
        window.addFieldRowCreate = addFieldRowCreate;
        window.removeFieldRowCreate = removeFieldRowCreate;
        window.onCreateTypeChange = onCreateTypeChange;
    });
    </script>
</body>
</html>