<?php
// config/auth.php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Only start session if not already started and no output has been sent
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

class Auth {
    private $db;
    
    public function __construct($database) {
        if ($database instanceof Database) {
            $this->db = $database->getConnection();
        } else {
            $this->db = $database;
        }
    }

    // Dynamically get the base URL for the project
    public static function baseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $projectFolder = explode('/', trim($scriptName, '/'));
        if (count($projectFolder) > 1) {
            $base = $protocol . $host . '/' . $projectFolder[0];
        } else {
            $base = $protocol . $host;
        }
        return $base;
    }
    
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                return true;
            }
        }
        return false;
    }
    
    public function register($name, $email, $password, $role = 'user') {
        // Check if email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return false; // Email already exists
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
        
        return $stmt->execute();
    }
    
    public function logout() {
        session_destroy();
        unset($_SESSION);
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role']
            ];
        }
        return null;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            if (!headers_sent()) {
                header('Location: ' . self::baseUrl() . '/auth/login.php');
                exit();
            }
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            if (!headers_sent()) {
                header('Location: ' . self::baseUrl() . '/user/index.php');
                exit();
            }
        }
    }
}
?>