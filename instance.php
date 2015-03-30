<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-gb" lang="en-gb" >
	<head>
		<meta charset="UTF-8">
		<title>Plotted Instances</title>
		<script src="https://www.google.com/jsapi" type="text/javascript"></script>
		<script type="text/javascript">
		google.load('visualization', '1.1', {'packages':['annotationchart']});
		google.setOnLoadCallback(drawChartPoint);
		function drawChartPoint() {
			var data = new google.visualization.DataTable();
			data.addColumn('number', 'x');

			<?php
				$instance = "c101";
				if(isset($_GET["instance"]) && strcmp(dirname($_GET["instance"]) ,".")==0) $instance = $_GET["instance"];
				$myInstanceFile = fopen("data/Solomon/$instance.txt", "r") or die('Unable to open file');
				$mySolutionFile = fopen("data/Solutions/$instance.txt", "r");
				if ($mySolutionFile) {
					$solution = true;
				} else {
					$mySolutionFile = fopen("data/Solutions/".$instance."t.txt", "r");
					if($mySolutionFile) {
						$solution = true;
					} else {
						$mySolutionFile = fopen("data/Solutions/".$instance."_sol.txt", "r");
						if ($mySolutionFile) {
							$solution = true;
						} else {
							$solution = false;
						}
					}
				}

				if($solution){
					// Let go of first line in solution
					fgets($mySolutionFile);
					$solution_points = '';
					$series_infos = array();
					$lines_number = explode(" ", fgets($mySolutionFile))[1];
					for($i = 0; $i < $lines_number; $i++) {
						$line = trim(fgets($mySolutionFile));
						$series_infos[$i] = explode(" ", trim(explode("|", $line)[0]));
						$solution_points .=  ' '.trim(explode("|", $line)[1]).' 0';
						echo"			data.addColumn('number', 'customers series ".$i."');\n";
						echo"			data.addColumn({type:'string', role:'tooltip'});\n";
					}
					$solution_points = explode(' ', trim($solution_points));
				} else {
					echo"			data.addColumn('number', 'customers');\n";
					echo"			data.addColumn({type:'string', role:'tooltip'});\n";
				}
			?>

			data.addColumn('number', 'depot');
			data.addColumn({type:'string', role:'tooltip'}); // annotation role col.

			var infos = new google.visualization.DataTable();
			infos.addColumn('number', 'twOpen');
			infos.addColumn('number', 'twClose');
			infos.addColumn('number', 'service');
			infos.addColumn('number', 'demand');

			<?php
				if ($solution) {
					$complete_instance = substr(fread($myInstanceFile, filesize("data/Solomon/$instance.txt")), 90);
					fclose($myInstanceFile);
					$myInstanceFile = fopen("data/Solomon/$instance.txt", "r") or die('Unable to open file');
					$name = trim( fgets($myInstanceFile) );
					for ($i = 1; $i < 4; $i++) fgets($myInstanceFile);
					$linearray = explode(" ", preg_replace('/\s+/', ' ',fgets($myInstanceFile)));
					$n = $linearray[1]; $q = $linearray[2];
					for ($i = 0; $i < 4; $i++) fgets($myInstanceFile);
					$max_x = 0; $max_y = 0;
					$current_series = -1;
					$reached_zero = false;
					foreach ($solution_points as $point) {
						// Find corresponding line in instance file
						preg_match("/\n[ ]{1,10} ".$point." ([^\n]{1,200})/", $complete_instance, $match);
						$linearray = explode(" ", preg_replace('/\s+/', ' ', $match[0]));
						$id = $linearray[1];
						$x = $linearray[2]; $max_x = max($x, $max_x);
						$y = $linearray[3]; $max_y = max($y, $max_y);
						$demand = $linearray[4];   $twOpen = $linearray[5];
						$twClose = $linearray[6];  $service = $linearray[7];

						if($id == 0 && $twClose == 0) break;
						if($id == 0) {
							$depot_values = array($x, $y, $twOpen, $twClose, $service, $demand);
							$depot_point = "          data.addRow([$x,".str_repeat("null,null,", $lines_number)."$y,'Depot']);\n";
							if ($current_series == -1) {
								echo "          data.addRow([$x".str_repeat(",null,null", $current_series).",$y,'Depot'".str_repeat(",null,null", $lines_number)."]);\n";	
							} else {
								if ($reached_zero) {
									echo "          data.addRow([$x".str_repeat(",null,null", $current_series + 1).",$y,'Depot'".str_repeat(",null,null", $lines_number - ($current_series + 1))."]);\n";
								} else {
									echo "          data.addRow([$x".str_repeat(",null,null", $current_series).",$y,'Depot'".str_repeat(",null,null", $lines_number - $current_series)."]);\n";
								}
							}
							$reached_zero = true;
							echo "          infos.addRow([$twOpen, $twClose, $service, $demand]);\n";
						}
						else {
							if ($reached_zero) {
								$current_series++;
								$reached_zero = false;
							}
							echo "          data.addRow([$x".str_repeat(",null,null", $current_series).",$y,'Customer:$id'".str_repeat(",null,null", $lines_number - $current_series)."]);\n";
							echo "          infos.addRow([$twOpen, $twClose, $service, $demand]);\n";
						}
					}
					echo $depot_point;
					fclose($mySolutionFile);
					fclose($myInstanceFile);
				} else {
					$name = trim( fgets($myInstanceFile) );
					for ($i = 1; $i < 4; $i++) fgets($myInstanceFile);
					$linearray = explode(" ", preg_replace('/\s+/', ' ',fgets($myInstanceFile)));
					$n = $linearray[1]; $q = $linearray[2];
					for ($i = 0; $i < 4; $i++) fgets($myInstanceFile);
					$max_x = 0; $max_y = 0;
					while(!feof($myInstanceFile)) {
						$linearray = explode(" ", preg_replace('/\s+/', ' ', fgets($myInstanceFile)));
						$id = $linearray[1];
						$x = $linearray[2]; $max_x = max($x, $max_x);
						$y = $linearray[3]; $max_y = max($y, $max_y);
						$demand = $linearray[4];   $twOpen = $linearray[5];
						$twClose = $linearray[6];  $service = $linearray[7];
						if($id == 0 && $twClose == 0) break;
						if($id == 0) {
							$depot_values = array($x, $y, $twOpen, $twClose, $service, $demand);
							echo "          data.addRow([$x,null,null,$y,'Depot']);\n";
							$back_to_depot = "          data.addRow([$x,$y,'Depot',null,null]);\n";
							echo $back_to_depot;
							echo "          infos.addRow([$twOpen, $twClose, $service, $demand]);\n";
							echo "          infos.addRow([$twOpen, $twClose, $service, $demand]);\n";
						}
						else {
							echo "          data.addRow([$x,$y,'Customer:$id',null,null]);\n";
							echo "          infos.addRow([$twOpen, $twClose, $service, $demand]);\n";
						}
					}
					echo $back_to_depot;
					fclose($myInstanceFile);
				}
			?>
			var options = {
				//title: 'Instance <?php echo $name; ?> (n=<?php echo $n; ?>, Q=<?php echo $q; ?>)',
				width: 1200,
				height: 1200,
				vAxis: {viewWindow: {min: 0, max: <?php echo $max_x + 10; ?>} , baselineColor:'white', textStyle:{color:'white'}},
				hAxis: {viewWindow: {min: 0, max: <?php echo $max_y + 10; ?>} , baselineColor:'white', textStyle:{color:'white'}},
				pointSize: 5,
				series: {
					<?php
						if ($solution) {
							for ($i = 0; $i < $lines_number; $i++) {
								echo $i.": { lineWidth: 1 },";
							}
							echo $lines_number.": { pointSize: 10, lineWidth: 1 }";
						} else {
							echo "0: { lineWidth: 0 }, 1: { pointSize: 10, lineWidth: 0 }";
						}
					?>
				}
			};
			var series_infos = { <?php
				foreach ($series_infos as $key => $value_ext) {
					echo $key.": [";
					foreach ($value_ext as $value_in) {
						echo $value_in.",";
					}
					echo "],\n";
				}
			?>};
			console.log(series_infos);
			var view = new google.visualization.DataView(data);
			var chart = new google.visualization.ScatterChart (document.getElementById('chart_instance'));
			google.visualization.events.addListener(chart, 'select', function () {
				var selection = chart.getSelection();
				var current_series = 0;
				for (var i = 0; i < selection.length; i++) {
					try {
						if(selection[i].row != null){
							// In case there is no solution, current series is always 0
							if (1 == <?php if ($solution) { echo 1; } else { echo 0; }?>) {
								for (var j = 0; j < <?php echo $lines_number*2+3; ?>; j++) {
									if (j%2 == 0 && data.getValue(selection[i].row, j) != null) {
										current_series = j/2 - 1;
									}
								}
							}
							else {
								current_series = 0;
							}
							if (data.getValue(selection[i].row, 2 + 2*current_series) == null) {
								$( "#dialog" ).dialog( "option", "title", data.getValue(selection[i].row, 4 + 2*current_series));
								var y = data.getValue(selection[i].row, 3 + 2*current_series);
							} else {
								$( "#dialog" ).dialog( "option", "title", data.getValue(selection[i].row, 2 + 2*current_series));
								var y = data.getValue(selection[i].row, 1 + 2*current_series);
							}
							if (data.getValue(selection[i].row, 4 + 2*current_series) == 'Depot' || data.getValue(selection[i].row, 2 + 2*current_series) == 'Depot') {
								throw "Depot";
							}
							$( "#dialog" ).html(
								"</p><p>x = " + data.getValue(selection[i].row, 0) + " y = " + y + "</p>"
								+ "<p>Time window  = ["+ infos.getValue(selection[i].row, 0) + ", " + infos.getValue(selection[i].row, 1) + "]</p>"
								+ "<p>Service time = " + infos.getValue(selection[i].row, 2)
								+ "<p>Demand       = " + infos.getValue(selection[i].row, 3) );
							$( "#dialog" ).dialog( "open" );
						} else {
							if (1 == <?php if ($solution) { echo 1; } else { echo 0; }?>) {
								var s = (view.getTableColumnIndex(chart.getSelection()[0]['column']) - 1)/2;
								$( "#dialog" ).dialog( "option", "title", "Series number " + s);
								$( "#dialog" ).html(
									"</p><p>Info n°1 : " + series_infos[s][0] + "</p>"
									+ "<p>Info n°2 : " + series_infos[s][1] + "</p>"
									+ "<p>Info n°3 : " + series_infos[s][2] + "</p>");
								$( "#dialog" ).dialog( "open" );
							}
						}
					} catch(err) {
						// This is a catch instead of a condition because Depot is sometimes out of bounds
						$( "#dialog" ).dialog( "option", "title", "Depot");
						$( "#dialog" ).html(
							"</p><p>x = " + <?php echo $depot_values[0]; ?> + " y = " + <?php echo $depot_values[1]; ?> + "</p>"
							+ "<p>Time window  = [<?php echo $depot_values[2].','.$depot_values[3];?>]</p>"
							+ "<p>Service time = " + <?php echo $depot_values[4]; ?>
							+ "<p>Demand       = " + <?php echo $depot_values[5]; ?>);
						$( "#dialog" ).dialog( "open" );
					}
				}
			});
			chart.draw(data, options);
		};
		</script>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap-theme.min.css">
		<link rel="stylesheet" href="//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css">
		<script src="//code.jquery.com/jquery-1.10.2.js"></script>
		<script src="//code.jquery.com/ui/1.11.2/jquery-ui.js"></script>
		<link href='http://fonts.googleapis.com/css?family=Oswald' rel='stylesheet' type='text/css' />
		<script>
		$(function() {
			$( "#dialog" ).dialog({
				autoOpen: false,
			});
		});
		</script>
		<style>
		</style>
	</head>
	<body class="contentpane">
		<div class="container">
			<div class="page-header">
				<h1>Instance <?php echo $name; ?></h1>
			</div>
		<div class="form-group">
		<?php
			$path = "instances.txt";
			$file = fopen($path, 'r');
			$data = fread($file, filesize($path));
			fclose($file);
			$lines = explode(PHP_EOL,$data);
			echo '<form method="GET"><select class="form-control" data-live-search="true" name="instance" onchange="this.form.submit()">';
			foreach($lines as $line) echo '<option value="'. urlencode($line).'"  '. ((strcmp($line, $instance)==0)?"selected":"").'>'.$line.'</option>';
			echo '</select></form>';
		?>
		</div>
			<p> Number of customers : <?php echo $n; ?> </p>
			<p> Vehicule capacity : <?php echo $q; ?> </p>
			<div id="dialog" title="Informations"></div>
			<div id="chart_instance" ></div>
		</div>
	</body>
</html>
