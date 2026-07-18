<?php
class Auth {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(['lifetime' => SESSION_LIFETIME, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
            session_start();
        }
    }

    public function login(string $email, string $password): array {
        $user = $this->db->fetchOne("SELECT * FROM lms_users WHERE (email=? OR phone=?) AND status='active'", [$email, $email]);
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials. Please try again.'];
        }
        $_SESSION['lms_user_id']   = $user['id'];
        $_SESSION['lms_user_name'] = $user['name'];
        $_SESSION['lms_user_role'] = $user['role'];
        $_SESSION['lms_user_photo']= $user['photo'];
        $this->db->query("UPDATE lms_users SET last_login=NOW() WHERE id=?", [$user['id']]);
        return ['success' => true];
    }

    public function register(string $name, string $email, string $phone, string $password): array {
        if ($this->db->fetchOne("SELECT id FROM lms_users WHERE email=?", [$email])) {
            return ['success' => false, 'message' => 'Email already registered.'];
        }
        $id = $this->db->insert('lms_users', [
            'name'       => $name,
            'email'      => $email,
            'phone'      => $phone,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'role'       => 'student',
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'user_id' => $id];
    }

    public function logout(): void {
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }

    public function isLoggedIn(): bool { return !empty($_SESSION['lms_user_id']); }
    public function requireLogin(): void { if (!$this->isLoggedIn()) { header('Location: ' . SITE_URL . '/login.php'); exit; } }
    public function getUserId(): int    { return (int)($_SESSION['lms_user_id'] ?? 0); }
    public function getRole(): string   { return $_SESSION['lms_user_role'] ?? ''; }
    public function getName(): string   { return $_SESSION['lms_user_name'] ?? ''; }
}
