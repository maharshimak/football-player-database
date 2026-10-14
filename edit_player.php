<?php
require __DIR__ . '/bootstrap.php'; require_auth(); $id = valid_id($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id'] ?? '') : ($_GET['id'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') { require_csrf(); $fields = valid_player($_POST); $db = connection(); if (query_player($db, $id) === null) { http_response_code(404); exit('Player not found.'); } save_player($db, $fields, $id); header('Location: view_players.php', true, 303); exit; }
$db = connection(); $player = query_player($db, $id); if ($player === null) { http_response_code(404); exit('Player not found.'); } page_top('Edit player'); player_form($player); page_bottom();
