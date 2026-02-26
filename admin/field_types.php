<?php
session_start();
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$user = getCurrentUser();
$db   = getDB();

// ── AJAX handlers ─────────────────────────────────────────────────────────────
if (isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    switch ($_POST['action']) {

        // ── Criar tipo de campo ──────────────────────────────────────────────
        case 'create':
            $name        = trim($_POST['name'] ?? '');
            $label       = trim($_POST['label'] ?? '');
            $icon        = trim($_POST['icon'] ?? '');
            $input_type  = trim($_POST['input_type'] ?? 'text');
            $placeholder = trim($_POST['placeholder'] ?? '');
            $pattern     = trim($_POST['validation_pattern'] ?? '');

            $name = strtolower(preg_replace('/[^a-z0-9_]/', '_', $name));

            if (empty($name) || empty($label)) {
                echo json_encode(['success' => false, 'message' => 'Nome e rótulo são obrigatórios.']);
                exit;
            }

            $valid_types = ['text','email','tel','url','textarea','password'];
            if (!in_array($input_type, $valid_types)) {
                echo json_encode(['success' => false, 'message' => 'Tipo de input inválido.']);
                exit;
            }

            // Verificar duplicata
            $stmt = $db->prepare("SELECT COUNT(*) FROM field_types WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => "Já existe um tipo com o nome \"$name\"."]); 
                exit;
            }

            $stmt = $db->prepare("SELECT COALESCE(MAX(order_index),0)+1 FROM field_types");
            $stmt->execute();
            $order = $stmt->fetchColumn();

            $stmt = $db->prepare("INSERT INTO field_types
                (name, label, icon, input_type, validation_pattern, placeholder, is_system, is_active, order_index, created_at)
                VALUES (?,?,?,?,?,?,0,1,?,NOW())");

            if ($stmt->execute([$name, $label, $icon, $input_type, $pattern ?: null, $placeholder ?: null, $order])) {
                $id = $db->lastInsertId();
                echo json_encode(['success' => true, 'message' => 'Tipo criado com sucesso!', 'id' => $id, 'order' => $order]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao criar tipo de campo.']);
            }
            exit;

        // ── Atualizar tipo de campo ──────────────────────────────────────────
        case 'update':
            $id          = (int)($_POST['id'] ?? 0);
            $label       = trim($_POST['label'] ?? '');
            $icon        = trim($_POST['icon'] ?? '');
            $input_type  = trim($_POST['input_type'] ?? 'text');
            $placeholder = trim($_POST['placeholder'] ?? '');
            $pattern     = trim($_POST['validation_pattern'] ?? '');
            $is_active   = isset($_POST['is_active']) ? 1 : 0;

            if (!$id || empty($label)) {
                echo json_encode(['success' => false, 'message' => 'ID e rótulo são obrigatórios.']);
                exit;
            }

            // Não pode alterar is_system
            $stmt = $db->prepare("UPDATE field_types
                SET label=?, icon=?, input_type=?, validation_pattern=?, placeholder=?, is_active=?
                WHERE id=?");

            if ($stmt->execute([$label, $icon, $input_type, $pattern ?: null, $placeholder ?: null, $is_active, $id])) {
                echo json_encode(['success' => true, 'message' => 'Tipo atualizado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar.']);
            }
            exit;

        // ── Remover tipo de campo ────────────────────────────────────────────
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID inválido.']); exit; }

            // Bloquear deleção de campos do sistema
            $stmt = $db->prepare("SELECT is_system FROM field_types WHERE id=?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            if (!$row) { echo json_encode(['success' => false, 'message' => 'Tipo não encontrado.']); exit; }
            if ($row['is_system']) { echo json_encode(['success' => false, 'message' => 'Tipos do sistema não podem ser excluídos.']); exit; }

            // Verificar uso — avisar se em uso
            $stmt = $db->prepare("SELECT COUNT(*) FROM profile_fields WHERE field_type_id=?");
            $stmt->execute([$id]);
            $usage = $stmt->fetchColumn();
            if ($usage > 0) {
                echo json_encode(['success' => false, 'message' => "Este tipo está em uso em $usage campo(s) de perfis. Desative-o ao invés de excluir."]);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM field_types WHERE id=? AND is_system=0");
            if ($stmt->execute([$id])) {
                echo json_encode(['success' => true, 'message' => 'Tipo removido com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao remover.']);
            }
            exit;

        // ── Reordenar ────────────────────────────────────────────────────────
        case 'reorder':
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            if (!is_array($ids)) { echo json_encode(['success' => false]); exit; }
            $stmt = $db->prepare("UPDATE field_types SET order_index=? WHERE id=?");
            foreach ($ids as $idx => $fid) {
                $stmt->execute([$idx + 1, (int)$fid]);
            }
            echo json_encode(['success' => true]);
            exit;
    }
}

// ── Carregar todos os tipos ───────────────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM field_types ORDER BY order_index ASC, id ASC");
$stmt->execute();
$field_types = $stmt->fetchAll();

$input_types = ['text' => 'Texto', 'email' => 'E-mail', 'tel' => 'Telefone', 'url' => 'URL', 'textarea' => 'Textarea', 'password' => 'Senha'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipos de Campo - Admin CloudiTag</title>
    <script>(function(){var t=localStorage.getItem('clouditag_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .ft-row { display:flex; align-items:center; gap:10px; padding:10px 14px;
                  border:1px solid var(--gray-300); border-radius:var(--border-radius);
                  background:var(--card-bg); margin-bottom:7px; transition:.2s; }
        .ft-row:hover { border-color:var(--brand-blue); box-shadow:0 2px 8px rgba(0,153,229,.1); }
        .ft-drag  { cursor:grab; color:var(--gray-400); padding:0 4px; }
        .ft-drag:active { cursor:grabbing; }
        .ft-icon-preview { width:34px; text-align:center; font-size:1.2em; color:var(--brand-blue); flex-shrink:0; }
        .ft-badge { font-size:.72em; padding:2px 8px; border-radius:20px; font-weight:600;
                    background:var(--gray-100); color:var(--text-secondary); white-space:nowrap; }
        .ft-badge.system { background: rgba(0,153,229,.13); color:var(--brand-blue); }
        .ft-badge.inactive { background:rgba(220,53,69,.1); color:#dc3545; }
        .ft-badge.active   { background:rgba(125,206,0,.15); color:#4a8700; }
        .sortable-ghost { opacity:.4; background:var(--gray-100); }
        .add-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px 16px; }
        @media(max-width:700px){ .add-form-grid { grid-template-columns:1fr; } }
        /* modal */
        .modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;
                         display:none;align-items:center;justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:var(--card-bg);border-radius:var(--border-radius);
                     box-shadow:0 12px 40px rgba(0,0,0,.2);width:100%;max-width:560px;
                     padding:28px 32px; position:relative; }
        .modal-box h3 { margin:0 0 18px; }
        .modal-close { position:absolute;top:14px;right:18px;background:none;border:none;
                       font-size:1.3em;cursor:pointer;color:var(--text-secondary); }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-cloud"></i> Cloudi<span class="brand-accent">Tag</span></h3>
            <p>Admin: <?php echo htmlspecialchars($user['name']); ?></p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Usuários</a></li>
            <li><a href="profiles.php"><i class="fas fa-list"></i> Perfis</a></li>
            <li><a href="field_types.php" class="active"><i class="fas fa-sliders-h"></i> Tipos de Campo</a></li>
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

            <!-- Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-header">
                        <div>
                            <h1><i class="fas fa-sliders-h"></i> Tipos de Campo</h1>
                            <div class="breadcrumb">
                                <a href="dashboard.php">Admin</a> / <span>Tipos de Campo</span>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Novo Tipo
                        </button>
                    </div>
                    <div id="global-msg"></div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <!-- Info box -->
                    <div class="alert" style="border-left:4px solid var(--brand-blue); background:rgba(0,153,229,.07); color:var(--text-primary);">
                        <i class="fas fa-info-circle" style="color:var(--brand-blue);"></i>
                        Gerencie os tipos de campo disponíveis para usuários ao criar ou editar perfis.
                        Tipos marcados com <span class="ft-badge system">sistema</span> são nativos e não podem ser excluídos.
                        Arraste as linhas para reordenar.
                    </div>

                    <!-- Lista -->
                    <div class="card">
                        <div class="card-body" style="padding-bottom:4px;">
                            <div id="ft-list">
                                <?php foreach ($field_types as $ft): ?>
                                <div class="ft-row" data-id="<?php echo $ft['id']; ?>">
                                    <span class="ft-drag"><i class="fas fa-grip-vertical"></i></span>
                                    <span class="ft-icon-preview">
                                        <i class="<?php echo htmlspecialchars($ft['icon'] ?: 'fas fa-tag'); ?>"></i>
                                    </span>
                                    <div style="flex:1;min-width:0;">
                                        <strong style="font-size:.95em;"><?php echo htmlspecialchars($ft['label']); ?></strong>
                                        <span style="color:var(--gray-400);font-size:.8em;margin-left:6px;">
                                            <?php echo htmlspecialchars($ft['name']); ?>
                                        </span>
                                    </div>
                                    <span class="ft-badge"><?php echo $input_types[$ft['input_type']] ?? $ft['input_type']; ?></span>
                                    <?php if ($ft['is_system']): ?>
                                        <span class="ft-badge system"><i class="fas fa-lock"></i> sistema</span>
                                    <?php endif; ?>
                                    <span class="ft-badge <?php echo $ft['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $ft['is_active'] ? 'ativo' : 'inativo'; ?>
                                    </span>
                                    <div style="display:flex;gap:6px;flex-shrink:0;">
                                        <button class="btn btn-sm btn-outline"
                                                onclick="openEditModal(<?php echo htmlspecialchars(json_encode($ft), ENT_QUOTES); ?>)"
                                                title="Editar">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <?php if (!$ft['is_system']): ?>
                                        <button class="btn btn-sm btn-danger"
                                                onclick="deleteType(<?php echo $ft['id']; ?>, '<?php echo htmlspecialchars($ft['label'], ENT_QUOTES); ?>')"
                                                title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-sm" style="opacity:.3;cursor:default;" disabled title="Tipo de sistema">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($field_types)): ?>
                                    <p class="text-center" style="color:var(--gray-400);padding:24px 0;">
                                        Nenhum tipo de campo cadastrado.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Modal: Adicionar ──────────────────────────────────────────────────── -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('addModal')" title="Fechar">
                <i class="fas fa-times"></i>
            </button>
            <h3><i class="fas fa-plus-circle" style="color:var(--brand-green);"></i> Novo Tipo de Campo</h3>
            <div id="add-msg"></div>
            <div class="add-form-grid">
                <div class="form-group" style="margin:0;">
                    <label>Nome interno * <small style="color:var(--gray-400);">(slug, sem espaços)</small></label>
                    <input type="text" id="add-name" class="form-control" placeholder="ex: linkedin"
                           oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9_]/g,'_')">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Rótulo (label) *</label>
                    <input type="text" id="add-label" class="form-control" placeholder="ex: LinkedIn">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Ícone <small style="color:var(--gray-400);">FontAwesome class</small></label>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <input type="text" id="add-icon" class="form-control" placeholder="fab fa-linkedin"
                               oninput="document.getElementById('add-icon-preview').className=this.value||'fas fa-tag'">
                        <span id="add-icon-preview" class="fas fa-tag" style="font-size:1.3em;color:var(--brand-blue);min-width:22px;"></span>
                    </div>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Tipo de Input *</label>
                    <select id="add-input-type" class="form-control">
                        <option value="text">Texto</option>
                        <option value="email">E-mail</option>
                        <option value="tel">Telefone</option>
                        <option value="url">URL</option>
                        <option value="textarea">Textarea</option>
                        <option value="password">Senha</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Placeholder</label>
                    <input type="text" id="add-placeholder" class="form-control" placeholder="ex: https://linkedin.com/in/…">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Padrão de validação <small style="color:var(--gray-400);">(regex, opcional)</small></label>
                    <input type="text" id="add-pattern" class="form-control" placeholder="ex: ^https?:\/\/.+">
                </div>
            </div>
            <div style="margin-top:18px;display:flex;gap:10px;justify-content:flex-end;">
                <button class="btn btn-light" onclick="closeModal('addModal')">Cancelar</button>
                <button class="btn btn-primary" onclick="submitAdd()">
                    <i class="fas fa-save"></i> Criar Tipo
                </button>
            </div>
        </div>
    </div>

    <!-- ── Modal: Editar ────────────────────────────────────────────────────── -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('editModal')" title="Fechar">
                <i class="fas fa-times"></i>
            </button>
            <h3><i class="fas fa-pen" style="color:var(--brand-blue);"></i> Editar Tipo de Campo</h3>
            <div id="edit-msg"></div>
            <input type="hidden" id="edit-id">
            <div class="add-form-grid">
                <div class="form-group" style="margin:0;">
                    <label>Nome interno</label>
                    <input type="text" id="edit-name" class="form-control" disabled
                           style="background:var(--gray-100);color:var(--text-secondary);">
                    <small style="color:var(--gray-400);">Não editável após criação</small>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Rótulo *</label>
                    <input type="text" id="edit-label" class="form-control">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Ícone</label>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <input type="text" id="edit-icon" class="form-control"
                               oninput="document.getElementById('edit-icon-preview').className=this.value||'fas fa-tag'">
                        <span id="edit-icon-preview" class="fas fa-tag" style="font-size:1.3em;color:var(--brand-blue);min-width:22px;"></span>
                    </div>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Tipo de Input *</label>
                    <select id="edit-input-type" class="form-control">
                        <option value="text">Texto</option>
                        <option value="email">E-mail</option>
                        <option value="tel">Telefone</option>
                        <option value="url">URL</option>
                        <option value="textarea">Textarea</option>
                        <option value="password">Senha</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Placeholder</label>
                    <input type="text" id="edit-placeholder" class="form-control">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Padrão de validação</label>
                    <input type="text" id="edit-pattern" class="form-control">
                </div>
                <div class="form-group" style="margin:0;grid-column:span 2;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" id="edit-active" style="width:16px;height:16px;">
                        Ativo (visível para usuários ao adicionar campos)
                    </label>
                </div>
            </div>
            <div style="margin-top:18px;display:flex;gap:10px;justify-content:flex-end;">
                <button class="btn btn-light" onclick="closeModal('editModal')">Cancelar</button>
                <button class="btn btn-primary" onclick="submitEdit()">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </div>
    </div>

    <!-- Sortable.js CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
    // ── Helpers ──────────────────────────────────────────────────────────────
    function showMsg(targetId, msg, type) {
        const el = document.getElementById(targetId);
        el.innerHTML = `<div class="alert alert-${type==='error'?'error':'success'}" style="margin-bottom:10px;">
            <i class="fas fa-${type==='error'?'exclamation-triangle':'check-circle'}"></i> ${msg}</div>`;
        if (type !== 'error') setTimeout(() => el.innerHTML = '', 3500);
    }

    function openModal(id)  { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }

    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(o => {
        o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
    });

    // ── Adicionar ────────────────────────────────────────────────────────────
    function openAddModal() {
        ['add-name','add-label','add-icon','add-placeholder','add-pattern'].forEach(id => {
            document.getElementById(id).value = '';
        });
        document.getElementById('add-input-type').value = 'text';
        document.getElementById('add-icon-preview').className = 'fas fa-tag';
        document.getElementById('add-msg').innerHTML = '';
        openModal('addModal');
        setTimeout(() => document.getElementById('add-name').focus(), 80);
    }

    function submitAdd() {
        const fd = new FormData();
        fd.append('action',             'create');
        fd.append('name',               document.getElementById('add-name').value.trim());
        fd.append('label',              document.getElementById('add-label').value.trim());
        fd.append('icon',               document.getElementById('add-icon').value.trim());
        fd.append('input_type',         document.getElementById('add-input-type').value);
        fd.append('placeholder',        document.getElementById('add-placeholder').value.trim());
        fd.append('validation_pattern', document.getElementById('add-pattern').value.trim());

        fetch('field_types.php', { method:'POST', body:fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeModal('addModal');
                    showMsg('global-msg', data.message, 'success');
                    appendRow({
                        id:         data.id,
                        name:       document.getElementById('add-name').value.trim(),
                        label:      document.getElementById('add-label').value.trim(),
                        icon:       document.getElementById('add-icon').value.trim(),
                        input_type: document.getElementById('add-input-type').value,
                        placeholder:document.getElementById('add-placeholder').value.trim(),
                        validation_pattern: document.getElementById('add-pattern').value.trim(),
                        is_system:  0,
                        is_active:  1,
                        order_index:data.order
                    });
                } else {
                    showMsg('add-msg', data.message, 'error');
                }
            })
            .catch(() => showMsg('add-msg', 'Erro de comunicação.', 'error'));
    }

    // ── Editar ───────────────────────────────────────────────────────────────
    function openEditModal(ft) {
        document.getElementById('edit-id').value          = ft.id;
        document.getElementById('edit-name').value        = ft.name;
        document.getElementById('edit-label').value       = ft.label;
        document.getElementById('edit-icon').value        = ft.icon || '';
        document.getElementById('edit-input-type').value  = ft.input_type;
        document.getElementById('edit-placeholder').value = ft.placeholder || '';
        document.getElementById('edit-pattern').value     = ft.validation_pattern || '';
        document.getElementById('edit-active').checked   = ft.is_active == 1;
        document.getElementById('edit-icon-preview').className = ft.icon || 'fas fa-tag';
        document.getElementById('edit-msg').innerHTML = '';
        openModal('editModal');
        setTimeout(() => document.getElementById('edit-label').focus(), 80);
    }

    function submitEdit() {
        const fd = new FormData();
        fd.append('action',             'update');
        fd.append('id',                 document.getElementById('edit-id').value);
        fd.append('label',              document.getElementById('edit-label').value.trim());
        fd.append('icon',               document.getElementById('edit-icon').value.trim());
        fd.append('input_type',         document.getElementById('edit-input-type').value);
        fd.append('placeholder',        document.getElementById('edit-placeholder').value.trim());
        fd.append('validation_pattern', document.getElementById('edit-pattern').value.trim());
        if (document.getElementById('edit-active').checked) fd.append('is_active','1');

        fetch('field_types.php', { method:'POST', body:fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeModal('editModal');
                    showMsg('global-msg', data.message, 'success');
                    updateRow(document.getElementById('edit-id').value, {
                        label:      document.getElementById('edit-label').value.trim(),
                        icon:       document.getElementById('edit-icon').value.trim(),
                        input_type: document.getElementById('edit-input-type').value,
                        is_active:  document.getElementById('edit-active').checked ? 1 : 0,
                    });
                } else {
                    showMsg('edit-msg', data.message, 'error');
                }
            })
            .catch(() => showMsg('edit-msg', 'Erro de comunicação.', 'error'));
    }

    // ── Excluir ──────────────────────────────────────────────────────────────
    function deleteType(id, label) {
        if (!confirm(`Excluir o tipo "${label}"?\nEsta ação é irreversível.`)) return;

        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);

        fetch('field_types.php', { method:'POST', body:fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showMsg('global-msg', data.message, 'success');
                    const row = document.querySelector(`[data-id="${id}"]`);
                    if (row) {
                        row.style.transition = 'all .25s';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(20px)';
                        setTimeout(() => row.remove(), 250);
                    }
                } else {
                    showMsg('global-msg', data.message, 'error');
                }
            })
            .catch(() => showMsg('global-msg', 'Erro de comunicação.', 'error'));
    }

    // ── DOM helpers ──────────────────────────────────────────────────────────
    const inputTypeLabels = {text:'Texto',email:'E-mail',tel:'Telefone',url:'URL',textarea:'Textarea',password:'Senha'};

    function appendRow(ft) {
        const list = document.getElementById('ft-list');
        // Remove empty message if present
        list.querySelectorAll('p').forEach(p => p.remove());
        const div = document.createElement('div');
        div.className = 'ft-row';
        div.setAttribute('data-id', ft.id);
        div.innerHTML = rowHTML(ft);
        list.appendChild(div);
    }

    function updateRow(id, changes) {
        const row = document.querySelector(`[data-id="${id}"]`);
        if (!row) return;
        // Update icon preview
        const iconEl = row.querySelector('.ft-icon-preview i');
        if (iconEl && changes.icon !== undefined) iconEl.className = changes.icon || 'fas fa-tag';
        // Update label
        const strong = row.querySelector('strong');
        if (strong && changes.label) strong.textContent = changes.label;
        // Update input_type badge
        const badges = row.querySelectorAll('.ft-badge');
        badges.forEach(b => {
            const txt = b.textContent.trim();
            if (inputTypeLabels[Object.keys(inputTypeLabels).find(k => inputTypeLabels[k] === txt)] !== undefined) {
                b.textContent = inputTypeLabels[changes.input_type] || changes.input_type;
            }
            if (txt === 'ativo' || txt === 'inativo') {
                b.textContent = changes.is_active ? 'ativo' : 'inativo';
                b.className   = 'ft-badge ' + (changes.is_active ? 'active' : 'inactive');
            }
        });
        // Update edit button data
        const editBtn = row.querySelector('button[onclick^="openEditModal"]');
        if (editBtn) {
            // Rebuild from existing ft data merged with changes
            const current = JSON.parse(editBtn.getAttribute('onclick').replace(/^openEditModal\(/, '').replace(/\)$/, ''));
            Object.assign(current, changes);
            editBtn.setAttribute('onclick', `openEditModal(${JSON.stringify(current)})`);
        }
    }

    function rowHTML(ft) {
        return `
            <span class="ft-drag"><i class="fas fa-grip-vertical"></i></span>
            <span class="ft-icon-preview"><i class="${ft.icon||'fas fa-tag'}"></i></span>
            <div style="flex:1;min-width:0;">
                <strong style="font-size:.95em;">${ft.label}</strong>
                <span style="color:var(--gray-400);font-size:.8em;margin-left:6px;">${ft.name}</span>
            </div>
            <span class="ft-badge">${inputTypeLabels[ft.input_type]||ft.input_type}</span>
            <span class="ft-badge ${ft.is_active?'active':'inactive'}">${ft.is_active?'ativo':'inativo'}</span>
            <div style="display:flex;gap:6px;flex-shrink:0;">
                <button class="btn btn-sm btn-outline"
                        onclick='openEditModal(${JSON.stringify(ft)})' title="Editar">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="btn btn-sm btn-danger"
                        onclick="deleteType(${ft.id},'${ft.label.replace(/'/g,"\\'")}')" title="Excluir">
                    <i class="fas fa-trash"></i>
                </button>
            </div>`;
    }

    // ── Drag-and-drop reorder (Sortable.js) ──────────────────────────────────
    Sortable.create(document.getElementById('ft-list'), {
        handle: '.ft-drag',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd() {
            const ids = [...document.querySelectorAll('#ft-list [data-id]')].map(el => el.dataset.id);
            const fd = new FormData();
            fd.append('action', 'reorder');
            fd.append('ids', JSON.stringify(ids));
            fetch('field_types.php', { method:'POST', body:fd }).catch(console.error);
        }
    });
    </script>
</body>
</html>
