<?php
require_once './includes/functions/config.php';
require_once './includes/functions/auth.php';
require_once './includes/functions/function.php';
require_once './includes/functions/csrf.php';
require_once './includes/functions/session.php';


if (isset($_GET['token'])) {
    $result = verifyEmail($_GET['token']);

    if ($result['success']) {
        header("refresh:3;url=completeProfile.php");
        setSession($result['userId']);
    } else {
        echo "Invalid token or expired";
    }
}
