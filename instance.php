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
			data.addColumn('number', 'customers');
			data.addColumn({type:'string', role:'tooltip'}); // annotation role col.
			data.addColumn('number', 'depot');
			data.addColumn({type:'string', role:'tooltip'}); // annotation role col.

			var infos = new google.visualization.DataTable();
			infos.addColumn('number', 'twOpen');
			infos.addColumn('number', 'twClose');
			infos.addColumn('number', 'service');
			infos.addColumn('number', 'demand');

			<?php
				$instance = "c101";
				if(isset($_GET["instance"]) && strcmp(dirname($_GET["instance"]) ,".")==0) $instance = $_GET["instance"];
				$myInstanceFile = fopen("data/Solomon/$instance.txt", "r") or die("Unable to open file!");
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
						echo "          data.addRow([$x,null,null,$y,'Depot']);\n";
						$back_to_depot = "          data.addRow([$x,$y,'Depot',null,null]);\n";
						echo $back_to_depot;
					}
					else {
						echo "          data.addRow([$x,$y,'Customer:$id',null,null]);\n";
						echo "          infos.addRow([$twOpen, $twClose, $service, $demand]);\n";
					}
				}
				echo $back_to_depot;
				fclose($myInstanceFile);
			?>
			var options = {
				//title: 'Instance <?php echo $name; ?> (n=<?php echo $n; ?>, Q=<?php echo $q; ?>)',
				width: 1200,
				height: 1200,
				vAxis: {viewWindow: {min: 0, max: <?php echo $max_x; ?>} , baselineColor:'white', textStyle:{color:'white'}},
				hAxis: {viewWindow: {min: 0, max: <?php echo $max_y; ?>} , baselineColor:'white', textStyle:{color:'white'}},
				pointSize: 5,
				series: {
					0: {
						lineWidth: 1
					},
					1: {
						pointSize: 10,
						lineWidth: 1
					}
				}
			};

			var chart = new google.visualization.ScatterChart (document.getElementById('chart_instance'));
			google.visualization.events.addListener(chart, 'select', function () {
				var selection = chart.getSelection();
				for (var i = 0; i < selection.length; i++) {
					if(selection[i].row != null){
						$( "#dialog" ).dialog( "option", "title", "Client " + selection[i].row);
						$( "#dialog" ).html(
							"</p><p>x = " + data.getValue(selection[i].row, 0) + " y = " + data.getValue(selection[i].row, 3) + "</p>"
							+ "<p>Time window  = ["+ infos.getValue(selection[i].row, 0) + ", " + infos.getValue(selection[i].row, 1) + "]</p>"
							+ "<p>Service time = " + infos.getValue(selection[i].row, 2)
							+ "<p>Demand       = " + infos.getValue(selection[i].row, 3) );
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
