<?php defined("_JEXEC") or die();

$params = F_Snippet::getParams();
$display_time = $params->get("time", null);

if ($params->get("header") == true)
{
  echo "<tr>";
  echo "<th></th>";
  echo "<th>Group</th>";
  echo "<th>Time</th>";
  echo "<th>Description</th>";
  echo "<th>Amount</th>";
  echo "<th></th>";  
  echo "</tr>";
  return;
}

$el = $params->get("element");
if (!$el) die("param error");

$user = JFactory::getUser();
$edit_url = H_UiRouter::getEditMoneyRecordUrl($el->id);

?>
<tr>
  <td>
    <!-- left side -->
    <?php if ($el->source == H_Data::SOURCE_USER) : ?>
    <a href='<?php echo $edit_url; ?>'><span class='icon-pencil' ></span>
    </a>
    <?php endif; ?>
  </td>
  <td>
    <!-- group -->
    <small>
    <?php echo $el->group; ?>
    </small>
  </td>
  <td>
    <!-- time -->
    <?php  echo H_UiCells::getTimeCell($el); ?>
    <br/><br/>
  </td>
  <td>
    <!-- description -->
    <?php 
    echo strlen($el->name) > 0 ? $el->name : "???";
    ?>
  </td>
  <td>
    <!-- amount -->
    <?php 
    if ($el->amount != 0.0) {
      echo sprintf("%.2f €", $el->amount);
    }
    ?>
  </td>
  <td>
    <!-- right side -->
  </td>
</tr>