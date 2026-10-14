<?php
declare(strict_types=1);
function esc($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csv_safe($value): string { $text = (string)$value; return preg_match('/^(?:[\t\r\n]|\s*[=+\-@])/', $text) ? "'" . $text : $text; }
function valid_player(array $input): array {
    $name = trim((string)($input['player_name'] ?? ''));
    $email = trim((string)($input['player_email'] ?? ''));
    $team = trim((string)($input['player_team'] ?? ''));
    if ($name === '' || strlen($name) > 120 || preg_match('/[\x00-\x1F\x7F]/', $name)) throw new InvalidArgumentException('Invalid player name.');
    if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid email.');
    if (!in_array($team, teams(), true)) throw new InvalidArgumentException('Invalid team.');
    return [$name, $email, $team];
}
function teams(): array { return ['FC Barcelona', 'Real Madrid', 'Manchester City', 'PSG', 'Bayern Munich']; }
function valid_id($input): int { $id = filter_var($input, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]); if ($id === false) throw new InvalidArgumentException('Invalid player id.'); return $id; }
function csrf_valid($provided, $expected): bool { return is_string($provided) && is_string($expected) && strlen($expected) === 64 && hash_equals($expected, $provided); }
function configured_admin(): array {
    $user = (string)getenv('APP_ADMIN_USERNAME'); $hash = (string)getenv('APP_ADMIN_PASSWORD_HASH');
    if ($user === '' || $hash === '' || password_get_info($hash)['algoName'] === 'unknown') throw new RuntimeException('Administrator is not configured.');
    return [$user, $hash];
}
function authenticate(string $user, string $password, array $config): bool { return hash_equals($config[0], $user) && password_verify($password, $config[1]); }
function connection(): mysqli {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $host = (string)(getenv('DB_HOST') ?: '127.0.0.1'); $port = (int)(getenv('DB_PORT') ?: 3306);
    $user = (string)getenv('DB_USER'); $pass = (string)getenv('DB_PASSWORD'); $name = (string)getenv('DB_NAME');
    if ($user === '' || $pass === '' || $name === '') throw new RuntimeException('Database is not configured.');
    $db = new mysqli($host, $user, $pass, $name, $port); $db->set_charset('utf8mb4'); return $db;
}
function query_player(mysqli $db, int $id): ?array { $stmt = $db->prepare('SELECT id,name,email,team FROM players WHERE id=?'); $stmt->bind_param('i', $id); $stmt->execute(); $result = $stmt->get_result()->fetch_assoc(); $stmt->close(); return $result; }
function save_player(mysqli $db, array $fields, ?int $id = null): void {
    [$name, $email, $team] = $fields;
    if ($id === null) { $stmt = $db->prepare('INSERT INTO players (name,email,team) VALUES (?,?,?)'); $stmt->bind_param('sss', $name, $email, $team); }
    else { $stmt = $db->prepare('UPDATE players SET name=?,email=?,team=? WHERE id=?'); $stmt->bind_param('sssi', $name, $email, $team, $id); }
    $stmt->execute(); $stmt->close();
}
function delete_player(mysqli $db, int $id): void { $stmt = $db->prepare('DELETE FROM players WHERE id=?'); $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
