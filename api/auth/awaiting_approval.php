<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

$fullname = isset($_SESSION['fname'], $_SESSION['lname']) ? htmlspecialchars($_SESSION['fname'] . ' ' . $_SESSION['lname']) : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Awaiting Approval | e-DataColls</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css">
    <style>
        .page-awaiting-approval {
            max-width: 600px;
            margin: 100px auto;
            padding: 2rem;
            text-align: center;
            color: #444;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .page-awaiting-approval h3 {
            color: #00aced;
            margin-bottom: 1rem;
        }
        .page-awaiting-approval p {
            margin: 0.5rem 0;
            line-height: 1.6;
        }
        .page-awaiting-approval a {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .page-awaiting-approval a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="page-awaiting-approval">
        <h3>
            Thank you for registering, <b><?= ucwords($fullname) ?>!</b>
        </h3>
        <p>Your account is currently awaiting approval by the system administrator.</p>
        <p>Please wait until your account is verified before accessing the full system.</p>
        <p>If you believe this is an error, please contact support: 0767 929226</p>
        <a href="<?= BASE_URL ?>/login.php">
            ← Back to Login
        </a>
    </div>
</body>
</html>


