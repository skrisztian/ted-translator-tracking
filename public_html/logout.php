<?php

require_once 'pageutils.php';

session_start();
$oldlocation = preg_replace('/\?.*$/', '', $_SERVER["HTTP_REFERER"]);

# Destroy sesion values
session_destroy();

pageutils_log('site_auth', $_SESSION['otpweb_auth_username'] .' logout', basename($oldlocation));
pageutils_cleanup();

# Send the user back to the referrer page
header("Location: $oldlocation");
exit;

?>