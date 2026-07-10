<?php
require_once 'server/session.php';

if (!empty($_SESSION['user'])) {
    header('Location: /index.php');
    exit;
}

$error = $_GET['error'] ?? '';
$errorMsg = match($error) {
    'invalid'  => 'Invalid email or password.',
    'inactive' => 'Your account has been deactivated. Contact an administrator.',
    'missing'  => 'Please enter your email and password.',
    default    => '',
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In — Ones n Zeros ERP</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body { height: 100%; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }

    /* ── Split layout ── */
    .login-wrapper {
      display: flex;
      height: 100vh;
    }

    /* Left: image panel */
    .login-image-panel {
      flex: 1;
      background-image: url('./assets/img/auth/login-bg.jpg');
      background-position: center center;
      background-size: cover;
      background-repeat: no-repeat;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .login-image-panel::before {
      content: '';
      position: absolute;
      inset: 0;
      background: rgba(8, 18, 48, 0.30);
    }

    .welcome-text {
      position: relative;
      z-index: 1;
      color: #fff;
      font-size: 2.8rem;
      font-weight: 300;
      letter-spacing: 0.55em;
      text-transform: uppercase;
      text-shadow: 0 2px 24px rgba(0,0,0,0.4);
    }

    /* Right: form panel */
    .login-form-panel {
      width: 440px;
      flex-shrink: 0;
      background: #fff;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem 3.5rem;
    }

    .login-form-panel h1 {
      font-size: 2rem;
      font-weight: 500;
      color: #111;
      margin-bottom: 2.5rem;
      align-self: flex-start;
    }

    /* Underline inputs */
    .form-field {
      width: 100%;
      margin-bottom: 2rem;
    }

    .field-header {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-bottom: 0.5rem;
    }

    .field-header label {
      font-size: 0.9rem;
      color: #999;
    }

    .forgot-link {
      font-size: 0.78rem;
      color: #999;
      text-decoration: none;
    }

    .forgot-link:hover { color: #1a237e; }

    .underline-input {
      width: 100%;
      border: none;
      border-bottom: 1.5px solid #ddd;
      outline: none;
      font-size: 1rem;
      padding: 0.45rem 0;
      background: transparent;
      color: #222;
      transition: border-color 0.2s;
    }

    .underline-input:focus { border-bottom-color: #1a237e; }

    /* Login button */
    .btn-login {
      width: 100%;
      padding: 0.9rem;
      background: #1a237e;
      color: #fff;
      border: none;
      border-radius: 50px;
      font-size: 1rem;
      font-weight: 500;
      letter-spacing: 0.04em;
      cursor: pointer;
      margin-top: 0.5rem;
      transition: background 0.2s, transform 0.1s;
    }

    .btn-login:hover  { background: #283593; }
    .btn-login:active { transform: scale(0.98); }

    /* Signup footer */
    .signup-text {
      margin-top: 2.5rem;
      font-size: 0.85rem;
      color: #aaa;
      text-align: center;
    }

    .signup-text a {
      color: #1a237e;
      text-decoration: none;
      font-weight: 600;
    }

    /* Error alert */
    .login-alert {
      width: 100%;
      padding: 0.75rem 1rem;
      background: #fdecea;
      color: #b71c1c;
      border-radius: 8px;
      font-size: 0.875rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    /* Mobile: hide image panel */
    @media (max-width: 768px) {
      .login-image-panel { display: none; }
      .login-form-panel  { width: 100%; padding: 2.5rem 1.75rem; }
    }
  </style>
</head>
<body>

<div class="login-wrapper">

  <!-- Left: image + welcome text -->
  <div class="login-image-panel">
    <span class="welcome-text">Welcome</span>
  </div>

  <!-- Right: login form -->
  <div class="login-form-panel">

    <h1>Login</h1>

    <?php if ($errorMsg): ?>
      <div class="login-alert">
        <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
        <?= htmlspecialchars($errorMsg) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/server/auth.php" novalidate style="width:100%">

      <div class="form-field">
        <div class="field-header">
          <label for="login-email">Email</label>
        </div>
        <input class="underline-input" type="email" id="login-email" name="email"
               value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"
               required autofocus autocomplete="username" />
      </div>

      <div class="form-field">
        <div class="field-header">
          <label for="login-password">Password</label>
          <a href="#" class="forgot-link">Forgot password?</a>
        </div>
        <input class="underline-input" type="password" id="login-password" name="password"
               required autocomplete="current-password" />
      </div>

      <button type="submit" class="btn-login">Login</button>

    </form>

    <p class="signup-text">
      Ones n Zeros &copy; <?= date('Y') ?>
    </p>

  </div>
</div>

</body>
</html>
