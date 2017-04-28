<?php

require_once "pageutils.php";
require_once "htmlutils.php";

# Calculate max time span
$max_span = (date_diff(date_create(date('d-m-Y')), date_create('01-05-2012'))->y)*12; 
$max_span += date_diff(date_create(date('d-m-Y')), date_create('01-05-2012'))->m;

if (!empty($_GET['time_span'])) {
	if (!is_numeric($_GET['time_span'])) {
		pageutils_clean_die();
	} elseif ($_GET['time_span'] <= $max_span) {
		$time_span = $_GET['time_span'];
	} else {
		$time_span = 3; # months to look for	
	}
} else {
	$time_span = 3; # months to look for	
}

# Set up the array which stores all data related to the translators
$translator_data = array();

# Print document headers for the web page
html_print_header("List of translators active translators");

# Document body starts here
body();

$s = ($time_span - 1)?'s':'';

echo "<h1>Active translators in the last $time_span month$s</h1>";

# Open database. We do not know how many lines in total will come back from the API,
# so we will do the inserts in batches after each API call.
$db = pageutils_open_db();

# Get list of translators, who were active in the last $time_span months,
# that is they have completed works
$sql = "SELECT DISTINCT tr.amara_id AS id, tr.full_name AS name FROM otp_translators tr 
		LEFT OUTER JOIN otp_tasks t
		ON tr.amara_id = t.assignee
		WHERE t.completed IS NOT NULL
		AND t.completed > DATE_SUB(NOW(), INTERVAL $time_span MONTH)";

if ($result = $db->query($sql)) { 
	while($row = $result->fetch_assoc()) {
		$translator_data[$row['id']]['name'] = $row['name'];
	}
} else {
	pageutils_log('page_error', $db->error .' line '.__LINE__);
}

# Get the number of completed translatoins, reviews, approvals
if (count($translator_data) > 0) {

	# List of translators to filter on
	$sql_list = "('" . implode("','", array_keys($translator_data)) ."')";

	# Subtitle
	$sql = "SELECT assignee AS id, count(*) AS total
	FROM otp_tasks
	WHERE type = 'Subtitle'
	AND completed IS NOT NULL
	AND assignee IN $sql_list
	AND completed > DATE_SUB(NOW(), INTERVAL $time_span MONTH) 
	GROUP BY assignee";

	if ($result = $db->query($sql)) { 
		while($row = $result->fetch_assoc()) {
			$translator_data[$row['id']]['p_subtitle'] = $row['total'];
		}
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}

	# Translate
	$sql = "SELECT assignee AS id, count(*) AS total
	FROM otp_tasks
	WHERE type = 'Translate'
	AND completed IS NOT NULL
	AND assignee IN $sql_list
	AND completed > DATE_SUB(NOW(), INTERVAL $time_span MONTH) 
	GROUP BY assignee";

	if ($result = $db->query($sql)) { 
		while($row = $result->fetch_assoc()) {
			$translator_data[$row['id']]['p_translate'] = $row['total'];
		}
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}

	# Review
	$sql = "SELECT assignee AS id, count(*) AS total
	FROM otp_tasks
	WHERE type = 'Review'
	AND completed IS NOT NULL
	AND assignee IN $sql_list
	AND completed > DATE_SUB(NOW(), INTERVAL $time_span MONTH) 
	GROUP BY assignee";

	if ($result = $db->query($sql)) { 
		while($row = $result->fetch_assoc()) {
			$translator_data[$row['id']]['p_review'] = $row['total'];
		}
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}

	# Approve
	$sql = "SELECT assignee AS id, count(*) AS total
	FROM otp_tasks
	WHERE type = 'Approve'
	AND completed IS NOT NULL
	AND assignee IN $sql_list
	AND completed > DATE_SUB(NOW(), INTERVAL $time_span MONTH) 
	GROUP BY assignee";

	if ($result = $db->query($sql)) { 
		while($row = $result->fetch_assoc()) {
			$translator_data[$row['id']]['p_approve'] = $row['total'];
		}
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}

	# Total number of lines translated up to now
	$sql = "SELECT t.assignee AS id, SUM(s.subtitle_count) AS total
			FROM otp_tasks t
			INNER JOIN otp_subtitles s
			ON t.video_id = s.video_id
			WHERE t.type = 'Translate'
			AND t.completed IS NOT NULL
			AND assignee IN $sql_list 
			GROUP BY t.assignee";

	if ($result = $db->query($sql)) { 
		while($row = $result->fetch_assoc()) {
			$translator_data[$row['id']]['lines'] = $row['total'];
		}
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}

	# Current assignments
	# Subtitle
	$sql = "SELECT assignee AS id, count(*) AS total
	FROM otp_tasks
	WHERE type = 'Subtitle'
	AND completed IS NULL
	AND assignee IN $sql_list
	GROUP BY assignee";

	if ($result = $db->query($sql)) { 
		while($row = $result->fetch_assoc()) {
			$translator_data[$row['id']]['c_subtitle'] = $row['total'];
		}
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}

	# Current assignments
	# Translate
	$sql = "SELECT assignee AS id, count(*) AS total
	FROM otp_tasks
	WHERE type = 'Translate'
	AND completed IS NULL
	AND assignee IN $sql_list
	GROUP BY assignee";

	if ($result = $db->query($sql)) { 
		while($row = $result->fetch_assoc()) {
			$translator_data[$row['id']]['c_translate'] = $row['total'];
		}
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}

	# Review
	$sql = "SELECT assignee AS id, count(*) AS total
	FROM otp_tasks
	WHERE type = 'Review'
	AND completed IS NULL
	AND assignee IN $sql_list
	GROUP BY assignee";

	if ($result = $db->query($sql)) { 
		while($row = $result->fetch_assoc()) {
			$translator_data[$row['id']]['c_review'] = $row['total'];
		}
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}

	# Approve
	$sql = "SELECT assignee AS id, count(*) AS total
	FROM otp_tasks
	WHERE type = 'Approve'
	AND completed IS NULL
	AND assignee IN $sql_list
	GROUP BY assignee";

	if ($result = $db->query($sql)) { 
		while($row = $result->fetch_assoc()) {
			$translator_data[$row['id']]['c_approve'] = $row['total'];
		}
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}

	# Latest activity date
	$sql = "SELECT user AS id, MAX(created) AS a_date FROM `otp_activity` 
	WHERE user IN $sql_list 
	GROUP BY user";

	if ($result = $db->query($sql)) { 
		while($row = $result->fetch_assoc()) {
			$translator_data[$row['id']]['a_date'] = $row['a_date'];
		}
	} else {
		pageutils_log('page_error', $db->error .' line '.__LINE__);
	}
}

# Calculate totals
foreach ($translator_data as $key => $value) {
	$p_total = 0;
	$c_total = 0;	
	foreach ($value as $key1 => $value1) {
		switch (substr($key1, 0, 2)) {
			case 'p_':
				$p_total += $value1;
				break;
			case 'c_':
				$c_total += $value1;
				break;
			default:
				break;
		}
	}
	$translator_data[$key]['p_total'] = $p_total;
	$translator_data[$key]['c_total'] = $c_total; 
}

# Now pour the array into a nicely formatted table
?>

<table id="table_1" class="tablesorter">
	<thead>
		<tr>
			<th class="sorter-false" colspan="2"></th>
			<th class="sorter-false" colspan="5">In the last <?php echo $time_span; ?> month<?php echo $s; ?> completed</th>
			<th class="sorter-false" colspan="6">Currently working on</th>
		</tr>
		<tr>
			<th>Name</th>
			<th>Lines translated*</th>
			<th>Subtitle</th>
			<th>Translate</th>
			<th>Review</th>
			<th>Approve</th>
			<th>Total</th>
			<th>Subtitle</th>
			<th>Translate</th>
			<th>Review</th>
			<th>Approve</th>
			<th>Total</th>
			<th>Last activity on</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($translator_data as $translator => $data): ?>
			<tr>
				<td><?php echo pageutils_view_link('translator', $translator, null, ($data['name']?$data['name']:'<i>No name</i>')); ?></td>
				<td class="numeric"><?php echo @pageutils_check_empty($data['lines'], 0); ?></td>
				<td class="numeric"><?php echo @pageutils_check_empty($data['p_subtitle'], 0); ?></td>
				<td class="numeric"><?php echo @pageutils_check_empty($data['p_translate'], 0); ?></td>
				<td class="numeric"><?php echo @pageutils_check_empty($data['p_review'], 0); ?></td>
				<td class="numeric"><?php echo @pageutils_check_empty($data['p_approve'], 0); ?></td>
				<td class="numeric"><?php echo @pageutils_check_empty($data['p_total'], 0); ?></td>
				<td class="numeric"><?php echo @pageutils_check_empty($data['c_subtitle'], 0); ?></td>
				<td class="numeric"><?php echo @pageutils_check_empty($data['c_translate'], 0); ?></td>
				<td class="numeric"><?php echo @pageutils_check_empty($data['c_review'], 0); ?></td>
				<td class="numeric"><?php echo @pageutils_check_empty($data['c_approve'], 0); ?></td>
				<td class="numeric"><?php echo @pageutils_check_empty($data['c_total'], 0); ?></td>
				<td class="numeric"><?php echo @pageutils_check_empty($data['a_date'], 0); ?></td>
			</tr>
		<?php endforeach ?>
	</tbody>
</table>

<p>* Since 1st May 2012
<p>In total <b><?php echo count($translator_data); ?></b> translators have been active in the last <?php echo $time_span; ?> month<?php echo $s; ?>.</p>

<form action="list_translator_active.php" method="get">
	<p>If you want, you can select a different time frame:
		<select name="time_span">
			<?php for ($i=0; $i < $max_span; $i++) { 
				echo '<option value="'. ($i+1) .'">'. ($i+1) .' month'. ($i?'s':'') .'</option>';
			} ?>
		</select>
		<input type="submit" />
	</p>
</form>

<script type="text/javascript">
	$(document).ready(function() { 
		$('#table_1').tablesorter(); 
	});
</script>

<?php

$db->close();

# End of document body
pbody();
phtml();
pageutils_cleanup();

?>