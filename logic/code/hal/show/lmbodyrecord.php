<?php defined("_JEXEC") or die();

$params = F_Snippet::getParams();
$display_time = $params->get("time", null);

if ($params->get("header") == true)
{
    echo "<tr>";
    echo "<th></th>";
    echo "<th>Time</th>";
    echo "<th>Weight</th>";
    echo "<th>Height</th>";
    echo "<th>Activity</th>";
    echo "<th></th>";  
    echo "</tr>";
    return;
}

$el = $params->get("element");
if (!$el) die("param error");

$show_activity_detail = $params->get("activity_detail", 0);

$user = JFactory::getUser();
$edit_url = H_UiRouter::getEditBodyRecordUrl($el->id);

?>
<tr>
  <td>
    <!-- left side -->
    <?php if ($el->source == H_BodyRecord::SOURCE_USER) : ?>
    <a href='<?php echo $edit_url; ?>'><span class='icon-pencil' ></span>
    </a>
    <?php endif; ?>
  </td>
  <td>
    <!-- time -->
    <?php echo H_UiCells::getTimeCell($el); ?>
    <br/><br/>
  </td>
  <td>
    <!-- weight -->
    <?php 
    echo round($el->weight, 3) . " kg";
    ?>
  </td>
  <td>
    <!-- height -->
    <?php 
    if ($el->source == H_BodyRecord::SOURCE_USER && $el->height > 0.0) {
      echo round($el->height, 0) . " cm";
    }
    ?>
  </td>
  <td>
    <!-- activity -->
    <?php 
    if ($el->source == H_BodyRecord::SOURCE_USER) {
      if ($el->mul > 0.1) {
        echo round($el->mul, 3) . " x ";
        if ($show_activity_detail) {
          echo "<br/><small>".
            H_UiLang::getBMRMulDescription((float)$el->mul) . "</small>";
        }  
      }
    }
    else if ($el->source == H_BodyRecord::SOURCE_FIT) {
      echo H_UiLang::getFromGoogleFit();
    }
    ?>
  </td>
  <td>
    <!-- right side -->
  </td>
</tr>