<?php

require_once "pageutils.php";
require_once "htmlutils.php";


if(empty($_GET['log'])) {
	pageutils_clean_die();
} else {
	switch ($_GET['log']) {
		case 'data':
			$logfile = $otp_ini['dir']['log_dir'].$otp_ini['log']['data_update'];
			break;
		case 'script':
			$logfile = $otp_ini['dir']['log_dir'].$otp_ini['log']['scripts'];
			break;
		case 'error':
			$logfile = $otp_ini['dir']['log_dir'].$otp_ini['log']['page_error'];
			break;
		case 'auth':
			# check auth level, only admin should see this
			if (!empty($_SESSION['otpweb_auth_level']) && $_SESSION['otpweb_auth_level']) {
				$logfile = $otp_ini['dir']['log_dir'].$otp_ini['log']['site_auth'];
			} else {
				echo 'Sorry, you are not authorized to view this.';
				pageutils_cleanup();
				exit;
			}
			break;
		case 'access':
			# check auth level, only admin should see this
			if (!empty($_SESSION['otpweb_auth_level']) && $_SESSION['otpweb_auth_level']) {
				$logfile = $otp_ini['dir']['log_dir'].$otp_ini['log']['page_access'];
			} else {
				echo 'Sorry, you are not authorized to view this.';
				pageutils_cleanup();
				exit;
			}
			break;
		default:
			pageutils_clean_die();
			break;
	}
}

# Print document headers for the web page
html_print_header($_GET['log'] .' log');

# Document body starts here
body();

$db = pageutils_open_db();

echo '<h1>'. $_GET['log'] .' log</h1>';

pre();
include($logfile);
ppre();

echo "<p>-- end of page --</p>";

pbody();
phtml();
pageutils_cleanup();

?>