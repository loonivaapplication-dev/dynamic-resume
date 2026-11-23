<?php
session_start();
$_SESSION = [];                                   // clear data
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
              $p['path'], $p['domain'], $p['secure'], $p['httponly']); // kill cookie
}
session_destroy();                                // destroy on server
header('Location: login.php');
exit;