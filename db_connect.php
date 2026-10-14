<?php
// Compatibility include; authentication must happen before opening any database.
require_once __DIR__ . '/bootstrap.php'; require_auth(); $conn = connection();
