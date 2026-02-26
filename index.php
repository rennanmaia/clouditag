<?php
session_start();
require_once 'includes/functions.php';

// Se já está logado, redireciona para dashboard apropriado
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['user_type'] === 'admin_geral') {
        redirect('admin/dashboard.php');
    } else {
        redirect('dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudiTag - Sistema de Gestão de Perfil Empresarial</title>
    <meta name="description" content="Sistema completo para gestão de perfis empresariais e profissionais com links personalizáveis e recursos avançados.">
    <script>(function(){var t=localStorage.getItem('clouditag_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Landing page extras */
        .hero-section {
            background: var(--grad-brand);
            color: white;
            padding: 90px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: var(--brand-green);
            opacity: .07;
            top: -200px; right: -100px;
            pointer-events: none;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            width: 350px; height: 350px;
            border-radius: 50%;
            background: var(--brand-cyan);
            opacity: .08;
            bottom: -150px; left: -80px;
            pointer-events: none;
        }
        .hero-title {
            font-size: clamp(2.2rem, 5vw, 3.8rem);
            font-weight: 800;
            margin-bottom: 18px;
            color: #fff;
            letter-spacing: -.03em;
        }
        .hero-title .accent { color: var(--brand-green); }
        .hero-title i { color: var(--brand-cyan); }
        .hero-subtitle {
            font-size: clamp(1.1rem, 2.5vw, 1.5rem);
            margin-bottom: 14px;
            opacity: .9;
            color: #fff;
        }
        .hero-desc {
            font-size: 1.05rem;
            margin-bottom: 38px;
            opacity: .78;
            max-width: 560px;
            margin-inline: auto;
            color: #fff;
        }
        .hero-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
        .features-section {
            padding: 80px 0;
            background: var(--surface);
        }
        .features-section h2 { color: var(--text); }
        .features-section .lead { color: var(--text-muted); font-size: 1.15rem; max-width: 500px; margin: 0 auto 50px; }
        .feature-card {
            text-align: center;
            padding: 36px 24px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            background: var(--surface);
            transition: var(--transition);
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow);
            border-color: var(--brand-blue);
        }
        .feature-icon {
            font-size: 2.6rem;
            color: var(--brand-blue);
            margin-bottom: 18px;
            display: block;
        }
        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--text);
        }
        .feature-description { color: var(--text-muted); line-height: 1.65; font-size: 14.5px; }
        .cta-section {
            background: var(--bg);
            padding: 80px 0;
            text-align: center;
        }
        .cta-section h2 { color: var(--text); }
        .cta-section p { color: var(--text-muted); }
        .footer {
            background: var(--brand-navy);
            color: rgba(255,255,255,.75);
            padding: 48px 0 28px;
        }
        [data-theme="dark"] .footer { background: #040c1a; }
        .footer h4, .footer h5 { color: #fff; font-size: 1.1rem; margin-bottom: 12px; }
        .footer h4 i { color: var(--brand-cyan); }
        .footer-links { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: 6px; }
        .footer-links a { color: rgba(255,255,255,.65); transition: var(--transition); font-size: 14px; }
        .footer-links a:hover { color: var(--brand-green); opacity: 1; }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,.12); margin-top: 32px; padding-top: 20px; font-size: 13px; }
        @media (max-width: 768px) {
            .hero-section { padding: 60px 0 50px; }
            .features-section, .cta-section { padding: 56px 0; }
            .hero-btns { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-cloud"></i> Cloudi<span class="brand-accent">Tag</span>
            </a>
            <ul class="navbar-nav">
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <li><a href="register.php"><i class="fas fa-user-plus"></i> Cadastrar</a></li>
                <li>
                    <button class="theme-toggle" onclick="toggleTheme()" title="Alternar tema">
                        <i class="fas fa-moon"></i>
                        <span class="theme-label">Modo Escuro</span>
                    </button>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container" style="position:relative;z-index:1;">
            <h1 class="hero-title">
                <i class="fas fa-cloud"></i>
                Cloudi<span class="accent">Tag</span>
            </h1>
            <p class="hero-subtitle">
                Gestão inteligente de perfis empresariais e profissionais
            </p>
            <p class="hero-desc">
                Crie perfis personalizados, gerencie links dinâmicos e centralize todas as suas informações em um só lugar.
            </p>
            <div class="hero-btns">
                <a href="register.php" class="btn btn-lg" style="background:var(--brand-green);color:#fff;border-color:var(--brand-green);">
                    <i class="fas fa-rocket"></i> Começar Gratuitamente
                </a>
                <a href="login.php" class="btn btn-lg" style="background:rgba(255,255,255,.18);color:#fff;border:2px solid rgba(255,255,255,.5);">
                    <i class="fas fa-sign-in-alt"></i> Fazer Login
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Recursos Incríveis</h2>
                <p class="lead">Tudo que você precisa para criar e gerenciar seus perfis profissionais</p>
            </div>
            
            <div class="row">
                <div class="col-4">
                    <div class="feature-card">
                        <i class="fas fa-link feature-icon"></i>
                        <h3 class="feature-title">Links Personalizáveis</h3>
                        <p class="feature-description">
                            Crie e gerencie links dinâmicos para redes sociais, sites, contatos e muito mais. 
                            Adicione ícones personalizados e organize da forma que preferir.
                        </p>
                    </div>
                </div>
                
                <div class="col-4">
                    <div class="feature-card">
                        <i class="fas fa-mobile-alt feature-icon"></i>
                        <h3 class="feature-title">Design Responsivo</h3>
                        <p class="feature-description">
                            Seus perfis ficam perfeitos em qualquer dispositivo. Design moderno, 
                            elegante e otimizado para mobile, tablet e desktop.
                        </p>
                    </div>
                </div>
                
                <div class="col-4">
                    <div class="feature-card">
                        <i class="fas fa-qrcode feature-icon"></i>
                        <h3 class="feature-title">Recursos Avançados</h3>
                        <p class="feature-description">
                            PIX integrado, conexão WiFi via QR Code, localização no mapa, 
                            WhatsApp direto e campos totalmente personalizáveis.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="row mt-5">
                <div class="col-4">
                    <div class="feature-card">
                        <i class="fas fa-users feature-icon"></i>
                        <h3 class="feature-title">Múltiplos Perfis</h3>
                        <p class="feature-description">
                            Crie quantos perfis quiser! Gerencie perfis empresariais e profissionais 
                            de forma independente, cada um com sua identidade única.
                        </p>
                    </div>
                </div>
                
                <div class="col-4">
                    <div class="feature-card">
                        <i class="fas fa-edit feature-icon"></i>
                        <h3 class="feature-title">Fácil de Usar</h3>
                        <p class="feature-description">
                            Interface intuitiva e amigável. Crie e edite seus perfis em minutos, 
                            sem necessidade de conhecimentos técnicos.
                        </p>
                    </div>
                </div>
                
                <div class="col-4">
                    <div class="feature-card">
                        <i class="fas fa-share-alt feature-icon"></i>
                        <h3 class="feature-title">Compartilhamento</h3>
                        <p class="feature-description">
                            URLs personalizadas e fáceis de lembrar. Compartilhe seus perfis 
                            em redes sociais, cartões de visita ou onde quiser.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Pronto para começar?</h2>
            <p style="font-size: 1.1rem; margin-bottom: 36px;">Crie sua conta gratuita agora e comece a gerenciar seus perfis profissionais</p>
            <a href="register.php" class="btn btn-primary btn-lg">
                <i class="fas fa-rocket"></i> Criar Conta Grátis
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-6">
                    <h4><i class="fas fa-cloud"></i> Cloudi<span style="color:var(--brand-green)">Tag</span></h4>
                    <p style="font-size:14px;">Sistema de gestão de perfil empresarial completo e fácil de usar.</p>
                </div>
                <div class="col-6 text-right">
                    <h5>Links Rápidos</h5>
                    <ul class="footer-links" style="text-align:right;">
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Cadastrar</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom text-center">
                &copy; <?php echo date('Y'); ?> CloudiTag. Todos os direitos reservados.
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>