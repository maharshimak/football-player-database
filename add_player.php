<?php
require __DIR__ . '/bootstrap.php'; require_auth(); require_csrf(); $fields = valid_player($_POST); $db = connection(); save_player($db, $fields); header('Location: view_players.php', true, 303);
