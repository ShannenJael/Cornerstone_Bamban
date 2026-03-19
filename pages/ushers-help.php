<?php
session_start();

$validUser = 'UshersHelp';
$validPass = 'HarvestUshers2026!';

if (isset($_GET['logout'])) {
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: ushers-help.php');
    exit;
}

if (!isset($_SESSION['ushers_help_auth']) || $_SESSION['ushers_help_auth'] !== true) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (hash_equals($validUser, $username) && hash_equals($validPass, $password)) {
            $_SESSION['ushers_help_auth'] = true;
            header('Location: ushers-help.php');
            exit;
        }

        $error = 'Invalid username or password.';
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ushers Help Login</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #0a0a0a;
                color: #ffffff;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
            }
            .login-card {
                background: #1a1a1a;
                border: 1px solid #2a2a2a;
                border-radius: 10px;
                padding: 32px;
                width: 100%;
                max-width: 360px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            }
            h1 {
                margin: 0 0 16px 0;
                font-size: 1.4rem;
                color: #14AFB1;
                text-align: center;
            }
            label {
                display: block;
                margin: 12px 0 6px;
                font-size: 0.95rem;
            }
            input {
                width: 100%;
                padding: 10px 12px;
                border-radius: 6px;
                border: 1px solid #333;
                background: #0f0f0f;
                color: #fff;
            }
            .error {
                background: rgba(220, 53, 69, 0.2);
                border: 1px solid #dc3545;
                color: #ffb3b3;
                padding: 10px;
                border-radius: 6px;
                margin-bottom: 12px;
                text-align: center;
            }
            button {
                width: 100%;
                padding: 12px;
                margin-top: 16px;
                border: none;
                border-radius: 6px;
                background: #14AFB1;
                color: #fff;
                font-size: 1rem;
                cursor: pointer;
            }
            button:hover {
                background: #0f8f92;
            }
        </style>
    </head>
    <body>
        <form class="login-card" method="post">
            <h1>Ushers Help Login</h1>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <label for="username">Username</label>
            <input id="username" name="username" type="text" autocomplete="username" required>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit">Sign In</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

readfile(__DIR__ . '/ushers-help-content.html');
