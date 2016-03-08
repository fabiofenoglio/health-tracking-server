<?php defined("_JEXEC") or die(); 

$user = JFactory::getUser();
$regime = H_FoodRegime::getUserActiveOrDefault($user->id);
$userInfo = H_UserInfo::loadByUser($user->id);
$lastBodyRecord = H_BodyRecord::getMerged($user->id);
$bmr = H_FoodCalculator::computeBMR($userInfo, $lastBodyRecord);

$target = new JObject();
$target->energy = $bmr * 
	$lastBodyRecord->mul * 
	$regime->getComponent(H_FoodComponent::ID_ENERGY)->goal_percentage;

$displayClass = "lmcaloriesrecord_fit";
$from = F_Input::getInteger("from", 0);
$max_days_to_display = F_Input::getInteger("max", 31);

$filter = "userid=" . $user->id . " AND type=" . H_FitData::TYPE_CALORIES;
if ($from) $filter .= " AND date <=" . $from;
$fitCaloriesRecords = H_FitData::query($filter, "date DESC");

$lastDisplayedTime = null;
$displayed = 0;
$break_with_more_to_see = false;

$show_params = array("target" => $target);
?>
<h3>
	Your <?php echo H_UiLang::getRandomAdjective(); ?> calories records
</h3>

<table class='table table-noborders'>
<?php echo F_Content::call_element_list_header($displayClass, $show_params); ?>
<?php
$show_params["previous"] = null;

foreach ($fitCaloriesRecords as $record) { 
	echo F_Content::call_element_list_display($record, $displayClass, $show_params);
	$show_params["previous"] = $record;
	$lastDisplayedTime = $record->date;
	$displayed ++;
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