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
    echo round($el->value, 3) . " kg";
    ?>
    <br/><small>from Google Fit</small>
  </td>
  <td>
    <!-- height -->
    <?php 
    echo "";
    ?>
  </td>
  <td>
    <!-- activity -->
    <?php 
    echo "";
    ?>
  </td>
  <td>
    <!-- right side -->
  </td>
</tr>