<?php
require __DIR__ . '/bootstrap.php'; require_auth(); require_csrf(); $_SESSION = []; session_destroy(); setcookie(session_name(), '', ['expires' => time()-3600, 'path' => '/', 'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'httponly' => true, 'samesite' => 'Strict']); header('Location: login.php', true, 303);
