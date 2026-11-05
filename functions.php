<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = $_POST['csrf_token'] ?? '';
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
            http_response_code(403);
            exit('Invalid form token. Go back, refresh the page, and try again.');
        }
    }
}
function flash(string $message): void {
    $_SESSION['flash_message'] = $message;
}
function show_flash(): void {
    if (!empty($_SESSION['flash_message'])) {
        echo '<div class="alert alert-info">'.h($_SESSION['flash_message']).'</div>';
        unset($_SESSION['flash_message']);
    }
}
