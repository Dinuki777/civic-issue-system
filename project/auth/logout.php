<?php

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';


if (isLoggedIn()) {

    logAudit(
        $conn,
        $_SESSION['user_id'],
        'User Logout',
        'user',
        $_SESSION['user_id'],
        'User logged out'
    );

}


logout();

?>