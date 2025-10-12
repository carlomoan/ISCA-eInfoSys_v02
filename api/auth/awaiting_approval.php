<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$fullname = isset($_SESSION['fname'], $_SESSION['lname']) ? htmlspecialchars($_SESSION['fname'] . ' ' . $_SESSION['lname']) : 'User';
?>
<div class="page-awaiting-approval" style="padding: 2rem; font-size: 11px; text-align: center; color: #444;">
<h3>
    Thank you for registering, <b><?= ucwords($fullname) ?>!</b>
</h3>
    <p>Your account is currently awaiting approval by the system administrator.</p>
    <p>Please wait until your account is verified before accessing the full system.</p>
    <p>If you believe this is an error, please contact support: 0767 929226 </p>
    <br>
         <a href="http://localhost/ISCA-eInfoSys_v02/login.php" style="text-decoration:none; color:#007bff;">
        ← Back to Login
    </a>
</div>


