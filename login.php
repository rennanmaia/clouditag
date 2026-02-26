<?php
session_start();
require_once 'includes/functions.php';

$error_message = '';

// Se já está logado, redireciona
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['user_type'] === 'admin_geral') {
        redirect('admin/dashboard.php');
    } else {
        redirect('dashboard.php');
    }
}

if ($_POST) {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (validateLogin($email, $password)) {
        $user = getCurrentUser();
        if ($user['user_type'] === 'admin_geral') {
            redirect('admin/dashboard.php');
        } else {
            redirect('dashboard.php');
        }
    } else {
        $error_message = 'Email ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CloudiTag</title>
    <script>(function(){var t=localStorage.getItem('clouditag_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <!-- Theme toggle -->
    <button class="theme-toggle-float" onclick="toggleTheme()" title="Alternar tema">
        <i class="fas fa-moon"></i>
        <span class="theme-label">Modo Escuro</span>
    </button>

    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <h1><i class="fas fa-cloud"></i> Cloudi<span class="brand-accent">Tag</span></h1>
                <p>Sistema de Gestão de Perfil Empresarial</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Seu email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Sua senha" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>
            
            <div class="login-footer">
                <p>Não tem uma conta? <a href="register.php">Cadastre-se aqui</a></p>
            </div>
        </div>
    </div>
</body>
</html>