
<?php

// ============================================================
// SECURE ADMIN SESSION CONFIGURATION
// Online Bus Reservation System
// Deployment-Ready Version
// ============================================================


// ============================================================
// START SECURE SESSION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


// ============================================================
// SESSION SECURITY HEADERS
// ============================================================

header(
    'X-Content-Type-Options: nosniff'
);

header(
    'X-Frame-Options: SAMEORIGIN'
);

header(
    'Referrer-Policy: strict-origin-when-cross-origin'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


// ============================================================
// ADMIN AUTHENTICATION CHECK
// ============================================================

if (
    !isset($_SESSION['admin_id'])
    ||
    (int) $_SESSION['admin_id'] <= 0
) {

    header(
        'Location: login.php'
    );

    exit;
}


// ============================================================
// VALIDATE REQUIRED ADMIN SESSION DATA
// ============================================================

if (
    !isset($_SESSION['admin_username'])
    ||
    !isset($_SESSION['admin_name'])
) {

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header(
        'Location: login.php'
    );

    exit;
}


// ============================================================
// SESSION ID REGENERATION
// ============================================================

// Regenerate the session ID periodically
// to reduce session fixation risk.

if (
    !isset($_SESSION['session_regenerated_at'])
    ||
    (time() - (int) $_SESSION['session_regenerated_at']) > 900
) {

    session_regenerate_id(true);

    $_SESSION['session_regenerated_at'] =
        time();
}


// ============================================================
// FINAL ADMIN ID NORMALIZATION
// ============================================================

$_SESSION['admin_id'] =
    (int) $_SESSION['admin_id'];

?>
