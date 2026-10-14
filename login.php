<?php
require __DIR__ . '/bootstrap.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $username = (string)($_POST['username'] ?? ''); $password = (string)($_POST['password'] ?? '');
    if (strlen($username) > 128 || strlen($password) > 1024) { http_response_code(422); exit('Invalid credentials.'); }
    $attempt = $_SESSION['attempt_window'] ?? ['start' => time(), 'count' => 0];
    if (time() - $attempt['start'] > 300) $attempt = ['start' => time(), 'count' => 0];
    if ($attempt['count'] >= 5) { http_response_code(429); exit('Login temporarily limited.'); }
    $attempt['count']++; $_SESSION['attempt_window'] = $attempt;
    if (authenticate($username, $password, $admin)) {
        session_regenerate_id(true); $_SESSION = ['authenticated' => true, 'auth_version' => hash('sha256', $admin[1]), 'last_seen' => time(), 'csrf' => bin2hex(random_bytes(32))]; header('Location: index.php', true, 303); exit;
    }
    http_response_code(401); $error = 'Invalid credentials.';
}
page_top('Sign in'); echo '<p>'.esc($error).'</p><form method="POST"><input type="hidden" name="csrf" value="'.esc($_SESSION['csrf']).'"><label>Username <input name="username" autocomplete="username" required></label><label>Password <input type="password" name="password" autocomplete="current-password" required></label><button>Sign in</button></form>'; page_bottom();
