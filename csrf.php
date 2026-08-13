<?php
/**
 * CSRF protection helper.
 * Include this file (after session_start()) on every page that renders
 * or processes a POST form.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns the current CSRF token, generating one if it doesn't exist yet.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Renders a hidden input field carrying the CSRF token.
 */
function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Verifies a submitted CSRF token against the one stored in the session.
 * Returns true if valid, false otherwise.
 */
function csrf_verify(?string $submitted): bool
{
    if (empty($_SESSION['csrf_token']) || empty($submitted)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submitted);
}