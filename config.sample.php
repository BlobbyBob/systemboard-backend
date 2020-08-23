<?php

define('DB_DSN', 'mysql:host=localhost;dbname=systemboard;charset=utf8');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_URL', 'https://localhost');

define('SEGMENTS_PER_WALL', 3);
define('ARGON_SETTINGS', ['memorycost' => 1 << 13]);
define('SESSION_DURATION', 43200);
