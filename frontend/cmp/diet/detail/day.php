<?php defined("_JEXEC") or die();

/*
TODO
split each column for components ?
or use horizontal bar with TOTAL and EACH for category
*/

F_Library::importExternal("highcharts");
use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;

$day = H_DataTimespan::getDayFromTime(F_Input::getInteger("day", time()));

$previousUrl = H_UiRouter::getFoodDetailForDay($day - F_UtilsTime::A_DAY);

$nextDay = $day + F_UtilsTime::A_DAY;
if ($nextDay <= time()) {
  $nextUrl = H_UiRouter::getFoodDetailForDay($day + F_UtilsTime::A_DAY);  
}
else {
  $nextUrl = null;
}

echo "<h3><a href='$previousUrl'><</a> &nbsp; " .
  date("j F", $day);

if ($nextUrl) {
  echo " &nbsp; <a href='$nextUrl'>></a>";
}

echo "</h3>";
echo "<b>details</b>";

$user = JFactory::getUser();

/* Loading data fields:
$data->user
$data->userInfo
$data->mergedBodyRecord
$data->caloriesRecordsFit
$data->foodRecords
$data->activeRegime
$data->foodInfos
$data->energyGoal
$data->bmr
$data->foodComponents
$data->targetComponents
$data->dailyCaloriesOut
$data->completed
*/
$data = H_DataAggregator::getFoodData($user->id, $day);
if (!$data->completed) {
  F_Log::showError("not enough data :(");
  return;
}

$data->foodRecordsComponents = H_DataAggregator::aggregateFoodRecords($data->foodRecords, $data->foodInfos, $data->activeRegime);

$graphs = array();

// Component bar

// array($c, $renderTo, $callId)
$graphs[] = buildComponentsBar($data);

foreach ($data->foodComponents as $foodComponent) {
  if (!isset($data->activeRegime->data->components[$foodComponent->id])) continue;
  if (!$data->activeRegime->data->components[$foodComponent->id]->monitor) continue;
  $graphs[] = buildComponentPieForFoods($data, $foodComponent->id);
}

foreach ($graphs as $graph) {
  printGraphScripts($graph[0]);
}

foreach ($graphs as $graph) {
  ?> 
  <div id='<?php echo $graph[1]; ?>'></div> 
	<br/>
	<br/>
  <?php
}
?>
<script>
<?php  
foreach ($graphs as $graph) {
  echo $graph[0]->render("graph_".$graph[2]);
}
?>
</script>

<?php

function printGraphScripts($graph) {
	static $done = false;
	if ($done) return;
	$done = true;
	$graph->printScripts();
}

function buildComponentPieForFoods($d, $componentId) {
  // array($c, $renderTo, $callId)
  $cArray = H_UiGraphs::getBasicPie();
  $c = $cArray[0];
  
  $foodComponent = $d->foodComponents[$componentId];
  $propertyName = $foodComponent->info_property;
  
  $c->title->text = $foodComponent->display_name . " intake food share";
  $c->tooltip->formatter = new HighchartJsExpr(
				"function() { return '<b>'+ this.point.name +'</b><br/>'+ this.y +' %<br/>' + this.point.custom; }");
  
  $graphData = array(
    'type' => "pie",
    'name' => $foodComponent->display_name . " share",
    'data' => array()
  );
  
  $total = $d->foodRecordsComponents[$componentId];
  $target = $d->targetComponents[$componentId];
  
  if ($total < $target) {
    $missing = $target - $total;
    $divideBy = $target;
  }
  else {
    $missing = 0;
    $divideBy = $total;
  }
  
  // calculate share per food
  foreach ($d->foodRecords as $foodRecord) {
    if ($foodRecord->foodid < 1) continue;
    if ($foodRecord->amount <= 0.0) continue;
    
    $foodInfo = $d->foodInfos[$foodRecord->foodid];
    $contribute = ((float)$foodRecord->amount / 100.0) * $foodInfo->$propertyName;
    
    $graphDataPoint = array(
      "name" => $foodInfo->getDisplayName(), 
      "y" => round(100.0 * $contribute / $divideBy, 0),
      "custom" => round($contribute, 0) . " " . $foodComponent->unit
    );
    array_push($graphData["data"], $graphDataPoint);
  }
  
  if ($missing) {
    $graphDataPoint = array(
      "name" => "MISSING", 
      "y" => round(100.0 * $missing / $divideBy, 0),
      "custom" => round($missing, 0) . " " . $foodComponent->unit,
      'sliced' => true,
      'selected' => true,
      'color' => H_UiGraphics::PIE_LEFT_COLOR
    );
    array_push($graphData["data"], $graphDataPoint);
  }
  
  $c->series[] = $graphData;
  $cArray[0] = $c;
	return $cArray;
}

function buildComponentsBar($d) {
  // array($c, $renderTo, $callId)
  $cArray = H_UiGraphs::getBasicBar();
  $c = $cArray[0];
	
	$dType = "bar";
	
	$c->plotOptions->$dType->dataLabels->enabled = true;
	$c->plotOptions->$dType->dataLabels->formatter = new HighchartJsExpr("
		function() {
			return '' + this.y +' %';
		}");
	
	$c->title->text = "Intake per component";
	$c->subtitle->text = "";
	$c->yAxis->min = 0;
  // $c->yAxis->max = 175;
	$c->yAxis->title->text = "% of target";
	$c->tooltip->formatter = new HighchartJsExpr("
		function() {
			if (this.point.istarget) {
				return '' + this.x +' Target: '+ this.point.target_value;
			}
			else {
				return '' + this.x +': '+ this.y +' %<br/>' + this.point.custom_value;
			}
		}");
	
	$serie0 = array(
		'name' => "Intake per component",
		'data' => array()
	);
	$serie1 = array(
		"type" => "line",
		'name' => "Target",
		'data' => array()
	);
	
  foreach ($d->targetComponents as $tcId => $target_component_value) {
    $foodComponent = $d->foodComponents[$tcId];
    
		$c->xAxis->categories[] = $foodComponent->display_name;
    
		$dataPointValue = 100.0 * 
      $d->foodRecordsComponents[$tcId] / 
      $target_component_value;
		
		array_push($serie0["data"], array(
			"y" => round($dataPointValue, 0),
			"color" => H_UiGraphics::getColumnColorFromPercentage($dataPointValue, 100.0),
			"custom_value" => round($d->foodRecordsComponents[$tcId], 0) . " " . $foodComponent->unit,
			"target_value" => round($target_component_value, 0) . " " . $foodComponent->unit
		));
		
		// push target point
		array_push($serie1["data"], array(
			"other_value" => round($dataPointValue, 0),
			"y" => 100.0,
			"istarget" => true,
			"target_value" => round($target_component_value, 0) . " " . $foodComponent->unit
		));
	}
	
	$c->series[] = $serie0;
	$c->series[] = $serie1;
	
  $cArray[0] = $c;
	return $cArray;
}