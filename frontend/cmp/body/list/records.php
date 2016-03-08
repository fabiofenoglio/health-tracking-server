<?php defined("_JEXEC") or die(); 

const WEIGHT_RECORD_SOURCE_USER = 0;
const WEIGHT_RECORD_SOURCE_FIT = 1;

F_Library::importExternal("highcharts");
use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;

$class = H_BodyRecord::CLASS_NAME;
$user = JFactory::getUser();

$from = F_Input::getInteger("from", 0);
$max_days_to_display = F_Input::getInteger("maxdays", 90);
$mergedRecord = H_BodyRecord::getMerged($user->id);
$avgWeight = $mergedRecord->weight;
$bmi = H_FoodCalculator::computeBMI($mergedRecord);
$now = time();

$filter = "userid=" . $user->id . " AND time>=" . ($now - F_UtilsTime::A_DAY * $max_days_to_display);
if ($from) $filter .= " AND time <=" . $from;

$userBodyRecords = F_Table::loadClassList($class, $filter, "time ASC");
$bodyRecords = array();
foreach ($userBodyRecords as $userBodyRecord) {
	$key = $recordDay = H_DataTimespan::getDayFromTime($userBodyRecord->time);
	if (!isset($bodyRecords[$key])) $bodyRecords[$key] = array();
	array_push($bodyRecords[$key], $userBodyRecord);
}

foreach($bodyRecords as $k => $a) {
	$bodyRecords[$k] = array_reverse($a);
}

if (empty($bodyRecords)) {
	$weightOverTimeGraph = null;
}
else {
	$weightOverTimeGraph = buildWeightOverTimeGraph($bodyRecords);	
}
$bodyRecords = array_reverse($bodyRecords);

$lastDisplayedTime = null;
$displayed = 0;
$break_with_more_to_see = false;

$add_record_url = H_UiRouter::getAddBodyRecordUrl();
$show_params = array("activity_detail" => true);
?>
<h3>
	Your <?php echo H_UiLang::getRandomAdjective(); ?> body records
</h3>
<p>
	<a href="<?php echo $add_record_url; ?>"
		 class="btn btn-success btn-large"
		 style="float:right;margin-bottom:30px;"
		 >
		<span class='icon-plus' ></span> I measured my things!
	</a>
</p>
<p>
	<?php
	if ($bmi) {
		$idealWeight = (H_FoodCalculator::IDEAL_BMI / $bmi) * $mergedRecord->weight;
		if (abs($bmi - H_FoodCalculator::IDEAL_BMI) > H_FoodCalculator::IDEAL_BMI_VAR) {
			// not ok
			echo "<font suspended-color='#885544'>BMI: ".round($bmi, 1)." - ideal weight: ".round($idealWeight, 0)." kg</font><br/>";
			if ($bmi > H_FoodCalculator::IDEAL_BMI) {
				$idealPlusVarWeight = ((H_FoodCalculator::IDEAL_BMI + H_FoodCalculator::IDEAL_BMI_VAR) / $bmi) * $mergedRecord->weight;
				echo "<small>You should lose " . round($mergedRecord->weight - $idealPlusVarWeight, 0) . " to " . 
					 round($mergedRecord->weight - $idealWeight, 0) . " kg</small>";	
			}
			else {
				$idealPlusVarWeight = ((H_FoodCalculator::IDEAL_BMI - H_FoodCalculator::IDEAL_BMI_VAR) / $bmi) * $mergedRecord->weight;
				echo "<small>You should gain " . round($idealPlusVarWeight - $mergedRecord->weight, 0) . " to " . 
					 round($idealWeight - $mergedRecord->weight, 0) . " kg</small>";	
			}
			
		}
		else {
			// ok
			echo "<font color='#33AA44'>BMI: ".round($bmi, 1)." - ideal weight: ".round($idealWeight, 0)." kg</font>";
		}
	}
	?>
</p>

<?php
if ($weightOverTimeGraph) {
	$weightOverTimeGraph->printScripts();
}
?>
<div id='container-weight-over-time-graph' ></div><br/><br/>

<?php
if ($avgWeight) { ?>
	<p>
		Your effective weight for data computing is <b><?php 
			echo round($avgWeight, 2); 
		?> kg</b>
		<br/>
		<small>calculated by linear averaging up to <?php 
				echo H_BodyRecord::AVERAGE_WEIGHT_PERIOD_DAYS; 
			?> days @ <?php 
				echo H_BodyRecord::AVERAGE_WEIGHT_EMIDW; 
			?> days half-life decay weighting, <?php
				echo H_BodyRecord::AVERAGE_WEIGHT_CLUSTERING;
			?> hours clustering
		</small>
	</p>
<?php
}
?>

<table class='table table-noborders'>
<?php echo F_Content::call_element_list_header($class, $show_params); ?>
<?php
$previous = null;
$show_params["previous"] = null;

foreach ($bodyRecords as $bodyRecordArrayArray) { 
	foreach ($bodyRecordArrayArray as $bodyRecord) {
		if ($max_days_to_display > 0 && $displayed >= $max_days_to_display) {
			$break_with_more_to_see = true;
			break;
		}
		
		echo F_Content::call_element_list_display($bodyRecord, $class, $show_params);
		$show_params["previous"] = $bodyRecord;
		$show_params["activity_detail"] = false;
		
		$previous = $bodyRecord;
		$lastDisplayedTime = $bodyRecord->time;
		$displayed ++;	
	}
}
?>	
</table>

<?php
if ($from) {
	$url = JUri::getInstance(); $url->setVar("from", 0);
	echo "<a href='$url' class='btn'>first page</a> ";
}

if ($break_with_more_to_see) {
	$url = JUri::getInstance(); $url->setVar("from", $lastDisplayedTime);
	echo "<a href='$url' class='btn'>next $max_days_to_display</a> ";
}

function getCaloriesBalance($recordDay) {
	static $dayCaloriesBalances = array();
	$user = JFactory::getUser();
	
	if (isset($dayCaloriesBalances[$recordDay])) {
		return $dayCaloriesBalances[$recordDay];
	}
	
	$fitData = H_FitData::query("type=".H_FitData::TYPE_CALORIES." AND userid=".$user->id." AND date>=$recordDay AND date<".($recordDay+F_UtilsTime::A_DAY));
	$foodRecords = H_FoodRecord::query("userid=".$user->id." AND time>=$recordDay AND time<".($recordDay+F_UtilsTime::A_DAY));
	$activityRecords = H_ActivityRecord::query("userid=".$user->id." AND time>=$recordDay AND time<".($recordDay+F_UtilsTime::A_DAY));
	
	// combine input
	$totalIn = H_DataAggregator::aggregateFoodRecordsEnergy($foodRecords);
	
	// combine output
	$userInfo = H_UserInfo::loadByUser($user->id);
	if ($bodyRecord->height < 1.0 || $bodyRecord->weight < 1.0 || $bodyRecord->mul < 0.1) {
		$aggr = H_BodyRecord::getMerged($user->id);
		if ($bodyRecord->height < 1.0) $bodyRecord->height = $aggr->height;
		if ($bodyRecord->weight < 1.0) $bodyRecord->weight = $aggr->weight;
		if ($bodyRecord->mul < 0.1) $bodyRecord->mul = $aggr->mul;
	}

	if (!$userInfo || $bodyRecord->height < 1.0 || $bodyRecord->weight < 1.0 || $bodyRecord->mul < 0.1) {
		// can't compute
		$bmr = 0.0;
	}
	else {
		$bmr = H_FoodCalculator::computeBMR($userInfo, $bodyRecord);
	}

	if ($bmr > 0.1) {
		if (empty($fitData)) {
			$totalOut = $bmr * $bodyRecord->mul;
		}
		else {
			$totalOut = 0.0;
			foreach ($fitData as $fitPoint) {
				$totalOut += $fitPoint->value;
			}
			$totalOut += ($bmr * ($bodyRecord->mul - 1.0)) ;
		}	
	}
	else {
		$totalOut = 0.0;
	}
	
	if (!empty($activityRecords)) {
		foreach ($activityRecords as $activityRecord) {
			$totalOut += (float)$activityRecord->calories;
		}	
	}
	
	// now combine shit
	$record = array("in" =>$totalIn, "out" => $totalOut);
	$dayCaloriesBalances[$recordDay] = $record;
	unset($fitData);
	unset($foodRecords);
	return $record;
}

function buildWeightOverTimeGraph($bodyRecords) {
	$chart = new Highchart();
	$chart->chart = array(
			'renderTo' => 'container-weight-over-time-graph',
			'type' => 'spline',
			'marginRight' => 130,
			'marginBottom' => 25
	);
	$chart->title = array(
			'text' => 'Weight over time',
			'x' => - 20
	);
	$chart->subtitle = array(
			'text' => 'from all sources',
			'x' => - 20
	);
	$chart->xAxis->categories = array();
	$chart->yAxis = array(
		array(
			'title' => array(
					'text' => 'Weight (kg)'
			),
			'plotLines' => array(
					array(
							'value' => 0,
							'width' => 1,
							'color' => '#808080'
					)
			),
		),
		array(
			'title' => array(
					'text' => 'Calories balance'
			),
			'plotLines' => array(
					array(
							'value' => 0,
							'width' => 1,
							'color' => '#808080'
					)
			),
			'opposite' => true,
			'min' => 500,
		),
	);
	$chart->legend = array(
			"enabled" => false,
			'layout' => 'vertical',
			'align' => 'right',
			'verticalAlign' => 'top',
			'x' => - 10,
			'y' => 100,
			'borderWidth' => 0
	);
	
	$serie = array(
			'name' => 'Weight',
			"showInLegend" => false,
			'data' => array(),
			'lineWidth' => 5.0,
	);
	$serie1 = array(
			'name' => 'Calories IN',
			"showInLegend" => false,
			'data' => array(),
			'yAxis' => 1,
			'lineWidth' => 0.5,
	);
	$serie2 = array(
			'name' => 'Calories OUT',
			"showInLegend" => false,
			'data' => array(),
			'yAxis' => 1,
			'lineWidth' => 0.5,
	);
	
	$chart->tooltip->shared = true;

	$chart->tooltip->formatter = new HighchartJsExpr(
    "function() { 
			var str = '<b>'+ this.points[0].x +'</b><br/>'+ this.points[0].series.name + ' ' + this.points[0].y + ' kg (average)';
			
			if (this.points[1].y > 0.1) {
			  if (this.points[1].y >= this.points[2].y) {
					str += '<br/>' + this.points[1].y +' Cal IN - ' + this.points[2].y + ' Cal OUT = +' + (this.points[1].y - this.points[2].y) + ' Cal (+' +
						Math.round(1000.0*(this.points[1].y - this.points[2].y)/".H_FoodCalculator::CALORIES_PER_KG.", 3) + ' g)';
				}
				else {
					str += '<br/>' + this.points[1].y +' Cal IN - ' + this.points[2].y + ' Cal OUT = ' + (this.points[1].y - this.points[2].y) + ' Cal (' +
						Math.round(1000.0*(this.points[1].y - this.points[2].y)/".H_FoodCalculator::CALORIES_PER_KG.", 3) + ' g)';
				}
			}
			else {
				str += '<br/>no calories data.';
			}
			
			return str;
		}"
	);
	
	$now = time();
	$nowDay = H_DataTimespan::getDayFromTime($now);
	
	$oldest = null;
	foreach ($bodyRecords as $time => $bodyRecordArrayArray) { 
		if ($oldest === null || $time < $oldest) {
			$oldest = $time;
		}
	}
	
	$lastBodyRecordResult = null;
	
	for ($time = $oldest; $time <= $nowDay; ) {

		$caloriesBalance = getCaloriesBalance($time);
		$date_format = ($now - $time >= F_UtilsTime::AN_YEAR) ? "j M y" : "j M";
		$chart->xAxis->categories[] = date($date_format, $time);
			
		$calIn = $caloriesBalance["in"];
		$calOut = $caloriesBalance["out"];

		if (isset($bodyRecords[$time])) {
			$clc_w = 0.0;
			$clc_c = 0;

			foreach ($bodyRecords[$time] as $bodyRecord) {
				if ($bodyRecord->weight < 0.1) continue;

				$clc_w += (float)$bodyRecord->weight;
				$clc_c ++;
			}

			$dataPoint = round($clc_w / (float)$clc_c, 2);
			$lastBodyRecordResult = $dataPoint;	
		}
		else {
			$dataPoint = $lastBodyRecordResult;
		}
		
		if ($calIn > 0.1 && $calOut > 0.1) {
			$dataPoint1 = round($calIn, 0);
			$dataPoint2 = round($calOut, 0);	
		}
		else {
			$dataPoint1 = null;
			$dataPoint2 = null;	
		}

		array_push($serie["data"], (float)$dataPoint);
		array_push($serie1["data"], (float)$dataPoint1);
		array_push($serie2["data"], (float)$dataPoint2);
		
		$time = strtotime("+1 day", $time);
	}
	
	$chart->series[] = $serie;
	$chart->series[] = $serie1;
	$chart->series[] = $serie2;
	
	return $chart;
}

?>
<script>
<?php 
if ($weightOverTimeGraph) {
	echo $weightOverTimeGraph->render("weightOverTimeChart"); 
}
?>
</script>