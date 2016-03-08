<?php defined("_JEXEC") or die(); 

$class = H_FoodInfo::CLASS_NAME;

$user = JFactory::getUser();

$filter = "userid=" . $user->id;
$filter_by_group = F_Input::exists("group");

if ($filter_by_group) {
	$group_sanitize_filter = "/[^a-zA-Z0-9-_.\s]/";
	$input_group = F_Safety::getSanitizedInput("group", $group_sanitize_filter);
	if (strlen($input_group) > 0) {
		$filter .= " AND `group` LIKE '%".$input_group."%'";
	}
	else {
		$filter .= " AND `group`=''";
	}
}

// seems useless but ready for multiple loads grouping
$foodInfos = array_merge(
	F_Table::loadClassList($class, $filter, "name ASC"),
	array()
);

$add_info_url = H_UiRouter::getAddFoodInfoUrl();
$groups = H_FoodInfo::getDifferentGroupsForUser($user->id);

?>
<h3>
	Your <?php echo H_UiLang::getRandomAdjective(); ?> food information repository
</h3>
<a href='<?php echo H_UiRouter::getCommonFoodInfosUrl(); ?>'>
	<small>switch to common foods</small>
</a><br/>

<p>
	<a href="<?php echo $add_info_url; ?>"
		 class="btn btn-success btn-large"
		 style="float:right;margin-bottom:30px;margin-left:20px;"
		 >
		<span class='icon-plus' ></span> tell me about a new food
	</a>
</p>

<?php // write group filter suggestions
if (!empty($groups)) {
	echo "<p>";
	if ($filter_by_group) {
		$filter_url = JURI::getInstance();
		$filter_url->delVar("group");
		$filter_url = F_Addresses::absoluteUrlToRelativeUrl($filter_url->toString());

		echo "<a class='btn btn-small' href='$filter_url'>all (clear filter)</a> ";
		echo "<br/> ";
	}
	
	$filter_url = JURI::getInstance();
	$filter_url->setVar("group", "");
	$filter_url = F_Addresses::absoluteUrlToRelativeUrl($filter_url->toString());

	echo "<a class='btn btn-small' href='$filter_url'>no group</a> ";

	foreach ($groups as $group) {
		if (strlen($group) < 1) continue;

		$filter_url = JURI::getInstance();
		$filter_url->setVar("group", urlencode($group));
		$filter_url = F_Addresses::absoluteUrlToRelativeUrl($filter_url->toString());

		echo "<a class='btn btn-small' href='$filter_url'>$group</a> ";
	}
	echo "</p>";
}
?>

<table class='table table-noborders'>
<?php echo F_Content::call_element_list_header($class); ?>
<?php 
foreach ($foodInfos as $foodInfo) { 
	echo F_Content::call_element_list_display($foodInfo, $class);
}
?>
</table>
<?php
