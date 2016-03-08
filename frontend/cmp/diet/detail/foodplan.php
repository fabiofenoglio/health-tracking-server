<?php defined("_JEXEC") or die();

// Load current day status
F_Library::importExternal("highcharts");
use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;

// Load input object and check permissions
$user = JFactory::getUser();
$now = time();

// Check input data
// input foods and authorizations
$input = H_UiBuilderFoodRecordList::collectInputFoodList();
if (!H_UiBuilderFoodRecordList::checkInputFoodListAuthorization($user->id, $input)) {
	return H_UiLang::notAllowed();
}

if (isset($input->input["l1"])) {
	$inputFoodList = $input->input["l1"];
}
else {
	$inputFoodList = null;
}

// other input parameters
$requiredPercentage = F_Input::getInteger("percentage", 0);
$dayOffset = F_Input::getInteger("dayoffset", 0);
$day = H_DataTimespan::getDayFromTime(time() - $dayOffset * F_UtilsTime::A_DAY);

if ($requiredPercentage < 1) {
	if ($dayOffset < 1) {
		$desiredTargetPercentage = getDesiredTargetPercentage();	
	}
	else {
		$desiredTargetPercentage = 100.0;
	}	
}
else {
	$desiredTargetPercentage = $requiredPercentage;
}

$evaluationRequired = F_Input::exists("action-suggest") ? true : false;
$evaluatingRecords = array();

// Get current food record data
if (!empty($inputFoodList) || $evaluationRequired) {
	$d = H_DataAggregator::getFoodData($user->id, $day);
	if (!$d->completed) {
		F_Log::showError("not enough data :(");
		return;
	}

	// Aggregate component data
	$d->foodRecordsComponents = H_DataAggregator::aggregateFoodRecords($d->foodRecords, $d->foodInfos, $d->activeRegime);	
	
	// Add input foods
	if (!empty($inputFoodList)) {
		foreach ($inputFoodList as $inputFoodItem) {
			if (!$inputFoodItem->status) {
				continue;
			}

			$addRecord = H_FoodRecord::create();
			$addRecord->userid = $user->id;
			$addRecord->time = $now;
			$addRecord->group = "evaluation";
			$addRecord->foodid = $inputFoodItem->foodid;
			$addRecord->amount = $inputFoodItem->amount;

			$evaluatingRecords[] = $addRecord;
			
			if (!isset($d->foodInfos[$inputFoodItem->foodid])) {
				$d->foodInfos[$inputFoodItem->foodid] = $input->foodInfos[$inputFoodItem->foodid];
			}
		}
	}
}

// Evaluate what to add if required
$advised = false;
$evaluationResult = null;

if ($evaluationRequired) {
	$evaluationResult = getFoodRecordsEvaluation($d, $desiredTargetPercentage, $evaluatingRecords);

	if (!empty($evaluationResult)) {
		$advised = true;
		$objArray = array_values($evaluationResult)[0];

		$addRecord = H_FoodRecord::create();
		$addRecord->userid = $user->id;
		$addRecord->time = $now;
		$addRecord->group = "evaluation";
		$addRecord->foodid = $objArray[1]->id;
		$addRecord->amount = $objArray[2];

		$evaluatingRecords[] = $addRecord;

		if (!isset($d->foodInfos[$addRecord->foodid])) {
			$d->foodInfos[$addRecord->foodid] = $input->foodInfos[$objArray[1]];
		}
	}
}

if ($d) {
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
  $data->completed
  */
  $providedByFood = array();
	foreach ($evaluatingRecords as $evaluatingRecord) {
		$foodInfo = $d->foodInfos[$evaluatingRecord->foodid];
		
		foreach ($d->activeRegime->data->components as $componentId => $componentSpecification) {
			$foodComponent = $d->foodComponents[$componentId];
			$propertyName = $foodComponent->info_property;
			$foodQt = $evaluatingRecord->amount * $foodInfo->$propertyName / 100.0;
			if (!isset($providedByFood[$componentId])) {
				$providedByFood[$componentId] = 0.0;
			}
			$providedByFood[$componentId] += $foodQt;
		}
	}
  
  // Print graph
  $cArray = H_UiGraphs::getBasicBar();
  $c = $cArray[0];
	$dType = "bar";
	
	$c->plotOptions->$dType->dataLabels->enabled = true;
	$c->plotOptions->$dType->dataLabels->formatter = new HighchartJsExpr("
		function() {
			if (this.point.identity_adding) {
				if (this.y < 1) {
					return '';
				}
				return 'to ' + this.point.custom_total +' %';
			}
			if (this.y < 1) {
				return '';
			}
			return '' + this.y +' %';
		}");
	
  $c->title->text = "Possible intake";
  $c->subtitle->text = "";
  $c->yAxis->min = 0;
  // $c->yAxis->max = 175;
  $c->yAxis->title->text = "% of daily target";
  $c->plotOptions->$dType->stacking = 'normal';
  $c->tooltip->formatter = new HighchartJsExpr("
    function() {
      if (this.point.istarget) {
        if (this.point.istarget == 2) {
          return '' + this.x +' Target at ".date("H:i", time()).": '+ this.point.target_value;
        }
        else {
          return '' + this.x +' Daily Target: '+ this.point.target_value;
        }
      }
      else {
        return '' + this.point.custom_name + '<br/>' + this.x +': '+ this.y +' %, ' + this.point.custom_value +
        (this.point.custom_total ? '<br/><br/>Reaching in total: ' + this.point.custom_total + ' %' : '');
      }
    }");

  $serie0 = array(
    'name' => "Intake per component",
    'data' => array()
  );
  $serie0b = array(
    'name' => "From added food",
    'data' => array()
  );
  $serie1 = array(
    "type" => "line",
    'name' => "Target",
    "color" => H_UiGraphics::LINE_TARGET_COLOR,
    'data' => array()
  );
  $serie1b = array(
    "type" => "line",
    'name' => "Target",
    "color" => H_UiGraphics::LINE_TARGET_COLOR,
    'data' => array()
  );

  foreach ($d->targetComponents as $tcId => $target_component_value) {
    $foodComponent = $d->foodComponents[$tcId];

    $c->xAxis->categories[] = $foodComponent->display_name;

    $dataPointValue = 100.0 * 
      $d->foodRecordsComponents[$tcId] / 
      $target_component_value;

    $dataPointValue = round($dataPointValue, 0);

    if (isset($providedByFood[$tcId])) {
      $foodContribute = $providedByFood[$tcId];
    }
    else {
      $foodContribute = null;
    }
    $withFoodContribute = 100.0 * ($foodContribute + $d->foodRecordsComponents[$tcId]) / $target_component_value;

    array_push($serie0["data"], array(
      "y" => $dataPointValue,
      "color" => H_UiGraphics::getColumnColorFromPercentage($withFoodContribute, $desiredTargetPercentage),
      "custom_name" => "Actual",
      "custom_value" => round($d->foodRecordsComponents[$tcId], 0) . " " . $foodComponent->unit
    ));

    array_push($serie0b["data"], array(
      "y" => round(100.0 * $foodContribute / $target_component_value, 0),
      "color" => H_UiGraphics::COLUMN_ADDING_COLOR,
      "custom_name" => "Adding",
      "custom_total" => round($withFoodContribute, 0),
      "custom_value" => round($foodContribute, 0) . " " . $foodComponent->unit,
			"identity_adding" => true
    ));

    // push target point
    array_push($serie1["data"], array(
      "y" => $desiredTargetPercentage,
      "istarget" => 2,
      "target_value" => round($desiredTargetPercentage*$target_component_value/100.0, 0) . " " . $foodComponent->unit
    ));
    
    array_push($serie1b["data"], array(
      "y" => 100.0,
      "istarget" => 1,
      "target_value" => round($target_component_value, 0) . " " . $foodComponent->unit
    ));
  }

  $c->series[] = $serie0b;
  $c->series[] = $serie0;
  if ($desiredTargetPercentage != 100.0) {
    $c->series[] = $serie1;  
  }
  $c->series[] = $serie1b;
  $cArray[0] = $c;

$c->printScripts();
}

$controlParams = array(
	"onAfterFoodListRefresh" => "afterFoodListRefresh"
);

$foodInputListControl = H_UiBuilderFoodRecordList::buildInputFoodList("l1", $evaluatingRecords, $controlParams);
$formUrl = JUri::current();

?> 
<h3>Food plan <?php
  if ($dayOffset) { echo " (" . $dayOffset . " days ago)"; }
  else { echo " for today"; }
  ?>
</h3>

<div id='<?php echo $cArray[1]; ?>'></div> 
<br/>
<form action="<?php echo $formUrl; ?>" method="post" id="evaluate-form">
<table class='table table-noborders'>
  <tr>
      <td style='border-top-style: hidden;'>
        Foods
      </td>
      <td style='border-top-style: hidden;'>
        <?php echo $foodInputListControl->html_search_button; ?>
				<br/>
				<?php echo $foodInputListControl->html_search_result; ?>
      </td>
  </tr>
  <tr>
      <td colspan='2' style='border-top-style: hidden;'>
        <?php echo $foodInputListControl->html_list; ?>
      </td>
  </tr>
</table>

<div class="form-actions">
  <button type="submit" name="action-evaluate" class="btn btn-primary"
					id="button-evaluate"
					>Evaluate</button>
	<button type="submit" name="action-suggest" class="btn btn-primary">Suggest me something</button>
	<button type="submit" name="action-add-records" class="btn btn-primary"
					id="button-add-records"
					>Add food records</button>
</div>
</form>

<script>
<?php  
if ($d) {
  echo $cArray[0]->render("graph_".$cArray[2]);
}
	
echo $foodInputListControl->js;
?>
	
jQuery("#button-add-records").on("click", function(e){
    e.preventDefault();
    jQuery('#evaluate-form').attr('action', "<?php echo H_UiRouter::getEditFoodRecordUrl(null, array(H_UiRouter::BACKTO_KEY => null)); ?>").submit();
});

function afterFoodListRefresh(list) {
	var btt = jQuery("#button-evaluate");
	var btt2 = jQuery("#button-add-records");
	
	var length = 0;
	for( var key in list ) {
		if( list.hasOwnProperty(key) ) {
				++length;
		}
	}
	
	if (length > 0) {
		btt.show();
		btt2.show();
	}
	else {
		btt.hide();
		btt2.hide();
	}
}
</script>

<?php
function getDesiredTargetPercentage() {
  /*
  bf :    20%,  up to 10:30
  lunch:  35%,  10:30 - 15:00
  snack : 20%,  15:00 - 18:00
  dinner: 25%,  18:00 to night
  */
  $now = time();
  $desiredTargetPercentage = 0.0;
  $icmp_timeOfDayHr = ($now - strtotime(date("Y-m-d", $now) . " 00:00:00"))/F_UtilsTime::AN_HOUR;
  
  if ($icmp_timeOfDayHr < 10.5) {
    $desiredTargetPercentage = 20.0;
  }
  else if ($icmp_timeOfDayHr < 15.0) {
    $desiredTargetPercentage = 55.0;
  }
  else if ($icmp_timeOfDayHr < 18.0) {
    $desiredTargetPercentage = 75.0;
  }
  else {
    $desiredTargetPercentage = 100.0;
  }
  
  return $desiredTargetPercentage;
}

function getFoodRecordsEvaluation($d, $desiredTargetPercentage, $evaluatingRecords) {
  // get the last meals
  $maxFoodRecords = 100;
  $now = time();
	$addedFoodRecordsComponents = array();
  
	// account for already added records
	foreach ($d->activeRegime->data->components as $componentId => $componentSpecification) {
		$addedFoodRecordsComponents[$componentId] = $d->foodRecordsComponents[$componentId];
	}

	foreach ($evaluatingRecords as $evaluatingRecord) {
		if ($evaluatingRecord->foodid < 1) {
			continue;
		}
		
		$foodInfo = $d->foodInfos[$evaluatingRecord->foodid];
		
		foreach ($d->activeRegime->data->components as $componentId => $componentSpecification) {
			$foodComponent = $d->foodComponents[$componentId];
			$propertyName = $foodComponent->info_property;
			$foodQt = $evaluatingRecord->amount * $foodInfo->$propertyName / 100.0;

			if (!isset($addedFoodRecordsComponents[$componentId])) {
				$addedFoodRecordsComponents[$componentId] = 0.0;
			}
			$addedFoodRecordsComponents[$componentId] += $foodQt;
		}
	}
	
  $query = "SELECT foodid,amount FROM " . F_Table::getClassTable(H_FoodRecord::CLASS_NAME) .
    " WHERE userid=".$d->user->id." AND foodid>0".
    " ORDER BY time DESC LIMIT $maxFoodRecords";
  
  $foodRecords = F_Table::doQuery($query, null, F_Table::LOADMETHOD_OBJECT_LIST);
  
  $computeResult = array();
  $cnt = 0;
  
  $amountMultipliers = array(1.0, 0.5, 0.25, 1.5, 2.0, 5.0);
  if ($desiredTargetPercentage > 80.0) {
    $objFparam_pow_over = 1.57;
    $objFparam_pow_under = 1.25;  
  }
  else {
    $objFparam_pow_over = 1.38;
    $objFparam_pow_under = 1.25;
  }
  
  $testedRecords = array();
  
  foreach ($foodRecords as $foodRecord) {
    if ($foodRecord->amount < 0.1) continue;
    if ($foodRecord->foodid < 1) continue;
    if (isset($testedRecords[$foodRecord->foodid])) {
      continue;
    }
    
    // compute contribute
    $testedRecords[$foodRecord->foodid] = 1;
    
    if (isset($d->foodInfos[$foodRecord->foodid])) {
      $foodInfo = $d->foodInfos[$foodRecord->foodid];  
    }
    else {
      $loadedFoodInfo = H_FoodInfo::load($foodRecord->foodid);
      if ($loadedFoodInfo) {
        $d->foodInfos[$foodRecord->foodid] = $loadedFoodInfo;
        $foodInfo = $loadedFoodInfo;
      }
      else {
        $foodInfo = null;
      }
    }
    if (!$foodInfo) continue;
    
    $testAmounts = array();
    
    if ($foodInfo->unit_size < 0.01) {
      if ($foodInfo->serving_size > 0.01) {
        foreach ($amountMultipliers as $temptativeMultiplier) {
          $testAmounts[] = $foodInfo->serving_size * $temptativeMultiplier;
        }    
      }
      else {
        foreach ($amountMultipliers as $temptativeMultiplier) {
          $testAmounts[] = $foodRecord->amount * $temptativeMultiplier;
        }
      }
    }
    else {
      // use unit size for test amounts
      $currentUnitAmount = (int)(round($foodRecord->amount / $foodInfo->unit_size, 0));
      $testAmounts[] = $foodInfo->unit_size * $currentUnitAmount;
      $step = $currentUnitAmount / 10.0;
      for ($i = $step; $i < 2 * $currentUnitAmount; $i += $step) {
        $iVal = $foodInfo->unit_size * (int)$i;
        if ($iVal > 0.0 && !in_array($iVal, $testAmounts)) {
          $testAmounts[] = $iVal;
        }
      }
    }
    
    foreach ($testAmounts as $temptativeAmount)
    {
      if ($temptativeAmount < 1.0) continue;
      
      $objFunc = 0.0;
      
      foreach ($d->activeRegime->data->components as $componentId => $componentSpecification) {
        $foodComponent = $d->foodComponents[$componentId];
        $propertyName = $foodComponent->info_property;
        $foodQt = $temptativeAmount * $foodInfo->$propertyName / 100.0;

        $totalComponent = $foodQt + $addedFoodRecordsComponents[$componentId];
        $totalComponentPc = 100.0 * $totalComponent / $d->targetComponents[$componentId];

        if ($totalComponentPc >= $desiredTargetPercentage) {
          // overeat
          $objFunc += pow($totalComponentPc - $desiredTargetPercentage, $objFparam_pow_over);
        }
        else {
          // undereat
          $objFunc += pow(($desiredTargetPercentage - $totalComponentPc), $objFparam_pow_under);
        }
      }

      $key = (int)$objFunc;
      while (strlen($key) < 10) $key = "0".$key;
      $key = $key . ".000" . ($cnt++) ;
      $computeResult[$key] = array($objFunc, $foodInfo, $temptativeAmount);
    }
  }
  
  ksort($computeResult);
  return $computeResult;
}