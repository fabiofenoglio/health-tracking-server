<?php defined("_JEXEC") or die();

// Load current day status
F_Library::importExternal("highcharts");
use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;

// Load input object and check permissions
$user = JFactory::getUser();

$input_id = (int)F_Input::getInteger("id");
$amount = (float)F_Input::getRaw("amount", 0);

if ($input_id) {
  $obj = H_FoodInfo::load($input_id);
  if (!$obj) {
    F_Log::showError("requested food info not found :(");
    return;
  }
}

if ($obj) {
  if (!H_FoodInfo::userCanView($obj, $user->id)) {
    F_Log::showError("you are not allowed >:[");
    return;
  }
  
  if ($amount < 0.1) {
    $amount = $obj->serving_size;
    if ($amount < 0.1) {
      F_Log::showError("Unknown amount");
      return;
    }
  }
}
else {
  $amount = 0.0;
}

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

$evaluationRequired = F_Input::getInteger("evaluate", 0) ? true : false;

if ($obj || $evaluationRequired) {
	$d = H_DataAggregator::getFoodData($user->id, $day);
	if (!$d->completed) {
		F_Log::showError("not enough data :(");
		return;
	}

	$d->foodRecordsComponents = H_DataAggregator::aggregateFoodRecords($d->foodRecords, $d->foodInfos, $d->activeRegime);	
}
else {
	$d = null;
}

$advised = false;
$evaluation = null;

if ($evaluationRequired && !$obj) {
	$evaluation = getFoodRecordsEvaluation($d, $desiredTargetPercentage);

	if (!empty($evaluation)) {
		$advised = true;
		$objArray = array_values($evaluation)[0];
		$obj = $objArray[1];
		$amount = $objArray[2];
	}
}

if (! $advised) {
  $advise_url = JURI::getInstance();
  $advise_url->delVar("id");
  $advise_url->delVar("amount");
  $advise_url->setVar("evaluate", 1);
  $advise_url = trim($advise_url->toString(), "\\/");
}

if ($obj) {
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
  foreach ($d->activeRegime->data->components as $componentId => $componentSpecification) {
    $foodComponent = $d->foodComponents[$componentId];
    $propertyName = $foodComponent->info_property;
    $foodQt = $amount * $obj->$propertyName / 100.0;
    $providedByFood[$componentId] = $foodQt;
  }

  // Print graph
  $cArray = H_UiGraphs::getBasicBar();
  $c = $cArray[0];
	$dType = "bar";
	
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
      "custom_value" => round($foodContribute, 0) . " " . $foodComponent->unit
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

?> 
<h3>Food plan <?php
  if ($dayOffset) { echo " (" . $dayOffset . " days ago)"; }
  else { echo " for today"; }
  ?></h3>
<?php 
if ($advise_url) {
  echo "<a href='$advise_url' class='btn'>suggest me something <br/><small>that won't probably fit</small></a><br/><br/>";
}
?>

<?php if ($obj) : ?>
  <strong>
  <?php if ($advised) : ?>
    what about <?php echo $amount; ?> g of <?php echo $obj->getDisplayName(); ?> ?
  <?php else : ?>
    if you add <?php echo $amount; ?> g of <?php echo $obj->getDisplayName(); ?>
  <?php endif; ?>
  </strong>
  <?php 
  if ($obj->unit_size > 0.01) {
    echo "<br/><small>(".round($amount / $obj->unit_size, 2) . " units)</small>";
  }
  ?>
  <br/><br/>
  <div id='<?php echo $cArray[1]; ?>'></div> 
<?php endif; ?>

Amount (in grams)<br/>
<input type="text" class="input input-large" 
				 id="food-amount-input"
				 placeholder="amount in grams (eg. 100)"
         value="<?php echo $amount > 0.0 ? "" : ""; ?>"
				 />
<br/>

Food<br/>
<input type="text" class="input input-large" 
       id="food-search-input"
       placeholder="write something here (eg. 'pizza')"
       />
<a class="btn btn-primary" id="food-search-btn" onclick="javascript:startFoodSearch();">Search</a>

<div id="food-search-results" >
  <table class='table table-noborders' id='food-search-results-table'>
  </table>
</div>

<?php
if ($evaluation) {
  echo "<p>Other advised foods:</p>";
  $lastFood = -1;
  $evalAlreadySuggested = array();
  foreach ($evaluation as $evaluationElement) {
    if ($evaluationElement[1]->id == $lastFood) {
      continue;
    }
    if (in_array($evaluationElement[1]->id, $evalAlreadySuggested)) {
      continue;
    }
    $lastFood = $evaluationElement[1]->id;
    $evalAlreadySuggested[] = $evaluationElement[1]->id;
    
    $url = JURI::getInstance();
    $url->setVar("id", $evaluationElement[1]->id);
    $url->setVar("amount", $evaluationElement[2]);
    $url = trim($url->toString(), "\\/");

    echo "<a href='$url'>".$evaluationElement[1]->getDisplayName() . ", " . $evaluationElement[2] . " g</a><br/>";
  }
}
?>

<script>
<?php  
if ($obj) {
  echo $cArray[0]->render("graph_".$cArray[2]);
}
?>
  
var requestedFood = <?php echo $obj ? $obj->id : 0; ?>;

var evaluationResult = <?php 
  if (true || !$evaluation) {
    echo "null;";
  }
  else {
    echo "[";
    foreach ($evaluation as $evaluationElement) {
      echo "{
        'objf' : ".$evaluationElement[0].",
        'amount' : ".$evaluationElement[2].",
        'food' : ".json_encode($evaluationElement[1])."
      },";
    }
    echo "];";
  }
?>

function selectFood(id) {
  jQuery('#food-search-results-table').html("");
  
  var amount = jQuery("#food-amount-input")[0].value;
  if (!amount) {
    amount = prompt("Amount to preview (in grams)", "");
  }
  
  if (!amount) {
    return;
  }
  
  window.location.href = "<?php echo H_UiRouter::getFoodDetailPreview(array("dttrw" => 1)); 
    ?>&id=" + id + "&amount=" + amount;
}

function startFoodSearch() {
  var query = document.getElementById("food-search-input").value;
  if (!query) {
    alert("insert a search term before starting the query");
    return;
  } 
  
  jQuery("#food-search-btn").hide();
  jQuery('#food-search-results-table').html("<tr><td><font color='black'>searching ...</font></td></tr>");
  
  var jqxhr = jQuery.ajax( 
    "<?php echo F_Service::getRequestUrl("lm.food.search"); ?>", 
    {
			"dataType" : "json",
      "data" : {"query" : query, "dataformat" : 0} 
    }  
  )
  .done(function(data) {
    if (data.error)
      {
        jQuery('#food-search-results-table').html("<tr><td><font color='red'>"+data.error+"</font></td></tr>");
      }
    else
      {
         var r = new Array(), j = -1;
         for (var key=0, size=data.list.length; key<size; key++) {
             r[++j] = "<tr><td style='cursor:pointer'>";
             r[++j] = "<div onclick=\"javascript:selectFood("+data.list[key]["i"]+");\">"+
                      "<span class='icon-arrow-right' />" + 
                      data.list[key]["n"] + "<br/><small>" + data.list[key]["d"] + "</small>";
             r[++j] = "</div></td></tr>";
         }
         jQuery('#food-search-results-table').html(r.join('')); 
      }
  })
  .fail(function() {
    alert( "error" );
    jQuery('#food-search-results-table').html("");
  })
  .always(function() {
    jQuery("#food-search-btn").show();
  });
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

function getFoodRecordsEvaluation($d, $desiredTargetPercentage) {
  // get the last meals
  $maxFoodRecords = 100;
  $now = time();
  
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

        $totalComponent = $foodQt + $d->foodRecordsComponents[$componentId];
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