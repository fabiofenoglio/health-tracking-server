<?php defined("_JEXEC") or die(); 

$filter = array();

if (!F_Input::getInteger("showsmall", 1)) {
	$filter[] = "duration>=600";
}
else {
	$filter[] = "duration>=60";
}

$records = null;

$params = array(
  "class" =>         H_ActivityRecord,
  "addRecordUrl" =>  H_UiRouter::getAddActivityRecordUrl(),
  "addRecordText" => "I did something!",
  "queryFilter" =>   $filter,
);

$content = H_UiBuilderList::build($params);

$atLeastOneWithoutCalories = H_UtilsArray::first(
	$content->list, 
	function($o){return ($o->source == H_ActivityRecord::SOURCE_USER && $o->calories < 0.1);}
);

?>
<h3>
Your <?php echo H_UiLang::getRandomAdjective(); ?> activity records
</h3>
<?php
if ($atLeastOneWithoutCalories) {
	echo "<p>
		<span class='icon-warning'></span> Activities with no specified calories will not be counted
	</p>";	
}

echo $content->html;