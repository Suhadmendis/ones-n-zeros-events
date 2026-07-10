<?php
require_once 'server/session.php';
require_once 'server/config.php';
require_once 'server/logger.php';

log_activity(['action_type' => 'logout', 'description' => 'User logged out']);

$_SESSION = [];
session_destroy();
header('Location: /login.php');
exit;
