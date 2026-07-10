<?php
// server/session.php — Session helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void {
    if (empty($_SESSION['user'])) {
        header('Location: /login.php');
        exit;
    }
}

function current_user(): array {
    return $_SESSION['user'] ?? [];
}
