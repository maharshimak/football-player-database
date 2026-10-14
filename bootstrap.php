<?php
declare(strict_types=1);
require_once __DIR__ . '/lib.php';
ini_set('display_errors', '0'); ini_set('session.use_strict_mode', '1');
$host = (string)($_SERVER['HTTP_HOST'] ?? '');
$allowed = array_filter(array_map('trim', explode(',', (string)(getenv('APP_ALLOWED_HOSTS') ?: '127.0.0.1,localhost'))));
$hostname = preg_replace('/:\d+$/', '', $host);
if (!in_array($hostname, $allowed, true)) { http_response_code(400); exit('Invalid host.'); }
header('Cache-Control: no-store'); header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict']);
session_start();
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
try { $admin = configured_admin(); } catch (Throwable $e) { http_response_code(503); exit('Configure the administrator before use.'); }
function require_csrf(): void { if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid($_POST['csrf'] ?? null, $_SESSION['csrf'] ?? null)) { http_response_code(403); exit('Invalid request token.'); } }
function require_auth(): void {
    global $admin;
    if (empty($_SESSION['authenticated']) || !hash_equals(hash('sha256', $admin[1]), (string)($_SESSION['auth_version'] ?? '')) || time() - (int)($_SESSION['last_seen'] ?? 0) > 1800) { unset($_SESSION['authenticated']); header('Location: login.php', true, 303); exit; }
    $_SESSION['last_seen'] = time();
}
function page_top(string $title): void { echo '<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.esc($title).'</title><link rel="stylesheet" href="styles.css"></head><body><nav><a href="index.php">Football Manager</a> | <a href="view_players.php">View players</a> | <a href="export_players_csv.php">Export CSV</a></nav><main><h1>'.esc($title).'</h1>'; }
function page_bottom(): void { echo '</main></body></html>'; }
function player_form(?array $player = null): void {
    echo '<form method="POST" action="'.($player ? 'edit_player.php' : 'add_player.php').'"><input type="hidden" name="csrf" value="'.esc($_SESSION['csrf']).'">';
    if ($player) echo '<input type="hidden" name="id" value="'.esc($player['id']).'">';
    echo '<label>Name <input name="player_name" maxlength="120" required value="'.esc($player['name'] ?? '').'"></label><label>Email <input type="email" name="player_email" maxlength="254" required value="'.esc($player['email'] ?? '').'"></label><label>Team <select name="player_team" required>';
    foreach (teams() as $team) echo '<option value="'.esc($team).'"'.(($player['team'] ?? '') === $team ? ' selected' : '').'>'.esc($team).'</option>';
    echo '</select></label><button type="submit">Save player</button></form>';
}
set_exception_handler(function(Throwable $e): void { http_response_code($e instanceof InvalidArgumentException ? 422 : 500); echo $e instanceof InvalidArgumentException ? esc($e->getMessage()) : 'Request failed. Check local configuration.'; });
