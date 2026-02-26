<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USERNAME,
                DB_PASSWORD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]
            );
        } catch (PDOException $e) {
            die('Erro na conexão com o banco de dados: ' . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Função para obter conexão com o banco
function getDB() {
    return Database::getInstance()->getConnection();
}

// Função para verificar se usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Função para obter dados do usuário logado
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Função para verificar se é admin geral
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['user_type'] === 'admin_geral';
}

// Função para verificar se é admin de perfis
function isProfileAdmin() {
    $user = getCurrentUser();
    return $user && in_array($user['user_type'], ['admin_geral', 'admin_perfis']);
}

// Função para gerar slug único
function generateSlug($text, $table = 'profiles', $field = 'slug') {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
    $slug = trim($slug, '-');
    
    $db = getDB();
    $original_slug = $slug;
    $counter = 1;
    
    while (true) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$field} = ?");
        $stmt->execute([$slug]);
        
        if ($stmt->fetchColumn() == 0) {
            break;
        }
        
        $slug = $original_slug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

// Função para upload de arquivo
function uploadFile($file, $directory, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Nenhum arquivo enviado'];
    }
    
    $upload_dir = $directory . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Tipo de arquivo não permitido'];
    }
    
    $filename = uniqid() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'message' => 'Erro ao fazer upload do arquivo'];
    }
}

// Função para formatar telefone
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) == 11) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7);
    } elseif (strlen($phone) == 10) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6);
    }
    
    return $phone;
}

// Normaliza URLs, adicionando https:// se o usuário não informar protocolo
function normalizeUrlValue($url) {
    $url = trim($url);
    if ($url === '') {
        return $url;
    }
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }
    return 'https://' . $url;
}

// Função para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Função para sanitizar entrada
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Função para redirecionar
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo "<script>window.location.href='$url';</script>";
        exit();
    }
}

// Função para exibir alertas
function showAlert($message, $type = 'info') {
    $class = 'alert-' . $type;
    return "<div class='alert $class'>$message</div>";
}

// Função para verificar se perfil pertence ao usuário
function userOwnsProfile($user_id, $profile_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM profiles WHERE id = ? AND user_id = ?");
    $stmt->execute([$profile_id, $user_id]);
    return $stmt->fetchColumn() > 0;
}

// Função para obter perfil por slug
function getProfileBySlug($slug) {
    $db = getDB();
    $stmt = $db->prepare("SELECT p.*, u.name as owner_name FROM profiles p JOIN users u ON p.user_id = u.id WHERE p.slug = ? AND p.is_active = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

// Função para obter links do perfil
function getProfileLinks($profile_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM profile_links WHERE profile_id = ? AND is_active = 1 ORDER BY order_index ASC");
    $stmt->execute([$profile_id]);
    return $stmt->fetchAll();
}

// Função para obter campos do perfil (apenas visíveis - para exibição pública)
function getProfileFields($profile_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT pf.*, 
               ft.name as field_name, 
               ft.label, 
               ft.icon, 
               ft.input_type, 
               ft.validation_pattern, 
               COALESCE(ft.placeholder, '') as placeholder
        FROM profile_fields pf 
        JOIN field_types ft ON pf.field_type_id = ft.id 
        WHERE pf.profile_id = ? AND pf.is_visible = 1 AND ft.is_active = 1 
        ORDER BY pf.order_index ASC, pf.id ASC
    ");
    $stmt->execute([$profile_id]);
    return $stmt->fetchAll();
}

// Função para obter todos os campos do perfil (para administração)
function getAllProfileFields($profile_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT pf.*, 
               ft.name as field_name, 
               ft.label, 
               ft.icon, 
               ft.input_type, 
               ft.validation_pattern, 
               COALESCE(ft.placeholder, '') as placeholder
        FROM profile_fields pf 
        JOIN field_types ft ON pf.field_type_id = ft.id 
        WHERE pf.profile_id = ? AND ft.is_active = 1 
        ORDER BY pf.order_index ASC, pf.id ASC
    ");
    $stmt->execute([$profile_id]);
    return $stmt->fetchAll();
}

// Função para obter todos os tipos de campo disponíveis
function getFieldTypes($include_inactive = false) {
    $db = getDB();
    $where = $include_inactive ? "" : "WHERE is_active = 1";
    $stmt = $db->prepare("SELECT * FROM field_types $where ORDER BY order_index ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Função para obter tipos de campo do sistema
function getSystemFieldTypes() {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM field_types WHERE is_system = 1 AND is_active = 1 ORDER BY order_index ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Função para obter campo específico de um perfil
function getProfileField($profile_id, $field_name) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT pf.*, ft.name as field_name, ft.label, ft.icon, ft.input_type 
        FROM profile_fields pf 
        JOIN field_types ft ON pf.field_type_id = ft.id 
        WHERE pf.profile_id = ? AND ft.name = ? AND pf.is_visible = 1
    ");
    $stmt->execute([$profile_id, $field_name]);
    return $stmt->fetch();
}

// Função para atualizar ou criar campo do perfil
function updateProfileField($profile_id, $field_type_id, $value, $is_visible = 1, $is_clickable = 1) {
    $db = getDB();
    
    // Verificar se já existe
    $stmt = $db->prepare("SELECT id FROM profile_fields WHERE profile_id = ? AND field_type_id = ?");
    $stmt->execute([$profile_id, $field_type_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Atualizar
        $stmt = $db->prepare("UPDATE profile_fields SET value = ?, is_visible = ?, is_clickable = ? WHERE id = ?");
        return $stmt->execute([$value, $is_visible, $is_clickable, $existing['id']]);
    } else {
        // Criar novo
        $stmt = $db->prepare("SELECT COALESCE(MAX(order_index), 0) + 1 as next_order FROM profile_fields WHERE profile_id = ?");
        $stmt->execute([$profile_id]);
        $next_order = $stmt->fetchColumn();
        
        $stmt = $db->prepare("INSERT INTO profile_fields (profile_id, field_type_id, value, is_visible, is_clickable, order_index, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$profile_id, $field_type_id, $value, $is_visible, $is_clickable, $next_order, date('Y-m-d H:i:s')]);
    }
}

// Função para remover campo do perfil
function removeProfileField($profile_id, $field_type_id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM profile_fields WHERE profile_id = ? AND field_type_id = ?");
    return $stmt->execute([$profile_id, $field_type_id]);
}

// Função para obter campos personalizados do perfil (mantida para compatibilidade)
function getProfileCustomFields($profile_id) {
    return getProfileFields($profile_id);
}

// Função para validar login
function validateLogin($email, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];
        return true;
    }
    
    return false;
}

// Função para logout
function logout() {
    session_destroy();
    redirect('login.php');
}
?>