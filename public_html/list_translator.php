<?php

require_once "pageutils.php";
require_once "htmlutils.php";

# Print document headers for the web page
html_print_header("List of Hungarian Translators");

# Document body starts here
body();

echo "<h1>List of translators</h1>";

# Open database. We do not know how many lines in total will come back from the API,
# so we will do the inserts in batches after each API call.
$db = pageutils_open_db();

if (isset($_GET['filter'])) {
	switch ($_GET['filter']) {
		case 'contributor':
			$where = "WHERE amara_role='contributor'";
			break;
		case 'manager':
			$where = "WHERE amara_role='manager'";
			break;
		case 'owner':
			$where = "WHERE amara_role='owner'";
			break;
		case 'admin':
			$where = "WHERE amara_role='admin'";
			break;
		default:
			pageutils_clean_die();
			break;
	}
} else {
	$where = null;
}

$sql = "SELECT * FROM otp_translators $where ORDER BY (full_name = '') ASC, full_name";

echo '<table id="table_1" class="tablesorter">';
echo '<thead>';
echo '<tr>';
echo '<th>Name</th>';
echo '<th>Amara ID</th>';
echo '<th>TED ID</th>';
echo '<th>Amara role</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

if ($result = $db->query($sql)) {
	while($row = $result->fetch_assoc()) {
	if ($row['full_name'] == '') {
		$row['full_name'] = '<i>No name</i>';
	}
		echo '<tr>';

		echo '<td>';
		echo '<a href="view_translator.php?id='. urlencode($row['amara_id']) . '" title="Open statistics about translator">'.$row['full_name'].'</a>';
		echo '</td>';

		echo '<td>';
		if ($row['amara_id']) {
			echo '<a href="http://amara.org/en/profiles/profile/'. urlencode($row['amara_id']) .'" title="Open profile page on amara.org" target="_blank" class="foreign_link">'.$row['amara_id'].'</a>';
		}
		echo '</td>';

		echo '<td>';
		if ($row['ted_id']) {
			echo '<a href="http://www.ted.com/profiles/'.$row['ted_id'].'/translator" title="Open profile page on ted.com" target="_blank" class="foreign_link">'.$row['ted_id'].'</a>';
		} else {
			echo '<i>not set</i>';
		}
		echo '</td>';

		echo '<td>';
		echo pageutils_check_empty($row['amara_role']);
		echo '</td>';
		echo '</tr>';		
	}
	$result->free();
} else {
	pageutils_log('page_error', $db->error .'line '.__LINE__);
}
echo '</tbody>';
echo '</table>';

# Sorting java script
echo '<script type="text/javascript">
		$(document).ready(function() { 
       		$(\'#table_1\').tablesorter(); 
       	});
	  </script>';




$db->close();


# End of document body
pbody();
phtml();
pageutils_cleanup();

?>