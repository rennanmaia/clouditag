<?php
session_start();

// Configurações do banco de dados
$db_config = array(
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'clouditag'
);

$install_complete = false;
$error_message = '';
$success_message = '';

if ($_POST) {
    try {
        // Conectar ao MySQL (sem banco específico)
        $pdo = new PDO("mysql:host={$db_config['host']}", $db_config['username'], $db_config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Criar banco de dados
        $pdo->exec("CREATE DATABASE IF NOT EXISTS {$db_config['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE {$db_config['database']}");
        
        // Criar tabelas uma por vez para melhor compatibilidade
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                photo VARCHAR(255) DEFAULT NULL,
                user_type ENUM('admin_geral', 'admin_perfis', 'user') DEFAULT 'user',
                created_at DATETIME DEFAULT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS profiles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                profile_type ENUM('empresa', 'profissional') NOT NULL,
                layout_template VARCHAR(50) NOT NULL DEFAULT 'classic',
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                description TEXT,
                logo VARCHAR(255) DEFAULT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS field_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                label VARCHAR(255) NOT NULL,
                icon VARCHAR(255) DEFAULT NULL,
                input_type ENUM('text', 'email', 'tel', 'url', 'textarea', 'password') DEFAULT 'text',
                validation_pattern VARCHAR(255) DEFAULT NULL,
                placeholder VARCHAR(255) DEFAULT NULL,
                is_system TINYINT(1) DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                order_index INT DEFAULT 0,
                created_at DATETIME DEFAULT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS profile_fields (
                id INT AUTO_INCREMENT PRIMARY KEY,
                profile_id INT NOT NULL,
                field_type_id INT NOT NULL,
                value TEXT,
                is_visible TINYINT(1) DEFAULT 1,
                is_clickable TINYINT(1) DEFAULT 1,
                order_index INT DEFAULT 0,
                created_at DATETIME DEFAULT NULL,
                FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
                FOREIGN KEY (field_type_id) REFERENCES field_types(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS profile_links (
                id INT AUTO_INCREMENT PRIMARY KEY,
                profile_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                url TEXT NOT NULL,
                icon VARCHAR(255) DEFAULT NULL,
                order_index INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT NULL,
                FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
            )"
        ];
        
        // Executar cada tabela separadamente
        foreach ($tables as $sql) {
            $pdo->exec($sql);
        }
        
        // Criar usuário admin geral padrão
        $admin_email = $_POST['admin_email'];
        $admin_password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
        $admin_name = $_POST['admin_name'];
        $current_time = date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, user_type, created_at) VALUES (?, ?, ?, 'admin_geral', ?)");
        $stmt->execute([$admin_name, $admin_email, $admin_password, $current_time]);
        
        // Inserir tipos de campo padrão do sistema
        $default_field_types = [
            ['phone', 'Telefone', 'fas fa-phone', 'tel', '^[\d\s\(\)\-\+]+$', '(11) 99999-9999', 1, 1],
            ['whatsapp', 'WhatsApp', 'fab fa-whatsapp', 'tel', '^[\d\s\(\)\-\+]+$', '(11) 99999-9999', 1, 2],
            ['email', 'Email', 'fas fa-envelope', 'email', '^[^\s@]+@[^\s@]+\.[^\s@]+$', 'contato@exemplo.com', 1, 3],
            ['website', 'Site', 'fas fa-globe', 'url', '^https?:\/\/.+', 'https://www.exemplo.com', 1, 4],
            ['address', 'Endereço', 'fas fa-map-marker-alt', 'textarea', null, 'Rua Exemplo, 123 - Bairro - Cidade/UF', 1, 5],
            ['pix', 'PIX', 'fas fa-qrcode', 'text', null, 'Chave PIX', 1, 6],
            ['wifi_ssid', 'WiFi (SSID)', 'fas fa-wifi', 'text', null, 'Nome da rede WiFi', 1, 7],
            ['wifi_password', 'WiFi (Senha)', 'fas fa-key', 'password', null, 'Senha da rede WiFi', 1, 8],
            ['google_review', 'Avaliação Google', 'fab fa-google', 'url', '^https?:\/\/.+', 'Link para avaliação', 1, 9],
            ['facebook', 'Facebook', 'fab fa-facebook', 'url', '^https?:\/\/.+', 'https://facebook.com/perfil', 0, 10],
            ['instagram', 'Instagram', 'fab fa-instagram', 'url', '^https?:\/\/.+', 'https://instagram.com/perfil', 0, 11],
            ['linkedin', 'LinkedIn', 'fab fa-linkedin', 'url', '^https?:\/\/.+', 'https://linkedin.com/in/perfil', 0, 12],
            ['youtube', 'YouTube', 'fab fa-youtube', 'url', '^https?:\/\/.+', 'https://youtube.com/canal', 0, 13],
            ['twitter', 'Twitter/X', 'fab fa-twitter', 'url', '^https?:\/\/.+', 'https://twitter.com/perfil', 0, 14],
            ['tiktok', 'TikTok', 'fab fa-tiktok', 'url', '^https?:\/\/.+', 'https://tiktok.com/@perfil', 0, 15]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO field_types (name, label, icon, input_type, validation_pattern, placeholder, is_system, order_index, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($default_field_types as $field_type) {
            $stmt->execute(array_merge($field_type, [$current_time]));
        }
        
        // Criar arquivo de configuração
        $config_content = "<?php
// Configurações do banco de dados
define('DB_HOST', '{$db_config['host']}');
define('DB_USERNAME', '{$db_config['username']}');
define('DB_PASSWORD', '{$db_config['password']}');
define('DB_NAME', '{$db_config['database']}');

// Configurações gerais
define('SITE_URL', 'http://localhost/clouditag');
define('UPLOAD_PATH', 'uploads/');

// Timezone
date_default_timezone_set('America/Sao_Paulo');
?>";
        
        file_put_contents('includes/config.php', $config_content);
        
        $install_complete = true;
        $success_message = 'Sistema instalado com sucesso! Você pode fazer login com as credenciais do administrador criadas.';
        
    } catch (Exception $e) {
        $error_message = 'Erro durante a instalação: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - CloudiTag</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .install-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 2.5em;
            font-weight: bold;
        }
        
        .logo p {
            color: #666;
            margin-top: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .success-container {
            text-align: center;
        }
        
        .success-container i {
            font-size: 4em;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .login-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s;
        }
        
        .login-link:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <?php if ($install_complete): ?>
            <div class="success-container">
                <i class="fas fa-check-circle"></i>
                <h2>Instalação Concluída!</h2>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
                <a href="login.php" class="login-link">
                    <i class="fas fa-sign-in-alt"></i> Fazer Login
                </a>
            </div>
        <?php else: ?>
            <div class="logo">
                <h1><i class="fas fa-cloud"></i> CloudiTag</h1>
                <p>Sistema de Gestão de Perfil Empresarial</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <h3 style="margin-bottom: 20px; color: #333;">Configuração do Administrador</h3>
                
                <div class="form-group">
                    <label for="admin_name">Nome do Administrador</label>
                    <input type="text" id="admin_name" name="admin_name" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">Email do Administrador</label>
                    <input type="email" id="admin_email" name="admin_email" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_password">Senha do Administrador</label>
                    <input type="password" id="admin_password" name="admin_password" required>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-download"></i> Instalar Sistema
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>