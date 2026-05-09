<?php
require_once __DIR__ . '/src/auth.php';
auth_logout();
header('Location: ' . base_url('login.php'));
exit;
