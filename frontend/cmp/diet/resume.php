<?php defined("_JEXEC") or die();

F_Library::importExternal("highcharts");
use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;

// custom interval setting
$lastNumber = F_Input::getInteger("last", null);
if ($lastNumber) {
	echo "<strong>Average for the last $lastNumber days</strong><br/><br/>";
	showStats($lastNumber, true);
}

$dayDetailUrl = H_UiRouter::getFoodDetailForDay(time());
echo "<strong><a href='$dayDetailUrl' target='_blank'>Today</a></strong><br/><br/>";
showStats(1, false, true);

echo "<strong>Last 3 days (incl. today)</strong><br/><br/>";
showStats(3, false);

echo "<strong>Previous week</strong><br/><br/>";
showStats(7, false);

echo "<strong>Previous two weeks</strong><br/><br/>";
showStats(15, true);

echo "<strong>Last month</strong><br/><br/>";
showStats(31, true, true);

function printGraphScripts($graph) {
	static $done = false;
	if ($done) return;
	$done = true;
	$graph->printScripts();
}

function showStats($lastNumber = 15, $skipToday = true, $showDetails = false) {
	$callId = F_UtilsRandom::generateRandomAlphaNum(20);
	
	// Compute starting point
	if ($skipToday) {
		$todayStr = date("Y-m-d", time()-F_UtilsTime::A_DAY) . " 00:00:00";
	}
	else {
		$todayStr = date("Y-m-d", time()) . " 00:00:00";
	}
	$todayInt = strtotime($todayStr);

	// Get current regime and user stats
	$user = JFactory::getUser();
	$regime = H_FoodRegime::getUserActiveOrDefault($user->id);
	$userInfo = H_UserInfo::loadByUser($user->id);

	$target = new JObject();
	$target->energy = H_DataProviderFood::getDailyCaloriesOut($user->id) *
		$regime->getComponent(H_FoodComponent::ID_ENERGY)->goal_percentage;

	$target->components = array();

	// ID keyed, unordered list
	$foodComponents = H_FoodComponent::loadUnorderedListCached();

	// Compute target specifications
	foreach ($regime->data->components as $componentId => $componentSpecification) {
		if (!$componentSpecification->monitor) continue;

		$foodInfo = $foodComponents[$componentId];

		if ($componentId == H_FoodComponent::ID_ENERGY) {
			$target_amount = $target->energy;
		}
		else {
			$target_amount = 
				$target->energy *
				$componentSpecification->goal_percentage *
				($userInfo->getSex() == H_UserInfo::SEX_MALE ? $foodInfo->gda_m : $foodInfo->gda_f) *
				(0.01);
		}

		$target_component = array(
			$target_amount, 
			$componentSpecification, 
			$foodInfo
		);

		$target->components[$componentId] = $target_component;
	}

	// Prepare report structure
	$report = array();
	$stats = new JObject();
	$stats->total->energy = 0.0;
	$stats->total->count = 0;
	$stats->total->components = array();

	$foodInfoCache = array();

	// Load day per day
	for ($i = 0; $i < $lastNumber; $i ++) {
		$dayInt = $todayInt - $i * F_UtilsTime::A_DAY;

		$where = "userid=".$user->id." AND time>=".$dayInt." AND time<".($dayInt + F_UtilsTime::A_DAY);
		$foodRecords = F_Table::loadClassList(H_FoodRecord::CLASS_NAME, $where);

		if (empty($foodRecords)) continue;

		$dayReport = new JObject();
		$dayReport->energy = 0.0;
		$dayReport->foods = array();
		$dayReport->components = array();

		$dayReport->chart = new Highchart();
		$dayReport->chart->chart->renderTo = "call$callId-container-".$dayInt;
		$dayReport->chart->chart->plotBackgroundColor = null;
		$dayReport->chart->chart->plotBorderWidth = null;
		$dayReport->chart->chart->plotShadow = false;
		$dayReport->chart->title->text = ($lastNumber <= 1) ? "today" : "";
		$dayReport->chart->tooltip->formatter = new HighchartJsExpr(
				"function() {
				return '<b>'+ this.point.name +'</b><br/>'+ this.y +' %<br/>' + this.point.calories + ' cal'; }");
		$dayReport->chart->plotOptions->pie->allowPointSelect = 1;
		$dayReport->chart->plotOptions->pie->cursor = "pointer";
		$dayReport->chart->plotOptions->pie->dataLabels->enabled = false;
		$dayReport->chart->plotOptions->pie->showInLegend = 0;
		$graphData = array(
			'type' => "pie",
			'name' => "Food share",
			'data' => array()
		);

		foreach ($foodRecords as $foodRecord) {
			if (!$foodRecord->foodid) continue;

			if (isset($foodInfoCache[$foodRecord->foodid])) {
				$foodInfo = $foodInfoCache[$foodRecord->foodid];
			}
			else {
				$foodInfo = H_FoodInfo::load($foodRecord->foodid);
				$foodInfoCache[$foodRecord->foodid] = $foodInfo;
			}

			if (!$foodInfo) continue;

			/*
			$target_component_array = array(
				$target_amount, 
				$componentSpecification, 
				$foodInfo
			);
			*/
			foreach ($target->components as $target_component_id => $target_component_array) {
				if (!isset($dayReport->components[$target_component_id])) $dayReport->components[$target_component_id] = 0.0;
				if (!isset($stats->total->components[$target_component_id])) $stats->total->components[$target_component_id] = 0.0;
				$property_name = $target_component_array[2]->info_property;
				$toAdd = $foodRecord->amount * $foodInfo->$property_name / 100.0;
				$dayReport->components[$target_component_id] += $toAdd;
				$stats->total->components[$target_component_id] += $toAdd;
			}

			$foodEnergy = $foodRecord->amount * $foodInfo->energy / 100.0;
			$dayReport->energy += $foodEnergy;
			$dayReport->foods[] = array($foodRecord, $foodInfo, $foodEnergy);
		}

		$pie_divide_by = 0;
		
		if ($dayReport->energy < $target->energy) {
			$energy_left = $target->energy - $dayReport->energy;
			$graphDataPoint = array(
				"name" => "ENERGY LEFT", 
				"y" => round(100.0 * $energy_left / $target->energy, 0),
				"calories" => round($energy_left),
				'sliced' => true,
				'selected' => true,
    		'color' => H_UiGraphics::PIE_LEFT_COLOR
			);
			array_push($graphData["data"], $graphDataPoint);
			
			$pie_divide_by = $target->energy;
		}
		else {
			// no energy left
			$pie_divide_by = $dayReport->energy;
		}
		
		foreach ($dayReport->foods as $reportFood) {
			$graphDataPoint = array(
				"name" => $reportFood[1]->getDisplayName(), 
				"y" => round(100.0 * $reportFood[2] / $pie_divide_by, 0),
				"calories" => $reportFood[2]
			);
			array_push($graphData["data"], $graphDataPoint);
		}
		
		$dayReport->chart->series[] = $graphData;
		$stats->total->energy += $dayReport->energy;
		$stats->total->count ++;

		$report[$dayInt] = $dayReport;
	}
	
	if (!$stats->total->count) {
		echo "no data for selected span<br/><br/>";
		return;
	}

	foreach ($target->components as $target_component_id => $target_component_array) {
		$stats->total->components[$target_component_id] /= $stats->total->count;
	}

	foreach ($target->components as $target_component_id => $target_component_array) {
		echo $target_component_array[2]->display_name . " " .
			round($stats->total->components[$target_component_id], 0) . " " .
			$target_component_array[2]->unit . " / " . 
			round($target_component_array[0]) . " " . $target_component_array[2]->unit .
			"<br/>";
	}
	echo "<br/>";
	
  $chartAvgColumn = H_UiGraphs::getBasicBar()[0];
	
	$chartAvgColumn->chart->renderTo = "call$callId-container-average-column";
	$chartAvgColumn->title->text = ($lastNumber > 1) ? "Average intake for last " . $lastNumber . " days" : "today";
	$chartAvgColumn->subtitle->text = "";
	$chartAvgColumn->yAxis->min = 0;
	$chartAvgColumn->yAxis->max = 125;
	$chartAvgColumn->yAxis->title->text = "% of target";

	$chartAvgColumn->plotOptions->bar->dataLabels->enabled = true;
	$chartAvgColumn->plotOptions->bar->dataLabels->formatter = new HighchartJsExpr("
		function() {
			return '' + this.y +' %';
		}");
	
	$chartAvgColumn->tooltip->formatter = new HighchartJsExpr("
		function() {
			if (this.point.istarget) {
				return '' + this.x +' Target: '+ this.point.target_value;
			}
			else {
				return '' + this.x +': '+ this.y +' %<br/>' + this.point.custom_value;
			}
		}");
	
	$chartAvgColumn->xAxis->categories = array();
	$chartAvgColumnSerie0 = array(
			'name' => "Average",
			'data' => array()
	);
	$chartAvgColumnSerie1 = array(
		"type" => "line",
		'name' => "Target",
		'data' => array()
	);
	
	foreach ($target->components as $target_component_id => $target_component_array) {
		$chartAvgColumn->xAxis->categories[] = $target_component_array[2]->display_name;
		$dataPointValue = $stats->total->components[$target_component_id] / $target_component_array[0];
		$dataPointValue = round(100.0 * $dataPointValue, 0);
		
		array_push($chartAvgColumnSerie0["data"], array(
			"y" => $dataPointValue,
			"color" => H_UiGraphics::getColumnColorFromPercentage($dataPointValue, 100.0),
			"custom_value" => round($stats->total->components[$target_component_id], 0) . " " . $target_component_array[2]->unit,
			"target_value" => round($target_component_array[0], 0) . " " . $target_component_array[2]->unit
		));
		
		// push target point
		array_push($chartAvgColumnSerie1["data"], array(
			"y" => 100.0,
			"istarget" => true,
			"target_value" => round($target_component_array[0], 0) . " " . $target_component_array[2]->unit
		));
	}
	
	$chartAvgColumn->series[] = $chartAvgColumnSerie0;
	$chartAvgColumn->series[] = $chartAvgColumnSerie1;
	printGraphScripts($dayReport->chart);
	?>
	<div id="call<?php echo $callId; ?>-container-average-column"></div>
	<br/><br/>
	<?php 
	
	if ($showDetails) {
		foreach ($report as $day => $dayReport) {
			if ($lastNumber > 1) {
				$dayDetailUrl = H_UiRouter::getFoodDetailForDay($day);
				echo "<strong><a href='$dayDetailUrl' target='_blank'>".date("Y-m-d", $day)."</a></strong><br/>";
			}

			if ($lastNumber > 1) {
				foreach ($target->components as $target_component_id => $target_component_array) {
					echo $target_component_array[2]->display_name . " " .
						round($dayReport->components[$target_component_id], 0) . " " .
						$target_component_array[2]->unit . " / " . 
						round($target_component_array[0]) . " " . $target_component_array[2]->unit .
						"<br/>";
				}
				echo "<br/>";
			}
			?>
			<div id="call<?php echo $callId; ?>-container-<?php echo $day; ?>"></div>
			<br/>
		<?php } 
	}
  ?>	

	<script type="text/javascript">
		<?php
		echo $chartAvgColumn->render("call$callId"."_chart_avg_column");

		if ($showDetails) {
			foreach ($report as $day => $dayReport) {
				echo $dayReport->chart->render("call$callId"."_chart_" . $day);
			}
		}
		?>
	</script>
<?php
}

