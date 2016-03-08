<?php defined("_JEXEC") or die(); 

$class = H_FoodRecord::CLASS_NAME;

function dividePerDay($list) {
	$r = array();
	foreach ($list as $el) {
		$key = date("k_z_o", $el->time);
		if (!isset($r[$key])) $r[$key] = array();
		array_push($r[$key], $el);
	}
	return $r;
}

$user = JFactory::getUser();

if (F_Input::exists("fast_clone")) {
	F_SimplecomponentHelper::show("cmp.actions.diet.repeatrecord");
}

$from = F_Input::getInteger("from", 0);
$max_days_to_display = F_Input::getInteger("max", 31);

$filter = "userid=" . $user->id;
if ($from) $filter .= " AND time <=" . $from;

$foodRecordsRaw = F_Table::loadClassList($class, $filter, "time DESC, timestamp DESC");

// divide records per day
$foodRecords = dividePerDay($foodRecordsRaw);
$lastDisplayedTime = null;
$displayed = 0;
$break_with_more_to_see = false;

$add_record_url = H_UiRouter::getAddFoodRecordUrl();
$show_params = array("time" => "none");

?>
<h3>
	Your <?php echo H_UiLang::getRandomAdjective(); ?> food records
</h3>
<p>
	<a href="<?php echo $add_record_url; ?>"
		 class="btn btn-success btn-large"
		 style="float:right;margin-bottom:30px;"
		 >
		<span class='icon-plus' ></span> I ate something!
	</a>
</p>
<?php
foreach ($foodRecords as $dayKey => $foodRecordGroup) { 
	if ($max_days_to_display > 0 && $displayed >= $max_days_to_display) {
		$break_with_more_to_see = true;
		break;
	}
	?>
	<?php 
	$dayDetailUrl = H_UiRouter::getFoodDetailForDay($foodRecordGroup[0]->time);
	echo "<strong><a href='$dayDetailUrl' target='_blank'>".date("j M", $foodRecordGroup[0]->time)."</a></strong><br/>";
	
	?>
	<br/>
	<table class='table table-noborders'>
	<?php echo F_Content::call_element_list_header($class, $show_params); ?>
	<?php 
	foreach ($foodRecordGroup as $foodRecord) { 
		echo F_Content::call_element_list_display($foodRecord, $class, $show_params);
		$lastDisplayedTime = $foodRecord->time;
	}
	?>
</table>
<?php
	$displayed ++;
}

if ($from) {
	$url = JUri::getInstance(); $url->setVar("from", 0);
	echo "<a href='$url' class='btn'>first page</a> ";
}

if ($break_with_more_to_see) {
	$url = JUri::getInstance(); $url->setVar("from", $lastDisplayedTime);
	echo "<a href='$url' class='btn'>next $max_days_to_display</a> ";
}
