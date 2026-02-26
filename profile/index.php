<?php
require_once '../includes/functions.php';

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
    <script>(function(){var t=localStorage.getItem('clouditag_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <style>
        /* Profile public page - centered card layout */
        body.profile-page {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            min-height: 100vh;
            padding: 0;
        }
        .profile-main {
            width: 100%;
            max-width: 580px;
            min-height: 100vh;
        }
        .profile-card {
            border-radius: 0;
            min-height: 100vh;
            box-shadow: var(--shadow-lg);
        }
        .profile-header {
            padding: 44px 28px 28px;
        }
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
        .profile-name { color: #fff; }
        .profile-description { color: rgba(255,255,255,.8); }
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
        .profile-header { position: relative; }
        .profile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }
        @media (max-width: 480px) {
            .profile-info { grid-template-columns: 1fr; }
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
<body class="profile-page">
    <button class="theme-toggle-float" onclick="toggleTheme()" title="Alternar tema">
        <i class="fas fa-moon"></i>
        <span class="theme-label">Modo Escuro</span>
    </button>
    <div class="profile-main">
        <div class="profile-card">
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
            <div class="profile-body">
                <?php if (!empty($profile_fields)): ?>
                    <div class="profile-info">
                        <?php foreach ($profile_fields as $field): ?>
                            <?php if ($field['is_clickable'] && in_array($field['input_type'], ['url', 'email', 'tel'])): ?>
                                <a href="<?php 
                                    switch($field['input_type']) {
                                        case 'email':
                                            echo 'mailto:' . htmlspecialchars($field['value']);
                                            break;
                                        case 'tel':
                                            if ($field['field_name'] === 'whatsapp') {
                                                echo '#" onclick="openWhatsApp(\'' . preg_replace('/[^0-9]/', '', $field['value']) . '\', \'Olá! Vi seu perfil no CloudiTag.\')';
                                            } else {
                                                echo 'tel:' . preg_replace('/[^0-9]/', '', $field['value']);
                                            }
                                            break;
                                        case 'url':
                                            if ($field['field_name'] === 'address') {
                                                echo '#" onclick="openMaps(\'' . htmlspecialchars($field['value']) . '\')';
                                            } elseif ($field['field_name'] === 'pix') {
                                                echo '#" onclick="copyToClipboard(\'' . htmlspecialchars($field['value']) . '\')';
                                            } elseif ($field['field_name'] === 'wifi_ssid') {
                                                $wifi_password = '';
                                                foreach ($profile_fields as $pf) {
                                                    if ($pf['field_name'] === 'wifi_password') {
                                                        $wifi_password = $pf['value'];
                                                        break;
                                                    }
                                                }
                                                echo '#" onclick="connectWiFi(\'' . htmlspecialchars($field['value']) . '\', \'" . htmlspecialchars($wifi_password) . "\')';
                                            } else {
                                                echo htmlspecialchars($field['value']);
                                            }
                                            break;
                                        default:
                                            echo htmlspecialchars($field['value']);
                                    }
                                ?>" 
                                <?php if ($field['input_type'] === 'url' && !in_array($field['field_name'], ['address', 'pix', 'wifi_ssid'])): ?>
                                    target="_blank"
                                <?php endif; ?>
                                class="info-item">
                                    <?php if ($field['icon']): ?>
                                        <i class="<?php echo htmlspecialchars($field['icon']); ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-info-circle"></i>
                                    <?php endif; ?>
                                    <span>
                                        <?php 
                                        if ($field['input_type'] === 'tel') {
                                            echo formatPhone($field['value']);
                                        } elseif ($field['field_name'] === 'pix') {
                                            echo 'PIX: ' . htmlspecialchars($field['value']);
                                        } elseif ($field['field_name'] === 'wifi_ssid') {
                                            echo 'WiFi: ' . htmlspecialchars($field['value']);
                                        } else {
                                            echo htmlspecialchars($field['value']);
                                        }
                                        ?>
                                    </span>
                                </a>
                            <?php else: ?>
                                <div class="info-item">
                                    <?php if ($field['icon']): ?>
                                        <i class="<?php echo htmlspecialchars($field['icon']); ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-info-circle"></i>
                                    <?php endif; ?>
                                    <span><?php echo nl2br(htmlspecialchars($field['value'])); ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
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