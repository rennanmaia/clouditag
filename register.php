<?php
session_start();
require_once 'includes/functions.php';

$error_message = '';
$success_message = '';

// Se já está logado, redireciona
if (isLoggedIn()) {
    redirect('dashboard.php');
}

if ($_POST) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validações
    if (empty($name) || empty($email) || empty($password)) {
        $error_message = 'Todos os campos são obrigatórios.';
    } elseif (!isValidEmail($email)) {
        $error_message = 'Email inválido.';
    } elseif (strlen($password) < 6) {
        $error_message = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'As senhas não conferem.';
    } else {
        // Verificar se email já existe
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetchColumn() > 0) {
            $error_message = 'Este email já está em uso.';
        } else {
            // Criar usuário
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password, user_type, created_at) VALUES (?, ?, ?, 'user', ?)");
            
            if ($stmt->execute([$name, $email, $hashed_password, date('Y-m-d H:i:s')])) {
                $success_message = 'Conta criada com sucesso! Você pode fazer login agora.';
            } else {
                $error_message = 'Erro ao crear conta. Tente novamente.';
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
    <title>Cadastro - CloudiTag</title>
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
                <p>Crie sua conta e comece a gerenciar seus perfis</p>
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
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="name">Nome Completo</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="name" name="name" placeholder="Seu nome completo" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Seu email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Sua senha (mínimo 6 caracteres)" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Senha</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirme sua senha" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Criar Conta
                </button>
            </form>
            
            <div class="login-footer">
                <p>Já tem uma conta? <a href="login.php">Faça login aqui</a></p>
            </div>
        </div>
    </div>
</body>
</html>