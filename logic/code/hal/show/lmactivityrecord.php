<?php defined("_JEXEC") or die();

$params = F_Snippet::getParams();
$display_time = $params->get("time", null);

if ($params->get("header") == true)
{
    echo "<tr>";
    echo "<th></th>";
    echo "<th>Time</th>";
    echo "<th>Activity</th>";
    echo "<th>Duration</th>";
    echo "<th>Calories</th>";
    echo "<th></th>";  
    echo "</tr>";
    return;
}

$el = $params->get("element");
if (!$el) die("param error");

$user = JFactory::getUser();
$now = time();
$edit_url = H_UiRouter::getEditActivityRecordUrl($el->id);
$date_format = ($now - $el->time >= F_UtilsTime::AN_YEAR) ? "j M y" : "j M";

if ($el->source == H_ActivityRecord::SOURCE_FIT) {
  $fitActivityInfo = H_FitActivityInfo::loadByFitCode($el->data->get("activity_code", -1));
}
else {
  $fitActivityInfo = null;
}

$isSmall = $el->duration < 15*60 ? true : false;

?>
<tr>
  <td>
    <!-- left side -->
    <?php if ($el->source == H_ActivityRecord::SOURCE_USER) : ?>
    <a href='<?php echo $edit_url; ?>'><span class='icon-pencil' ></span>
    </a>
    <?php endif; ?>
  </td>
  <td>
    <!-- time -->
    <?php if ($isSmall) { echo "<small>"; } ?> 
    <?php echo H_UiCells::getTimeCell($el); ?>
    <?php if ($isSmall) { echo "</small>"; } ?> 
    <br/><br/>
  </td>
  <td>
    <!-- activity -->
    <?php if ($isSmall) { echo "<small>"; } ?> 
    <?php if ($el->source == H_ActivityRecord::SOURCE_USER) : 
      echo $el->activity;
    else: 
      echo $fitActivityInfo->name;
    endif; 
    ?>
    <?php if ($isSmall) { echo "</small>"; } ?> 
  </td>
  <td>
    <!-- duration -->
    <?php if ($isSmall) { echo "<small>"; } ?> 
    <?php 
    if ($el->duration > 0) {
      echo F_UtilsFormatter::getIntervalString($el->duration);
    }
    if ($el->relative_fatigue > 0.0 && $el->relative_fatigue != 100) {
      echo "<br/><small>at " . round($el->relative_fatigue, 0) . " %</small>";
    }
    ?>
    <?php if ($isSmall) { echo "</small>"; } ?> 
  </td>
  <td>
    <!-- calories -->
    <?php if ($el->source == H_ActivityRecord::SOURCE_USER) :
      if ($el->calories > 0.0) {
        echo round($el->calories, 2) . " kcal";
      }
      else {
        echo H_UiCells::getMissingData();
      }
    else:
      echo H_UiLang::getFromGoogleFit();
    endif;
    ?>
  </td>
  <td>
    <!-- right side -->
  </td>
</tr>