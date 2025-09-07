<?php

require('vendor/autoload.php');
include('db.php');

use SecureAuth\Security\Csrf;
use SecureAuth\validation\Validator;
use SecureAuth\validation\ValidatorMessages;
use SecureAuth\Repository\BaseRepository;
use SecureAuth\Auth\Auth;
use SecureAuth\Auth\SessionHelper;
use SecureAuth\Security\RemembermeToken;
use SecureAuth\Security\RateLimiter;

// -------------------------------
// Always start session at top
// -------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// -------------------------------
// Generate CSRF token for form
// -------------------------------
$token = Csrf::getToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $validator       = new Validator();
    $validatormsg    = new ValidatorMessages($validator);
    $baserepo        = new BaseRepository($connection);
    $rememberManager = new RememberMeToken();
    $rateLimiter     = new RateLimiter($baserepo);

    $csrf_token = trim($_POST['csrf_token'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $ip         = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $remember_me = trim($_POST['remember_me'] ?? '');


    // -------------------------------
    // 1. Validate request
    // -------------------------------
    $data = [
        'email'      => $email,
        'password'   => $password,
        'csrf_token' => $csrf_token,
    ];

    $rules = [
        'email'      => 'required|email',
        'password'   => 'required|min:8|strong_password',
        'csrf_token' => 'required|verify_csrftoken'
    ];

    if (!$validator->validate($data, $rules, $validatormsg->validationMessage())) {
        SessionHelper::flash('error', $validator->errors()[0]);
        header('location:index.php');
        exit();
    }

    // -------------------------------
    // 2. Check rate limiting
    // -------------------------------


    // If no record found, initialize an empty array
    if ($rateLimiter->tooManyAttempts($ip, $email)) {
        $retryAfter = $rateLimiter->getRetryAfterSeconds($ip, $email);
        header('Retry-After: ' . $retryAfter);
        // http_response_code(429); // Correct status code for rate limiting
        SessionHelper::flash('error', 'Too many login attempts. Please wait ' . $retryAfter . ' seconds.');
        header('location:index.php');
        exit();
    }




    // -------------------------------
    // 3. Authenticate user
    // -------------------------------
    $result = $baserepo->query(
        'SELECT * FROM users WHERE email = ?',
        's',
        $email
    )->fetchOne();

    if ($result && Auth::AuthenticateUser($result, $email, $password)) {
        // ✅ Successful login
        // Clear login attempts
        $baserepo->query('DELETE FROM login_attempts WHERE email = ?', 's', $email);

        // Regenerate CSRF token to prevent fixation
        Csrf::generateToken();

        // Set session
        SessionHelper::SetUserSession(
            $result['fullname'],
            $result['email'],
            $result['role'],
            $result['id']
        );


        // check if rememberme checkbox is checked then generate rememberme token
        if (isset($remember_me) && !empty($remember_me)) {
            // echo $remember_me;
            $rememberManager->generateRemembermeToken()->setcookie();
        }



        header('Location: dashboard.php');
        exit();
    } else {
        // ❌ Failed login → log attempt
        $baserepo->query(
            'INSERT INTO login_attempts(ip, email) VALUES(?, ?)',
            'ss',
            $ip,
            $email
        );
        SessionHelper::flash('error', 'Invalid email or password');
        header('location:index.php');
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>

<body>

    <?php if ($msg = SessionHelper::getFlash('error')): ?>
        <?= htmlspecialchars($msg); ?>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <input type="text" name="email" placeholder="email">
        <br><br>
        <input type="password" name="password" placeholder="password">
        <br><br>
        <label>
            <input type="checkbox" name="remember_me"> Remember Me
        </label>
        <br><br>
        <button type="submit" name="submit">Login</button>
    </form>

</body>

</html>