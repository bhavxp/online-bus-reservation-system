<?php

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Return CSRF token
function csrf_token()
{
    return $_SESSION['csrf_token'];
}


// Validate CSRF token
function verify_csrf_token($token)
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}


// Reject invalid CSRF request
function require_valid_csrf()
{
    if (
        !verify_csrf_token(
            $_POST['csrf_token'] ?? ''
        )
    ) {
        http_response_code(403);

        exit('Invalid CSRF token.');
    }
}