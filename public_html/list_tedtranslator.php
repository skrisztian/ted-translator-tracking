<?php

require_once "pageutils.php";
require_once "htmlutils.php";

if (isset($_GET['order_by'])) {
	$order_by = $_GET['order_by'];
} else {
	$order_by = 'ted_full_name';
}

# Print document headers for the web page
html_print_header("List of Unidentified Hungarian Translators");
body();

?>


<h1>Hungarian translators from TED without Amara account</h1>

<table border="1">
	<tr>
		<th><a href="list_tedtranslator.php?order_by=ted_full_name" class="order_by">Name</a></th>
		<th><a href="list_tedtranslator.php?order_by=ted_id" class="order_by">TED ID</a></th>
		<th>Last updated</th>
	</tr>
	<tr>
		<?php
		$db = pageutils_open_db();
		$db->real_escape_string($order_by);
		$sql = "SELECT * FROM ted_translators ORDER BY $order_by";
		if ($result = $db->query($sql)) {
			while($row = $result->fetch_assoc()) {
				echo '<tr>';
				echo '<td>'. pageutils_view_link('ted_translator', $row['ted_id'], null, $row['ted_full_name']) .'</td>';
				echo '<td>';
				echo '<a href="http://www.ted.com/profiles/'.$row['ted_id'].'/translator" title="Open profile page on ted.com" target="_blank" class="foreign_link">'. $row['ted_id'] .'</a>';
				echo '</td>';
				echo '<td>'. pageutils_check_empty($row['last_update']) .'</td>';
				echo '</tr>';
			}
			$result->free();
		} else {
			pageutils_log('page_error', $db->error .'line '.__LINE__);
		}
		$db->close();
		?>
</table>
</body>
</html>


<?php pageutils_cleanup(); ?>