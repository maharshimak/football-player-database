<?php
require __DIR__ . '/bootstrap.php'; require_auth(); $db = connection(); $total = $db->query('SELECT COUNT(*) AS total FROM players')->fetch_assoc()['total'];
page_top('Football Manager'); echo '<p>Total players: '.esc($total).'</p>'; player_form(); echo '<form method="POST" action="logout.php"><input type="hidden" name="csrf" value="'.esc($_SESSION['csrf']).'"><button>Sign out</button></form>'; page_bottom();
