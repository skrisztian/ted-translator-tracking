<?php

require_once "pageutils.php";
require_once "htmlutils.php";

# Print document headers for the web page
# html_print_header("List of Hungarian Translators");

# Document body starts here
# body();

$response = null;


# Check if we have all the necessary parameters for the API call.
# If not set the response to an error message.
if (isset($_GET['type']) && isset($_GET['id'])) {
	switch ($_GET['type']) {
		case 'task':
			$api_dir = "/teams/ted/tasks/";
			break;
		case 'activity':
			$api_dir = "/activity/";
			break;
		default:
			$response = "<p>Wrong type paramater. Cannot call Amara API.</p>";
			break;
	}
} else {
	$response = "<p>Some paramaters are missing. Cannot call Amara API.</p>";
}

# If no errors then do the call
if (!$response) {
	$api_dir = $api_dir . $_GET['id'];
	$amara_response = pageutils_amara_api($api_dir);
	if (!is_string($amara_response)) {
		echo '<h2>Response from Amara</h2>';
		echo '<table>';
		foreach ($amara_response as $key => $value) {
			echo '<tr>';
			echo "<th>$key</th>";
			echo "<td>$value</td>";
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo $amara_response;
	}
}


# End of document body
# pbody();
# phtml();
pageutils_cleanup();

?>