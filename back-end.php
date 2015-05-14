 <?php 
 header("Access-Control-Allow-Origin: *");
 error_reporting(0);

    $Error="";
 	if ($_FILES["file_solomon"]["error"][0] > 0){
                    $Error.= ("Error: " . $_FILES["file_solomon"]["error"][0] . "<br />");
    }
    else{
                    $myInstanceFile = fopen($_FILES["file_solomon"]["tmp_name"],"r");
    }
    $mySolutionFile = fopen($_FILES["file_solution"]["tmp_name"],"r");
    if ($mySolutionFile) {
                    $solution = true;
    } else {
  					$solution = false;
  	}
    $com_add_1="";
    $com_add_2="";
    $com_options_series="";
    $com_series_infos="";
    
  	if($solution){
                    // Let go of first line in solution
                    fgets($mySolutionFile);
                    $solution_points = '';
                    $series_infos = array();
                    $lines_number = explode(" ", fgets($mySolutionFile))[1];
                    	// echo $_FILES["file_solution"]["tmp_name"];
                    for($i = 0; $i < $lines_number; $i++) {
                        $line = trim(fgets($mySolutionFile));
                        $series_infos[$i] = explode(" ", trim(explode("|", $line)[0]));
                        $solution_points .=  ' '.trim(explode("|", $line)[1]).' 0';

                        $com_add_1.="data.addColumn('number', 'customers series ".$i."');\n";
                        $com_add_1.="data.addColumn({type:'string', role:'tooltip'});\n";
                    }
                    $solution_points = explode(' ', trim($solution_points));
    } else {
                    $com_add_1.="data.addColumn('number', 'customers');\n";
                    $com_add_1.="data.addColumn({type:'string', role:'tooltip'});\n";
    }

    if ($solution) {
                    $complete_instance = substr(fread($myInstanceFile, filesize($_FILES["file_solomon"]["tmp_name"])), 90);
                    fclose($myInstanceFile);
                    $myInstanceFile = fopen($_FILES["file_solomon"]["tmp_name"],"r");
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
                                $com_add_2.="data.addRow([$x".str_repeat(",null,null", $current_series).",$y,'Depot'".str_repeat(",null,null", $lines_number)."]);\n";
                            } else {
                                if ($reached_zero) {
                                    $com_add_2.="data.addRow([$x".str_repeat(",null,null", $current_series + 1).",$y,'Depot'".str_repeat(",null,null", $lines_number - ($current_series + 1))."]);\n";
                                } else {
                                    $com_add_2.="data.addRow([$x".str_repeat(",null,null", $current_series).",$y,'Depot'".str_repeat(",null,null", $lines_number - $current_series)."]);\n";
                                }
                            }
                            $reached_zero = true;
                            $com_add_2.="infos.addRow([$twOpen, $twClose, $service, $demand]);\n";
                        }
                        else {
                            if ($reached_zero) {
                                $current_series++;
                                $reached_zero = false;
                            }
                            $com_add_2.="data.addRow([$x".str_repeat(",null,null", $current_series).",$y,'Customer:$id'".str_repeat(",null,null", $lines_number - $current_series)."]);\n";
                            $com_add_2.="infos.addRow([$twOpen, $twClose, $service, $demand]);\n";
                        }
                    }
                    $com_add_2.= $depot_point;
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
                            $com_add_2.="data.addRow([$x,null,null,$y,'Depot']);\n";
                            $back_to_depot = "          data.addRow([$x,$y,'Depot',null,null]);\n";
                            $com_add_2.= $back_to_depot;
                            $com_add_2.="infos.addRow([$twOpen, $twClose, $service, $demand]);\n";
                            $com_add_2.="infos.addRow([$twOpen, $twClose, $service, $demand]);\n";
                        }
                        else {
                            $com_add_2.="data.addRow([$x,$y,'Customer:$id',null,null]);\n";
                            $com_add_2.="infos.addRow([$twOpen, $twClose, $service, $demand]);\n";
                        }
                    }
                    $com_add_2.= $back_to_depot;
                    fclose($myInstanceFile);
    }

    
    // Get value of series for options
    if ($solution) {
                        for ($i = 0; $i < $lines_number; $i++) {
                            //$com_options_series.= ($i.": { lineWidth: 1 },");
                            $com_options_series[$i] = array("lineWidth" => 1);
                        }
                         //$com_options_series.= ($lines_number.": { pointSize: 10, lineWidth: 1 }");
                        $com_options_series[$lines_number] = array("pointSize" => 10, "lineWidth" => 1);
    } else {
                         //$com_options_series.= "0: { lineWidth: 0 }, 1: { pointSize: 10, lineWidth: 0 }";
                        $com_options_series[0] = array("lineWidth" => 0);
                        $com_options_series[1] = array("pointSize" => 10, "lineWidth" => 0);
    }
    // Get value of series_infos
   foreach ($series_infos as $key => $value_ext) {
            $com_series_infos.= ($key.": [");
            foreach ($value_ext as $value_in) {
                $com_series_infos.= ($value_in.",");
            }
            $com_series_infos.= ("],\n");
    }

    $data_boat = array(
            "Error" => $Error,
            "com_add_1" => $com_add_1,
            "com_add_2" => $com_add_2,
            "com_options_series" => $com_options_series,
            "com_series_infos" => $com_series_infos,
            "solution" => $solution,
            "name" => $name, 
            "lines_number" => $lines_number,
            "depot_values" => $depot_values,
            "n" => $n,
            "q" => $q,
            "max_x" => $max_x,
            "max_y" => $max_y
        );
    echo json_encode($data_boat);
 ?>