<?php 

require_once "pageutils.php";
require_once "htmlutils.php";

if(empty($_GET['id'])) {
	pageutils_clean_die();
} else {
	$ted_id = $_GET['id'];
}

$db = pageutils_open_db();

pageutils_prepare_for_sql($db, $ted_id, $ted_id_sql);
$sql = "SELECT * FROM ted_translators WHERE ted_id=$ted_id_sql";

if ($result = $db->query($sql)) {
	$row = $result->fetch_assoc();
	$result->free();
} else {
	pageutils_log('page_error', $db->error .' line '.__LINE__);
}

# Print document headers for the web page
html_print_header($row['ted_full_name']);

# Document body starts here
body();

echo "<h1>".pageutils_check_empty($row['ted_full_name'], 'Noname Translator')."</h1>"; 
?>

<h2>Translator data</h2>
<table border="1">
	<tr>
		<th>Full name:</th>
		<td><?php echo pageutils_check_empty($row['ted_full_name']); ?></td>
	</tr>
	<tr>
		<th>Amara ID:</th>
		<td><i>This user does not have an Amara account</i></td>
	</tr>
	<tr>
		<th>TED ID:</th>
		<td>
			<?php 
			if(isset($row['ted_id'])) {
				echo '<a href="http://ted.com/profiles/'.$row['ted_id'].'/translator" target="_blank" title="Open this translator\'s profile on TED.com" class="foreign_link">';
				echo $row['ted_id'];
				echo '</a>';
			} else {
				echo '<i>not set</i>';
			}
			?>
		</td>
	</tr>
</table>
<p>-- end of page --</p>

<?php

$db->close();
pbody();
phtml();
pageutils_cleanup();

?>
