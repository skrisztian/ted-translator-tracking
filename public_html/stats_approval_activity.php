<?php

require_once "pageutils.php";
require_once "htmlutils.php";


# Print document headers for the web page
html_print_header("Approval and activity statistics", 'plot');

# Document body starts here
body();

$db = pageutils_open_db();

# Published talks and approval waiting time
$sql = "SELECT DATE_FORMAT(a.completed, '%Y-%m') AS month, count(*) AS approvals, 
		ROUND(AVG(TIMESTAMPDIFF(SECOND, 
			(
				SELECT MAX(r.completed) 
				FROM otp_tasks r 
				WHERE a.video_id = r.video_id 
				AND r.type = 'Review' 
				AND r.completed < a.completed 
				AND r.completed is NOT NULL
			), a.completed)))/(60*60*24) AS wait
		FROM otp_tasks a
		WHERE a.completed IS NOT NULL  
		AND a.assignee IS NOT NULL 
		AND a.type = 'Approve'
		AND a.completed >= DATE('2012-05-01 00:00:01')
		GROUP BY month
		ORDER BY month ASC";

if ($result = $db->query($sql)) {
	while ($row = $result->fetch_assoc()) {
		# Save content in a $data array
		$data1[] = $row;
	}
	$result->free();
} else {
	pageutils_log('page_error', $db->error .' line '.__LINE__);
}

# Number of active translators/reviewers/approvers monthly
$sql = "SELECT count(distinct user) AS translators, DATE_FORMAT(created, '%Y-%m') AS month
		FROM otp_activity
		WHERE type IN (3, 4, 6, 7, 8, 10, 12, 13, 14, 15)
		AND created >= DATE('2012-05-01 00:00:01')
		GROUP BY month
		ORDER BY month ASC";

if ($result = $db->query($sql)) {
	while ($row = $result->fetch_assoc()) {
		# Save content in a $data array
		$data2[] = $row;
	}
	$result->free();
} else {
	pageutils_log('page_error', $db->error .' line '.__LINE__);
}

?>

<h1>Approval and activity statistics</h1>
<h3>Monthly approvals statistics for Hungarian translations</h3>

<p>The chart below shows how many talks we publish in a month (including all talk types, translating and subtitling) and on average how many days it takes to approve a talk.</p> <p>The approval days are counted from the completion of the review till the completion of the approval. That is it includes the time while the approval task is waiting to be picked up, as well as the actual work on the approval. Rejected approvals are counted as completed ones, since after the corrected review a new approval task is generated.</p>
<p><i>Hower your mouse over the bars to see the actual value.</i></p>

<div id="chart1" style="height:400px;width:1000px; "></div>

<h3>Monthly activity statistics for Hungarian translations</h3>
<p>This chart shows the count of all translators with any activity on Hungarian translations (including all talk types, translating and subtitling) in the given month. The activities here strictly mean working on a task, so (re)assigning a talk but doing nothing with it (e. g. not editing or commenting on it) is not counted.</p>

<div id="chart2" style="height:400px;width:1000px; "></div>

<script type="text/javascript" src="src/plugins/jqplot.barRenderer.min.js"></script>
<script type="text/javascript" src="src/plugins/jqplot.categoryAxisRenderer.min.js"></script>
<script type="text/javascript" src="src/plugins/jqplot.pointLabels.min.js"></script>
<script type="text/javascript" src="src/plugins/jqplot.canvasTextRenderer.min.js"></script>
<script type="text/javascript" src="src/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>
<script type="text/javascript" src="src/plugins/jqplot.highlighter.min.js"></script>


<script type="text/javascript">

$(document).ready(function(){
    var s1 = <?php print_data($data1, 'approvals'); ?>;
    var s2 = <?php print_data($data1, 'wait'); ?>;
    var s3 = <?php print_data($data2, 'translators'); ?>;
    var ticks = <?php print_data($data1, 'month', 'string'); ?>;
     
    var plot1 = $.jqplot('chart1', [s1, s2], {
        // The "seriesDefaults" option is an options object that will
        // be applied to all series in the chart.
        title: 'Hungarian approval statistics',
        seriesDefaults:{
            renderer:$.jqplot.BarRenderer,
            rendererOptions: {fillToZero: true}
        },
    	axesDefaults: {
	        tickRenderer: $.jqplot.CanvasAxisTickRenderer ,
	        tickOptions: {
	          angle: -30,
	          fontSize: '10pt'
	      	}
        },
        highlighter: {
        	show: true,
        	tooltipAxes: 'y',
        	sizeAdjust: -5,
        	lineWidthAdjust: 0
        },
        // Custom labels for the series are specified with the "label"
        // option on the series option.  Here a series option object
        // is specified for each series.
        series:[
            {label:'Number of approvals in a month'},
            {label:'How many days an approval took on average'},
        ],
        // Show the legend and put it outside the grid, but inside the
        // plot container, shrinking the grid to accomodate the legend.
        // A value of "outside" would not shrink the grid and allow
        // the legend to overflow the container.
        legend: {
            show: true,
            placement: 'insideGrid'
        },
        axes: {
            // Use a category axis on the x axis and use our custom ticks.
            xaxis: {
                renderer: $.jqplot.CategoryAxisRenderer,
                ticks: ticks
            },
            // Pad the y axis just a little so bars can get close to, but
            // not touch, the grid boundaries.  1.2 is the default padding.
            yaxis: {
                pad: 1.05,
                tickOptions: {formatString: '%d'}
            }
        }
    });

	var plot2 = $.jqplot('chart2', [s3], {
        // The "seriesDefaults" option is an options object that will
        // be applied to all series in the chart.
        title: 'Active Hungarian translators monthly',
        seriesDefaults:{
            renderer:$.jqplot.BarRenderer,
            rendererOptions: {fillToZero: true}
        },
    	axesDefaults: {
	        tickRenderer: $.jqplot.CanvasAxisTickRenderer ,
	        tickOptions: {
	          angle: -30,
	          fontSize: '10pt'
	      	}
        },
        highlighter: {
        	show: true,
        	tooltipAxes: 'y',
        	sizeAdjust: -5,
        	lineWidthAdjust: 0
        },
        // Custom labels for the series are specified with the "label"
        // option on the series option.  Here a series option object
        // is specified for each series.
        series:[
            {label:'Number of translators with any activity in a month'},
        ],
        // Show the legend and put it outside the grid, but inside the
        // plot container, shrinking the grid to accomodate the legend.
        // A value of "outside" would not shrink the grid and allow
        // the legend to overflow the container.
        legend: {
            show: true,
            placement: 'insideGrid'
        },
        axes: {
            // Use a category axis on the x axis and use our custom ticks.
            xaxis: {
                renderer: $.jqplot.CategoryAxisRenderer,
                ticks: ticks
            },
            // Pad the y axis just a little so bars can get close to, but
            // not touch, the grid boundaries.  1.2 is the default padding.
            yaxis: {
                pad: 1.05,
                tickOptions: {formatString: '%d'}
            }
        }
    });

});

</script>

<?php

pbody();
phtml();
pageutils_cleanup();


//------------- FUNCTIONS ------------------//

function print_data($data, $label, $type=null) {
	foreach ($data as $key => $value) {
		$list[] = $value[$label];
	}

	if ($type == 'string') {
		echo "['";
		echo implode("', '", $list);
		echo "']";
	} else {
		echo '[';
		echo implode(', ', $list);
		echo ']';
	}
}

?>