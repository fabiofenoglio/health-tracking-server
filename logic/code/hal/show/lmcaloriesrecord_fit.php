<?php defined("_JEXEC") or die();

$params = F_Snippet::getParams();
$display_time = $params->get("time", null);

if ($params->get("header") == true)
{
    echo "<tr>";
    echo "<th></th>";
    echo "<th>Time</th>";
    echo "<th>Calories</th>";
    echo "<th></th>";  
    echo "</tr>";
    return;
}

$el = $params->get("element");
if (!$el) die("param error");
$target = $params->get("target");

$user = JFactory::getUser();

?>
<tr>
  <td>
    <!-- left side -->
    </a>
  </td>
  <td>
    <!-- time -->
    <?php echo H_UiCells::getTimeCell($el); ?>
    <br/><br/>
  </td>
  <td>
    <!-- weight -->
    <?php 
    echo round($el->value, 0) . " kcal";
    ?>
    <br />
    <?php
    if ($el->value <= $target->energy) {
      $first = $el->value;
      $second = 0;
    }
    else {
      $first = $target->energy;
      $second = ($el->value - $target->energy);
    }
    ?>
    <progress min='0' max='<?php echo round($target->energy, 0); ?>' 
              value='<?php echo round($first, 0); ?>'></progress>
    <progress min='0' max='<?php echo round($target->energy, 0); ?>' 
              value='<?php echo round($second, 0); ?>'></progress>
  </td>
  <td>
    <!-- right side -->
  </td>
</tr>