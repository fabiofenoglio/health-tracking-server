<?php defined("_JEXEC") or die();

$params = get_object_vars(F_Snippet::getParams()) ;

function _getParam($params, $key, $default = null, $die = false) {
  if (!isset($params[$key])) {
    if (is_callable($default)) {
      return $default();
    }
    else {
      if ($default === null && $die) {
        die("param not found: " . $key);
      }
      return $default;  
    }
  }
  return $params[$key];
}

// load parameters
$userId =               _getParam($params, "userId", null);
$class =                _getParam($params, "class", null, true);
$addRecordUrl =         _getParam($params, "addRecordUrl", null);
$showParams =           _getParam($params, "elementShowParams", array());
$queryFilter =          _getParam($params, "queryFilter", null);
$querySort =            _getParam($params, "querySort", "time DESC");
$queryBuilder =         _getParam($params, "queryBuilder", null);
$preDisplayFilter =     _getParam($params, "preDisplayFilter", null);
$displayClassName =     _getParam($params, "displayClassName", $class::CLASS_NAME);
$preShowParamsEditor =  _getParam($params, "preShowParamsEditor", null);
$postShowParamsEditor = _getParam($params, "postShowParamsEditor", null);
$queryCompleted = 			_getParam($params, "onQueryCompleted", null);
$showAddNew = 					_getParam($params, "showAddNew", true);
$recordList = 					_getParam($params, "recordList", null);

// url input
$beforeTime =   F_Input::getInteger("from", 0);
$maxDaysToDisplay = F_Input::getInteger("maxdays", 31);

// common data
if ($userId === null) {
  $userId = JFactory::getUser()->id;
}
$now = time();

// Add fixed show params
if (empty($showParams)) {
  $showParams = array();
}

$showParams = array_merge($showParams, array(
  "autoList" => true,
  "class" => $class
));

// build query
$filterArray = array(
  "userid=" . $userId, 
  "time>=" . ($now - F_UtilsTime::A_DAY * $maxDaysToDisplay)
);
if ($beforeTime) {
  $filterArray[] = "time <=" . $beforeTime;
}
if (!empty($queryFilter)) {
  if (is_array($queryFilter)) {
    $filterArray = array_merge($filterArray, $queryFilter);  
  }
  else {
    $filterArray[] = $queryFilter;
  }
}

if ($queryBuilder) {
  $filterArray = $queryBuilder($filterArray);
}

// load elements
if ($recordList) {
	$records = $recordList;
	if (!is_array($records)) {
		$records = array($records);
	}
}
else {
	$records = $class::query($filterArray, $querySort);	
}

if ($queryCompleted !== null && is_callable($queryCompleted)) {
	$queryCompleted($records);
}

// prepare pagination
$lastDisplayedTime = null;
$displayedCounter = 0;
$breakWithMoreToSee = false;
?>
<?php if ($showAddNew && $addRecordUrl) : ?>
<p>
	<a href="<?php echo $addRecordUrl; ?>"
		 class="btn btn-success btn-large"
		 style="float:right;margin:30px;"
		 >
		<span class='icon-plus' >
    </span> <?php echo _getParam($params, "addRecordText", "new"); ?>
	</a>
</p>
<?php endif; ?>

<?php if (empty($records)) : ?>
<p> 
<br/><hr/><br/>
  sorry, there's nothing here :(
<br/><hr/><br/>
</p>

<?php else : ?>
  <table class='table table-noborders'>
  <?php 
  echo F_Content::call_element_list_header($displayClassName, $showParams);
  $showParams["previous"] = null;
  $showParams["next"] = null;
  $showParams["list"] = $records;

  $recordKeys = array_keys($records);
  $recordKeysCount = count($recordKeys);
    
  foreach ($recordKeys as $recordKeyCount => $recordKey) {
    $record = $records[$recordKey];
    
    if ($recordKeyCount > 0) {
      $showParams["previous"] = $records[$recordKeys[$recordKeyCount - 1]];
    }
    if ($recordKeyCount < ($recordKeysCount - 1)) {
      $showParams["next"] = $records[$recordKeys[$recordKeyCount + 1]];
    }
    
    if ($maxDaysToDisplay > 0 && $displayedCounter >= $maxDaysToDisplay) {
      $breakWithMoreToSee = true;
      break;
    }

    $displayThis = true;
    
    if ($preDisplayFilter !== null && is_callable($preDisplayFilter)) {
      $preDisplayFilterResult = $preDisplayFilter($record, $showParams);
      if ($preDisplayFilterResult === false) {
        $displayThis = false;
      }
      else if ($preDisplayFilterResult !== null && is_object($preDisplayFilterResult)) {
        $record = $preDisplayFilterResult;
      }
    }
    
    if (! $displayThis) {
      continue;
    }
    
    if ($preShowParamsEditor !== null && is_callable($preShowParamsEditor)) {
      $showParamsEditorResult = $preShowParamsEditor($showParams);
      if (is_array($showParamsEditorResult)) {
        $showParams = $showParamsEditorResult;
      }
    }
    
    echo F_Content::call_element_list_display($record, $displayClassName, $showParams);
    $showParams["previous"] = $record;
    
    if ($postShowParamsEditor !== null && is_callable($postShowParamsEditor)) {
      $showParamsEditorResult = $postShowParamsEditor($showParams);
      if (is_array($showParamsEditorResult)) {
        $showParams = $showParamsEditorResult;
      }
    }
    
    $lastDisplayedTime = $record->time;
    $displayedCounter ++;	  
  }
  ?>	
  </table>

  <?php
  if ($beforeTime) {
    $url = JUri::getInstance(); $url->setVar("from", 0);
    echo "<a href='$url' class='btn'>first page</a> ";
  }

  if ($breakWithMoreToSee) {
    $url = JUri::getInstance(); $url->setVar("from", $lastDisplayedTime);
    echo "<a href='$url' class='btn'>next $maxDaysToDisplay</a> ";
  }
endif; ?>