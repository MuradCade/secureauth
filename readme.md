# SecureAuth PHP Package

SecureAuth is a lightweight PHP package designed to simplify common web application security and authentication tasks, including validation, CSRF protection, authentication, authorization, email sending, and “remember me” functionality.  

---

## Table of Contents

1. [Installation](#installation)  
2. [Validation](#validation)  
   - [Usage](#validation-usage)  
   - [Validation Rules](#validation-rules)  
3. [BaseRepository](#baserepository)  
   - [Usage](#baserepository-usage)  
4. [Authentication](#authentication)  
   - [Usage](#authentication-usage)  
5. [Authorization](#authorization)  
   - [Usage](#authorization-usage)  
6. [Email Jobs](#email-jobs)  
   - [Usage](#email-jobs-usage)  
7. [RememberMe Token](#rememberme-token)  
   - [Usage](#rememberme-token-usage)  
8. [Rate Limiter](#rate-limiter)
    - [Database Schema](#database-schema)
    - [How It Works](#how-it-works)
    - [Example Usage](#example-usage)
9. [Environment Configuration](#environment-configuration) 
10. [Example Workflow](#example-workflow)
    - [Login Logic](#login-logic)
11. [SecureAuth PHP Package License](#secureauth-php-package-license) 

---

## Installation

Install via Composer:

```bash
composer require muradcade/secureauth
```

## Validation
SecureAuth wraps Laravel’s validation components for simple and robust validation.
### Validation Usage
```php
use SecureAuth\Security\Csrf;
use SecureAuth\Validation\Validator;
use SecureAuth\Validation\ValidatorMessages;

// Create Validator instance
$validator = new Validator();
// custom validation message
 $customErrorMessage = new ValidatorMessages($validator);
 // if there is no csrf token generate one   = Csrf::generateToken();
$token = Csrf::getToken(); // get the generated session


// Data to validate
$data = [
    'email' => 'test@example.com',
    'password' => 'StrongPass123!',
    'csrf_token' => $token
];

// Validation rules
$rules = [
    'email' => 'required|email',
    'password' => 'required|min:8|strong_password',
    'csrf_token' => 'required|verify_csrftoken'
];

// Validate and handle errors
if (!$validator->validate($data, $rules, $customErrorMessage->validationMessage())) {
    var_dump($validator->errors()[0]);
}

```
### Validation Rules
| Rule             | Description                                             |
| ---------------- | ------------------------------------------------------- |
| required         | Field must not be empty                                 |
| email            | Must be a valid email format                            |
| min:8            | Minimum 8 characters                                    |
| strong\_password | Must include uppercase, lowercase, numbers, and symbols |
| verify\_csrftoken| Validates that the CSRF token is valid                  |

## BaseRepository
Provides database interaction using prepared statements with MySQLi.
###  BaseRepository Usage
```php
use SecureAuth\Repository\BaseRepository;

// Pass a MySQLi connection
$repository = new BaseRepository($connection);

// Insert a new user
$repository->query(
    'INSERT INTO users(fullname,email,password) VALUES (?, ?, ?)',
    'sss',
    $data['fullname'],
    $data['email'],
    password_hash($data['password'], PASSWORD_DEFAULT)
);
```
- Supports SELECT, INSERT, UPDATE, DELETE operations.
## Authentication
Authenticate users with database data and manage sessions.
### Authentication Usage
```php
use SecureAuth\Auth\Auth;
use SecureAuth\Auth\SessionHelper;
use SecureAuth\Repository\BaseRepository;

// Fetch user record
$result = $repository
    ->query('SELECT * FROM users WHERE email = ?', 's', $data['email'])
    ->fetchOne();

// Authenticate user
if (Auth::authenticateUser($result, $data['email'], $data['password'])) {
    SessionHelper::setUserSession($result['fullname'], $result['email'], $result['userrole'], $result['id']);
    header('Location: dashboard.php');
    exit();
}
```
## Authorization
Check user login status and role-based access.
### Authorization Usage
```php
use SecureAuth\Auth\Authorization;
use SecureAuth\Auth\SessionHelper;

$auth = new Authorization();

// Redirect if user is logged in (e.g., login page)
$auth->Islogedin(SessionHelper::getSessionVariable('username'), 'dashboard.php');

// Redirect if user is not logged in
$auth->Isnotlogedin(SessionHelper::getSessionVariable('username'), 'index.php');

// Authorize specific user roles
$auth->AuthorizedUser(SessionHelper::getSessionVariable('userrole'), 'admin', 'index.php');
```

## Email Jobs
Supports sending emails with or without attachments using a worker-job system.

### Job Structure

- JobInterface – Defines rules for processing email jobs.<br>
- EmailJob – Handles sending emails.<br>
- WorkerJob – Dispatches email jobs.<br>

### Email Jobs Usage
```php
use SecureAuth\Jobs\WorkerJob;
use SecureAuth\Jobs\EmailJob;

$mailContent = [
    'to' => 'recipient@example.com',
    'subject' => 'Test Email',
    'body' => '<h1>Hello World</h1>',
    'attachment' => __DIR__ . '/files/test.txt' // optional
];

// Dispatch job (emailjobclass) , $config comes from env file and mailcontent is array above
$result = WorkerJob::run(EmailJob::class, $config, $mailContent);
```

## RememberMe Token
Manages persistent login tokens stored in cookies.
### RememberMe Token Usage
```php
use SecureAuth\Security\RememberMeToken;
use SecureAuth\Auth\Authorization;
use SecureAuth\Auth\SessionHelper;

$tokenManager = new RememberMeToken();

// Generate token and set cookie
$tokenManager->generateRememberMeToken()->setCookie();
// create instance of authorization class
$auth = new Authorization();
// Check if session missing but token exists
if ($auth->shouldRotateToken(SessionHelper::getSessionVariable('userid'), $tokenManager->getTokenContent())) {
    $tokenManager->rotateTokenContent();
    SessionHelper::setUserSession('Username', 'email@example.com', 'role', 2);
} else {
    $auth->redirectIfNotLoggedIn(SessionHelper::getSessionVariable('userid'), 'index.php', $tokenManager->getTokenContent());
}

// Get current token
$currentToken = $tokenManager->getTokenContent();
```

## Environment Configuration
```php
$config = [
    'DATABASE' => [
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'dbname' => 'secureauth'
    ],
    'MAIL' => [
        'GOOGLE_EMAIL' => 'john@example.com',
        'GOOGLE_SECRET_KEY' => '',
        'PROJECT_NAME' => 'TEST',
        'Email_Verification_Url' => 'http://localhost/secureauth/'
    ]
];
```
## Rate Limiter
The Rate Limiter is responsible for preventing brute-force login attacks by limiting the number of failed login attempts a user can make within a specified timeframe. It works by storing failed attempts in a database table and checking whether the threshold has been exceeded before processing further login requests.
###### Database Schema
Before using the Rate Limiter, create the `login_attempts` table:<br>
```sql
CREATE TABLE login_attempts (
    id INT(4) PRIMARY KEY AUTO_INCREMENT,
    ip VARCHAR(45) NOT NULL,
    email VARCHAR(255) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

```
### How It Works
1. Store Failed Attempts : Every time a login attempt fails (invalid email or password), an entry is stored in the login_attempts table.<br>
2. Check Attempt Limits:Before processing a new login, the RateLimiter checks if the IP/email combination has exceeded the maximum allowed attempts in the defined interval.<br>
3. Block Excessive Attempts: If the limit is reached, the login is denied. The user must wait until the retry window has expired before attempting again.<br>
4. Reset After Success:On successful login, all attempts for that user/email are cleared.

### Example Usage
Below is how you integrate the RateLimiter inside your login controller or login handler:
```php
use SecureAuth\Security\RateLimiter;

// Get client IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// 1. Store failed attempt
$baserepo->query(
    'INSERT INTO login_attempts(ip, email) VALUES(?, ?)',
    'ss',
    $ip,
    $email
);

// 2. Check if too many attempts
if ($rateLimiter->tooManyAttempts($ip, $email)) {
    $retryAfter = $rateLimiter->getRetryAfterSeconds($ip, $email);
    header('Retry-After: ' . $retryAfter);

    // Optionally send correct HTTP code (for APIs)
    // http_response_code(429);

    // Store error in session (for UI feedback)
    SessionHelper::flash('error', 'Too many login attempts. Please wait ' . $retryAfter . ' seconds.');

    header('location:index.php');
    exit();
}
```
### Example Workflow
Here’s how everything ties together in a login flow:<br>
Login Page (index.php)
```php
<?php if ($msg = SessionHelper::getFlash('error')): ?>
    <p style="color:red"><?= htmlspecialchars($msg); ?></p>
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
```
### Login Logic
This file handles:<br>
1. Input validation
2. CSRF token generation & validation
3. Rate limiting
4. User authentication
5. Remember Me functionality

```php
if ($rateLimiter->tooManyAttempts($ip, $email)) {
    $retryAfter = $rateLimiter->getRetryAfterSeconds($ip, $email);
    SessionHelper::flash('error', "Too many login attempts. Please wait {$retryAfter} seconds.");
    header('location:index.php');
    exit();
}

$result = $baserepo->query(
    'SELECT * FROM users WHERE email = ?',
    's',
    $email
)->fetchOne();

if ($result && Auth::AuthenticateUser($result, $email, $password)) {
    $baserepo->query('DELETE FROM login_attempts WHERE email = ?', 's', $email);
    // set session, regenerate CSRF, handle Remember Me, redirect
} else {
    $baserepo->query('INSERT INTO login_attempts(ip, email) VALUES(?, ?)', 'ss', $ip, $email);
    SessionHelper::flash('error', 'Invalid email or password');
    header('location:index.php');
    exit();
}
n
```
## SecureAuth PHP Package License

Copyright (c) 2025 Muradcade

Permission is granted to anyone to use, copy, and distribute this software for any purpose, including personal and commercial use.

You are free to:

- View the source code.
- Use it in your own projects.
- Fork it for personal use.

You are **not allowed** to:

- Modify the official repository.
- Claim ownership of the official codebase.
- Merge changes into the official repository.

The original author (Muradcade) retains the **exclusive right to update and maintain the official repository**.

---

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT ANY WARRANTY.  
USE IT AT YOUR OWN RISK. The author is not responsible for any damages, data loss, or other issues arising from the use of this software.
