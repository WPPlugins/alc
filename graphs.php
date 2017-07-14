<?php

function insertGraphs($linkId = 0)
{
	global $wpdb, $table_prefix;
	
	$daysInMonth = array(1 => 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	$currentMonth = date("n");
	$currentYear = date("Y");
	$currentMonthData = array();
	$uniqueValues = array();
	
	// get the countries required for the current month
	$query = "	SELECT
						DISTINCT {$table_prefix}alc_address.country
				FROM
						{$table_prefix}alc_address
				WHERE
						({$table_prefix}alc_address.linkId = $linkId)";
	$rows = $wpdb->get_results($query);
	
	$countries = array();
	
	// initialise data array for the current month
	foreach ($rows as $row) {
		$countries[$row->country] = 0;
	}
	
	for ($count = 1 ; $count <= $daysInMonth[$currentMonth] ; $count++) {
		$currentMonthData[$count] = $countries;
	}
	
	// populate the array with data
	$query = "	SELECT
						DAY({$table_prefix}alc_redirectlog.redirectDateTime)
					AS	redirectDay,
						COUNT({$table_prefix}alc_redirectlog.addressId)
					AS	redirects,
						{$table_prefix}alc_address.country
				FROM
						{$table_prefix}alc_redirectlog
				INNER JOIN
						{$table_prefix}alc_address
					ON	({$table_prefix}alc_address.id = {$table_prefix}alc_redirectlog.addressId)
				WHERE
						(MONTH({$table_prefix}alc_redirectlog.redirectDateTime) = $currentMonth)
					AND	(YEAR({$table_prefix}alc_redirectlog.redirectDateTime) = $currentYear)
				GROUP BY
						YEAR({$table_prefix}alc_redirectlog.redirectDateTime),
						DAY({$table_prefix}alc_redirectlog.redirectDateTime),
						{$table_prefix}alc_address.country";
		
	$rows = $wpdb->get_results($query);

	$graphData = "['Day',";
	
	foreach ($countries as $key => $value) {
		$graphData .= "'$key',";
	}
	
	$graphData = rtrim($graphData, ',');
	$graphData .= '],';
	
	foreach ($rows as $row) {
		$currentMonthData[$row->redirectDay][$row->country] = $row->redirects;
		
		$uniqueValues[$row->redirects] = 0;
	}
	
	for ($count = 1 ; $count <= $daysInMonth[$currentMonth] ; $count++) {
		$graphData .= "[{$count},";
		
		foreach ($countries as $country => $value) {
			$graphData .= $currentMonthData[$count][$country] . ",";
		}
		
		$graphData = rtrim($graphData, ',');
		$graphData .= '],';
	}
	
	$graphData = rtrim($graphData, ',');
	
	$uniqueKeys = sizeof($uniqueValues) + 1;

?>
<script type="text/javascript">

      // Load the Visualization API and the piechart package.
      google.load('visualization', '1', {'packages':['corechart']});

      // Set a callback to run when the Google Visualization API is loaded.
      google.setOnLoadCallback(drawChart);

      // Callback that creates and populates a data table,
      // instantiates the pie chart, passes in the data and
      // draws it.
      function drawChart() {

        // Create the data table.
		var data = google.visualization.arrayToDataTable([<?php echo $graphData; ?>]);

        // Set chart options
        var options = {'title':'Redirects for the current month',
                       'height':400,
					   'hAxis':{'gridlines':{'count':29},
								'title':'Date'},
						'vAxis':{'gridlines':{'count':<?php echo $uniqueKeys; ?>}}};

        // Instantiate and draw our chart, passing in some options.
        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
<?php
}

?>