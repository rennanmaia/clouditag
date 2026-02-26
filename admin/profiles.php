<?php
session_start();
require_once '../includes/functions.php';

// Apenas admin geral tem acesso à área administrativa completa
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$user = getCurrentUser();
$db   = getDB();

// Filtro de busca
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT p.*, u.name AS owner_name, u.email AS owner_email
        FROM profiles p
        JOIN users u ON p.user_id = u.id";
$params = [];

if ($search !== '') {
    $sql .= " WHERE p.name LIKE ? OR p.slug LIKE ? OR u.email LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$profiles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfis - Admin CloudiTag</title>
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
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Usuários</a></li>
            <li><a href="profiles.php" class="active"><i class="fas fa-list"></i> Perfis</a></li>
            <li><a href="field_types.php"><i class="fas fa-sliders-h"></i> Tipos de Campo</a></li>
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
                            <h1><i class="fas fa-list"></i> Perfis</h1>
                            <div class="breadcrumb">
                                <a href="dashboard.php">Admin</a> / <span>Perfis</span>
                            </div>
                        </div>
                        <form method="get" class="d-flex" style="gap:8px;align-items:center;">
                            <input type="text" name="q" class="form-control" placeholder="Buscar por nome, slug ou e-mail" value="<?php echo htmlspecialchars($search); ?>" style="max-width:260px;">
                            <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-search"></i> Buscar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($profiles)): ?>
                                <p class="text-center" style="color:var(--gray-500);margin:16px 0;">
                                    Nenhum perfil encontrado.
                                </p>
                            <?php else: ?>
                                <!-- Visão em tabela (desktop) -->
                                <div class="admin-profiles-table">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nome</th>
                                                <th>Slug</th>
                                                <th>Dono</th>
                                                <th>E-mail</th>
                                                <th>Status</th>
                                                <th>Criado em</th>
                                                <th style="width:170px;">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($profiles as $p): ?>
                                                <tr>
                                                    <td>#<?php echo (int)$p['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($p['slug']); ?></td>
                                                    <td><?php echo htmlspecialchars($p['owner_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($p['owner_email']); ?></td>
                                                    <td>
                                                        <?php if ($p['is_active']): ?>
                                                            <span class="badge badge-success">Ativo</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">Inativo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $p['created_at'] ? date('d/m/Y H:i', strtotime($p['created_at'])) : '-'; ?></td>
                                                    <td>
                                                        <a href="../profile/<?php echo urlencode($p['slug']); ?>" target="_blank" class="btn btn-sm btn-light" title="Ver público">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                        <a href="../edit_profile.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-primary" title="Editar perfil">
                                                            <i class="fas fa-edit"></i> Editar
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Visão em cards (mobile) -->
                                <div class="admin-profiles-cards">
                                    <?php foreach ($profiles as $p): ?>
                                        <div class="admin-profile-card">
                                            <div class="admin-profile-card-header">
                                                <div>
                                                    <div class="admin-profile-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                                    <div class="admin-profile-slug"><?php echo htmlspecialchars($p['slug']); ?></div>
                                                </div>
                                                <div>
                                                    <?php if ($p['is_active']): ?>
                                                        <span class="badge badge-success">Ativo</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Inativo</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="admin-profile-card-body">
                                                <div class="admin-profile-row"><span class="label">ID</span><span class="value">#<?php echo (int)$p['id']; ?></span></div>
                                                <div class="admin-profile-row"><span class="label">Dono</span><span class="value"><?php echo htmlspecialchars($p['owner_name']); ?></span></div>
                                                <div class="admin-profile-row"><span class="label">E-mail</span><span class="value"><?php echo htmlspecialchars($p['owner_email']); ?></span></div>
                                                <div class="admin-profile-row"><span class="label">Criado em</span><span class="value"><?php echo $p['created_at'] ? date('d/m/Y H:i', strtotime($p['created_at'])) : '-'; ?></span></div>
                                            </div>
                                            <div class="admin-profile-card-actions">
                                                <a href="../profile/<?php echo urlencode($p['slug']); ?>" target="_blank" class="btn btn-sm btn-light" title="Ver público">
                                                    <i class="fas fa-external-link-alt"></i> Ver
                                                </a>
                                                <a href="../edit_profile.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-primary" title="Editar perfil">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
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

    <script src="../assets/js/script.js"></script>
</body>
</html>
