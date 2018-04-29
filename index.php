<?php

require('simple_html_dom.php');
$outdata = array();
$currentconditions = new stdClass();
$forecast24hrs = new stdClass();
$fivedayforecast = new stdClass();
$averages;
$month_averages = array();
$fivedayforecasts = array();

// Website link to scrap
if((isset($_GET['country']))&&(isset($_GET['city'])))
{
	$country=$_GET['country'];
	$city=$_GET['city'];
}
else
{
	echo json_encode($outdata);
	return;
}
	
$website = 'http://www.weatherforecastmap.com/'.$country.'/'.$city;

$headers = @get_headers($website);
if(!$headers || $headers[0] == 'HTTP/1.1 404 Not Found')
{
	echo json_encode($outdata);
	return;
}

$html = file_get_html($website);
$i=0;
foreach($html->find('table') as $table)
{
	if($table->children[0]->tag == 'tr')
	{
		//echo $table->attr['style'];
		if($i==0)
		{
			$i++;
			continue;
		}
		$i++;
		$rows  = $table->find('tr');
		//echo "---".count($rows)."---";
		foreach($rows as $row) 
		{
			$cells = $row->children;
			//echo "%%%".count($cells)."%%%";
			$rowdata = array();
			foreach($cells as $cell) 
			{
				//$images = $cell->find('img');
				//echo "{".count($images)."}";
				$images = $cell->find('img');
				if(count($images)>0)
				{
					if(isset($images[0]->attr['alt']))
					{
						//echo trim(strip_tags($images[0]->attr['alt']))."###";
						$rowdata[] = trim(strip_tags($images[0]->attr['alt']));
					}
				}
				else 
				{
					if($cell->tag == 'img')
					{
						if(isset($cell->attr['alt']))
						{
							//echo trim(strip_tags($images[0]->attr['alt']))."---";
							$rowdata[] = trim(strip_tags($images[0]->attr['alt']));
						}
					}
					else
					{
						//echo trim(strip_tags($cell->innertext))."---";
						$rowdata[] = trim(strip_tags($cell->innertext));
					}
				}
			}	
			AddTableData($i,$rowdata);
		}
	}
}	

function AddTableData($tablen,$rowdata)
{
	global $currentconditions;
	global $fivedayforecast;
	global $averages;
	global $month_averages;
	global $fivedayforecasts;
	$rowdatastr = trim(implode(",",$rowdata));
	
	if(strlen($rowdatastr)>0)
	{
		switch($tablen)
		{
			case 2:
				if(count($rowdata)==2)
				{
					$rowdata[0] = rtrim($rowdata[0],':');
					$currentconditions->$rowdata[0] = $rowdata[1];
				}
				break;
			case 3:
				//if(strlen($rowdata)>8)
				// echo $tablen."--".implode(",",$rowdata)."--<br>";
				//$forecast24hrs
				//echo $tablen."--".implode(",",$rowdata)."--<br>";
				break;
			case 4:
				if(strlen($rowdatastr)>15)
				{
					
					$data = explode(",",$rowdatastr);
					$day = explode(" ",$data[0]);
					$sdata = explode(";",$data[1]);
					$max = explode("&",$sdata[1]);
					$min = explode("&",$sdata[3]);
					
					$fivedayforecast->day = $day[3];
					$fivedayforecast->forecast = $data[0]; 
					$fivedayforecast->max = $max[0];
					$fivedayforecast->min = $min[0];
					$fivedayforecast->unit = $sdata[4];
					$fivedayforecasts[] = $fivedayforecast;
					$fivedayforecast = new stdClass();
				}
				break;
			case 5:
				//echo $rowdatastr."--<br>";
				$data = explode(",",$rowdatastr);
				//var_dump($data);
				if(count($data)==2)
				{
					$averages->$data[0] = $data[1];
				}
				else
				{
					$sdata = explode(" ",$data[0]);
					$month = $sdata[count($sdata)-1];
					if($month != '&nbsp;')
					{
						$averages = new stdClass();	
						$averages->month = $sdata[count($sdata)-1];
						$month_averages[] = $averages;
					}
				}
				break;
		}
		//echo $tablen."--".implode(",",$rowdata)."--<br>";
	}
}
//var_dump($currentconditions);
//var_dump($fivedayforecasts);
//var_dump($month_averages);

$output = new stdClass();
$output->currentconditions = $currentconditions;
$output->fivedayforecast = $fivedayforecasts;
$output->month_averages = $month_averages;

echo json_encode($output);

return;


?>
