<?php
require __DIR__ . '/bootstrap.php'; require_auth(); require_csrf(); $id = valid_id($_POST['id'] ?? ''); $db = connection(); delete_player($db, $id); header('Location: view_players.php', true, 303);
