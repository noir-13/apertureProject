<?php
session_start();
require_once './includes/config.php';
require_once './includes/functions/auth.php';

if (isset($_GET['token'])) {
    $result = verifyEmail($_GET['token']);

    if ($result['success']) {
        header("refresh:3;url=completeProfile.php");
        setSession($result['userId']);
    } else {
        echo "Invalid token or expired";
    }
}
