<?php
require_once __DIR__ . '/../../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="page-awaiting-approval" style="padding: 2rem; font-size: 11px; text-align: center; color: #444;">
  <h3> System does not allow users to recover their password! </h3>
     <p> If you forget the password of your account, you are advised to request a new password from the system administrator via email or mobile phone. Your request will be processed after approval by the system administrator.</p>
     <p> <strong>Contact Support:</strong> +255 767 929 226 / +255 717 929 226 </p>
     <p> <strong>Emails:</strong> hbaraka2010@gmail.com, mbaraka.abdallah@edatacolls.co.tz, mbaraka.abdallah@nimr.or.tz </p>
<br>
 <a href="<?= BASE_URL ?>/login.php" style="text-decoration:none; color:#007bff;">   ← Back to Login  </a>
</div>


