<?php
require_once '../includes/functions.php';

// Garante que colunas necessárias existam (para bases antigas)
ensureProfileLayoutColumn();
ensureProfileThemeAndColorsColumns();

// Obter slug da URL
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    die('Perfil não encontrado');
}

// Obter dados do perfil
$profile = getProfileBySlug($slug);

if (empty($profile)) {
    header("HTTP/1.0 404 Not Found");
    die('Perfil não encontrado');
}

// Obter campos personalizados
$profile_fields = getProfileFields($profile['id']);

// Obter links personalizados
$links = getProfileLinks($profile['id']);

// Layout do cartão público
$layout_options = getProfileLayoutOptions();
$layout = isset($profile['layout_template']) ? $profile['layout_template'] : 'classic';
if (!isset($layout_options[$layout])) {
    $layout = 'classic';
}

// Cores de degradê e tema padrão por perfil
$gradient_start = $profile['gradient_start'] ?? '#0099e5';
$gradient_end   = $profile['gradient_end']   ?? '#00c9f5';
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $gradient_start)) {
    $gradient_start = '#0099e5';
}
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $gradient_end)) {
    $gradient_end = '#00c9f5';
}
$profile_theme = (isset($profile['theme_mode']) && $profile['theme_mode'] === 'dark') ? 'dark' : 'light';

// Helper para renderizar um item de informação do perfil
// $variant: 'card' (default, usado nos 4 primeiros do highlight e nos outros layouts)
//           'link' (usado para os demais itens no layout highlight, visual de link comum)
function renderProfileFieldItemPublic(array $field, string $wifi_password_value = '', string $variant = 'card'): void {
    // Campo de senha de Wi-Fi não aparece diretamente
    if (($field['field_name'] ?? '') === 'wifi_password') {
        return;
    }

    $tag     = 'div';
    $href    = '';
    $extra   = '';
    $value   = $field['value'] ?? '';
    $display = htmlspecialchars($value);
    $icon    = $field['icon'] ?: 'fas fa-info-circle';

    switch ($field['field_name']) {
        case 'whatsapp':
            $tag   = 'a';
            $num   = preg_replace('/[^0-9]/', '', $value);
            $href  = "#\" onclick=\"openWhatsApp('{$num}', 'Olá! Vi seu perfil no CloudiTag.')";
            $display = formatPhone($value);
            break;
        case 'phone':
            $tag   = 'a';
            $num   = preg_replace('/[^0-9]/', '', $value);
            $href  = "tel:{$num}";
            $display = formatPhone($value);
            break;
        case 'email':
            $tag   = 'a';
            $href  = 'mailto:' . htmlspecialchars($value);
            break;
        case 'pix':
            $tag   = 'a';
            $val   = htmlspecialchars($value, ENT_QUOTES);
            $href  = "#\" onclick=\"copyToClipboard('{$val}')";
            $display = 'PIX: ' . htmlspecialchars($value);
            // Ícone estilizado para PIX (QR Code)
            $icon   = 'fas fa-qrcode';
            break;
        case 'address':
            $tag   = 'a';
            $href  = "#\" onclick=\"openMaps('" . htmlspecialchars($value, ENT_QUOTES) . "')";
            $display = htmlspecialchars($value);
            break;
        case 'wifi_ssid':
            $tag   = 'a';
            $ssid  = htmlspecialchars($value, ENT_QUOTES);
            $pw    = htmlspecialchars($wifi_password_value, ENT_QUOTES);
            $href  = "#\" onclick=\"connectWiFi('{$ssid}', '{$pw}')";
            $display = 'Wi-Fi: ' . htmlspecialchars($value);
            break;
        default:
            if (($field['input_type'] ?? '') === 'url' && !empty($field['is_clickable'])) {
                $tag   = 'a';
                $href  = htmlspecialchars($value);
                $extra = 'target="_blank"';
            }
    }

    if ($variant === 'link') {
        // Estilo de link normal
        if ($tag === 'a') {
            echo '<a href="' . $href . '" ' . $extra . ' class="link-item">';
        } else {
            echo '<div class="link-item">';
        }
        echo '<i class="' . htmlspecialchars($icon) . '"></i>';
        echo '<span>' . nl2br($display) . '</span>';
        if ($tag === 'a') {
            echo '</a>';
        } else {
            echo '</div>';
        }
    } else {
        // Estilo card/plaquinha (ícone grande em cima, valor embaixo)
        if ($tag === 'a') {
            echo '<a href="' . $href . '" ' . $extra . ' class="info-item">';
        } else {
            echo '<div class="info-item">';
        }
        echo '<i class="' . htmlspecialchars($icon) . '"></i>';
        echo '<span>' . nl2br($display) . '</span>';
        if ($tag === 'a') {
            echo '</a>';
        } else {
            echo '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile['name']); ?> - CloudiTag</title>
    <meta name="description" content="<?php echo htmlspecialchars($profile['description']); ?>">
    <!-- Open Graph & Twitter Card -->
    <meta property="og:title" content="<?php echo htmlspecialchars($profile['name']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($profile['description']); ?>">
    <meta property="og:type" content="business.business">
    <meta property="og:url" content="<?php echo SITE_URL . '/profile/' . $profile['slug']; ?>">
    <?php if ($profile['logo']): ?>
        <meta property="og:image" content="<?php echo SITE_URL . '/uploads/profiles/' . $profile['logo']; ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($profile['name']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($profile['description']); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script>(function(){var d='<?php echo $profile_theme; ?>';var t=localStorage.getItem('clouditag_theme')||d;document.documentElement.setAttribute('data-theme',t);})();</script>
    <style>
        /* Base da página de perfil */
        body.profile-page {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            min-height: 100vh;
            padding: 0;
        }
        .profile-main {
            width: 100%;
            max-width: 620px;
            min-height: 100vh;
        }
        .profile-card {
            border-radius: 0;
            min-height: 100vh;
            box-shadow: var(--shadow-lg);
        }
        .profile-header {
            background: linear-gradient(135deg, <?php echo htmlspecialchars($gradient_start); ?> 0%, <?php echo htmlspecialchars($gradient_end); ?> 100%);
        }
        .profile-share-btn {
            position: absolute;
            top: 18px; right: 18px;
            background: rgba(255,255,255,.25);
            border: none;
            border-radius: 50%;
            width: 42px; height: 42px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            color: #fff;
            cursor: pointer;
            transition: var(--transition);
            backdrop-filter: blur(4px);
        }
        .profile-share-btn:hover { background: rgba(255,255,255,.4); transform: none; }

        /* Logo comum */
        .profile-logo {
            width: 110px; height: 110px;
            border-radius: 50%;
            margin: 0 auto 16px;
            border: 4px solid rgba(255,255,255,.35);
            box-shadow: 0 4px 20px rgba(0,0,0,.2);
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: rgba(255,255,255,.85);
            background: rgba(255,255,255,.18);
        }
        .profile-logo img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

        /* Layout clássico (mantém o visual atual) */
        body.layout-classic .profile-header {
            padding: 44px 28px 28px;
        }
        body.layout-classic .profile-name {
            color: #fff;
        }
        body.layout-classic .profile-description {
            color: rgba(255,255,255,.8);
        }
        body.layout-classic .profile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }

        /* Layout destaque: hero grande com botões de ação */
        body.layout-highlight .profile-card {
            min-height: 100vh;
        }
        body.layout-highlight .profile-header {
            padding: 52px 28px 32px;
            text-align: left;
        }
        body.layout-highlight .profile-logo {
            margin: 0 0 16px 0;
        }
        body.layout-highlight .profile-header-inner {
            display: flex;
            gap: 18px;
            align-items: center;
        }
        body.layout-highlight .profile-name {
            color: #fff;
            margin-bottom: 6px;
        }
        body.layout-highlight .profile-description {
            color: rgba(255,255,255,.85);
            font-size: 0.95rem;
        }
        body.layout-highlight .profile-body {
            padding-top: 10px;
        }

        /* Grade principal com até 4 ícones grandes */
        body.layout-highlight .profile-primary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        body.layout-highlight .profile-primary-grid .info-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 18px 12px;
            border-radius: 18px;
            text-align: center;
        }
        body.layout-highlight .profile-primary-grid .info-item i {
            font-size: 1.8rem;
            margin-bottom: 6px;
        }
        body.layout-highlight .profile-primary-grid .info-item span {
            font-size: 0.8rem;
            opacity: .9;
        }

        /* Lista secundária com botões menores */
        body.layout-highlight .profile-secondary {
            margin-top: 4px;
        }
        body.layout-highlight .profile-secondary .info-item {
            border-radius: 999px;
        }

        @media (max-width: 480px) {
            body.layout-highlight .profile-header-inner {
                flex-direction: column;
                align-items: flex-start;
            }
            body.layout-highlight .profile-primary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* Layout minimalista: informações em lista limpa */
        body.layout-minimal .profile-card {
            box-shadow: none;
        }
        body.layout-minimal .profile-header {
            padding: 32px 20px 16px;
            text-align: center;
        }
        body.layout-minimal .profile-logo {
            width: 90px;
            height: 90px;
            margin-bottom: 12px;
        }
        body.layout-minimal .profile-name {
            color: var(--text-primary);
        }
        body.layout-minimal .profile-description {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        body.layout-minimal .profile-body {
            padding: 16px 16px 24px;
        }
        body.layout-minimal .profile-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        body.layout-minimal .info-item {
            border-radius: 10px;
            background: var(--card-bg-alt);
        }

        @media (max-width: 480px) {
            .profile-body { padding: 20px 16px; }
        }

        .profile-footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 13px;
        }
        .profile-footer a { color: var(--brand-blue); }
    </style>
</head>
<body class="profile-page layout-<?php echo htmlspecialchars($layout); ?>">
    <button class="theme-toggle-float" onclick="toggleTheme()" title="Alternar tema">
        <i class="fas fa-moon"></i>
        <span class="theme-label">Modo Escuro</span>
    </button>
    <div class="profile-main">
        <div class="profile-card">
            <?php if ($layout === 'highlight'): ?>
                <div class="profile-header">
                    <div class="profile-header-inner">
                        <?php if ($profile['logo']): ?>
                            <img src="../uploads/profiles/<?php echo htmlspecialchars($profile['logo']); ?>" 
                                 alt="<?php echo htmlspecialchars($profile['name']); ?>" class="profile-logo">
                        <?php else: ?>
                            <div class="profile-logo">
                                <i class="fas fa-<?php echo $profile['profile_type'] === 'empresa' ? 'building' : 'user'; ?>"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h1 class="profile-name"><?php echo htmlspecialchars($profile['name']); ?></h1>
                            <?php if ($profile['description']): ?>
                                <p class="profile-description"><?php echo nl2br(htmlspecialchars($profile['description'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button class="profile-share-btn" onclick="shareProfile('<?php echo SITE_URL . '/profile/' . $profile['slug']; ?>', '<?php echo htmlspecialchars($profile['name']); ?>')" title="Compartilhar perfil">
                        <i class="fas fa-share-alt"></i>
                    </button>
                </div>
            <?php else: ?>
                <div class="profile-header">
                    <?php if ($profile['logo']): ?>
                        <img src="../uploads/profiles/<?php echo htmlspecialchars($profile['logo']); ?>" 
                             alt="<?php echo htmlspecialchars($profile['name']); ?>" class="profile-logo">
                    <?php else: ?>
                        <div class="profile-logo">
                            <i class="fas fa-<?php echo $profile['profile_type'] === 'empresa' ? 'building' : 'user'; ?>"></i>
                        </div>
                    <?php endif; ?>
                    <button class="profile-share-btn" onclick="shareProfile('<?php echo SITE_URL . '/profile/' . $profile['slug']; ?>', '<?php echo htmlspecialchars($profile['name']); ?>')" title="Compartilhar perfil">
                        <i class="fas fa-share-alt"></i>
                    </button>
                    <h1 class="profile-name"><?php echo htmlspecialchars($profile['name']); ?></h1>
                    <?php if ($profile['description']): ?>
                        <p class="profile-description"><?php echo nl2br(htmlspecialchars($profile['description'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="profile-body">
                <?php if (!empty($profile_fields)): ?>
                    <?php
                    // Pre-indexar wifi_password para uso no wifi_ssid
                    $wifi_password_value = '';
                    foreach ($profile_fields as $pf) {
                        if ($pf['field_name'] === 'wifi_password') {
                            $wifi_password_value = $pf['value'];
                            break;
                        }
                    }

                    if ($layout === 'highlight') {
                        // Separar campos em principais (até 4) e restantes
                        $renderable_fields = [];
                        foreach ($profile_fields as $pf) {
                            if ($pf['field_name'] === 'wifi_password') {
                                continue;
                            }
                            $renderable_fields[] = $pf;
                        }

                        $primary_fields   = array_slice($renderable_fields, 0, 4);
                        $secondary_fields = array_slice($renderable_fields, 4);
                    ?>
                        <?php if (!empty($primary_fields)): ?>
                            <div class="profile-primary-grid">
                                <?php foreach ($primary_fields as $field): ?>
                                    <?php renderProfileFieldItemPublic($field, $wifi_password_value, 'card'); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($secondary_fields)): ?>
                            <div class="profile-links profile-secondary">
                                <?php foreach ($secondary_fields as $field): ?>
                                    <?php renderProfileFieldItemPublic($field, $wifi_password_value, 'link'); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php } else { ?>
                        <div class="profile-info">
                            <?php foreach ($profile_fields as $field): ?>
                                <?php renderProfileFieldItemPublic($field, $wifi_password_value, 'card'); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php } ?>
                <?php endif; ?>
                <?php if (!empty($links)): ?>
                    <div class="profile-links">
                        <?php foreach ($links as $link): ?>
                            <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" class="link-item">
                                <?php if ($link['icon']): ?>
                                    <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-link"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($link['title']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="profile-footer">
                    <p>Powered by <a href="<?php echo SITE_URL; ?>" target="_blank" style="color: #4f8cff;">CloudiTag</a></p>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.3/qrcode.min.js"></script>
    <script>
        // Analytics básico (opcional)
        document.addEventListener('DOMContentLoaded', function() {
            fetch('../api/track_view.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ profile_id: <?php echo $profile['id']; ?>, type: 'view' })
            }).catch(console.error);
        });
        document.querySelectorAll('.link-item, .info-item a').forEach(link => {
            link.addEventListener('click', function() {
                const linkText = this.textContent.trim();
                fetch('../api/track_view.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ profile_id: <?php echo $profile['id']; ?>, type: 'click', link: linkText })
                }).catch(console.error);
            });
        });
        // Compartilhar perfil
        function shareProfile(url, name) {
            if (navigator.share) {
                navigator.share({ title: name, url: url });
            } else {
                navigator.clipboard.writeText(url);
                alert('Link copiado!');
            }
        }
    </script>
</body>
</html>