<?php
require __DIR__ . '/bootstrap.php'; require_auth(); $db = connection(); $result = $db->query('SELECT id,name,email,team FROM players ORDER BY id'); page_top('Players'); echo '<table><thead><tr><th>Name</th><th>Email</th><th>Team</th><th>Actions</th></tr></thead><tbody>';
while ($row = $result->fetch_assoc()) { echo '<tr><td>'.esc($row['name']).'</td><td>'.esc($row['email']).'</td><td>'.esc($row['team']).'</td><td><a href="edit_player.php?id='.esc($row['id']).'">Edit</a><form method="POST" action="delete_player.php"><input type="hidden" name="id" value="'.esc($row['id']).'"><input type="hidden" name="csrf" value="'.esc($_SESSION['csrf']).'"><button>Delete</button></form></td></tr>'; }
echo '</tbody></table>'; page_bottom();
