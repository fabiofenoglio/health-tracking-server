<?php defined("_JEXEC") or die();

$params = F_Snippet::getParams();
$display_time = $params->get("time", null);

if ($params->get("header") == true)
{
    echo "<tr>";
    echo "<th></th>";
    echo "<th><small>Group</small></th>";
    echo "<th>Food</th>";
    echo "<th>Amount</th>";
    if ($display_time != "none") echo "<th>Time</th>";
    echo "<th></th>";
    echo "</tr>";
    return;
}

$el = $params->get("element");
if (!$el) die("param error");


$food = $el->foodid ? H_FoodInfo::load($el->foodid) : null;
$edit_url = H_UiRouter::getEditFoodRecordUrl($el->id);
$edit_food_url = H_UiRouter::getEditFoodInfoUrl($food->id);
$clone_url = H_UiRouter::getAddFoodRecordUrl($el->id);
$fast_clone_url = H_UiRouter::getCloneFoodRecordUrl($el->id);
$user = JFactory::getUser();
$serving_size = $food ? $food->serving_size : 0;
$unit_size = $food ? $food->unit_size : 0;
if (!empty($el->group)) {
  $edit_meal_url = H_UiRouter::getEditFoodGroupRecordUrl($el->group, $el->time);
}
else {
  $edit_meal_url = null;
}
?>
<tr>
  <td>
    <!-- left side -->
    <a href='<?php echo $edit_url; ?>'><span class='icon-pencil' ></span>
    </a>
  </td>
  <td>
    <!-- group -->
    <small>
    <?php 
      if ($edit_meal_url) {
        echo "<a href='$edit_meal_url'>";
      }
      echo $el->group; 
      echo "<br/><small>";
      echo date("H:i", $el->time);
      echo "</small>";
      if ($edit_meal_url) {
        echo "</a>";
      }
    ?>
    </small>
  </td>
  <td>
    <!-- food -->
    <?php echo $food ? 
      $food->getDisplayName()
      : 
      "<small>???</small>"; 
    ?>
  </td>
  <td>
    <!-- amount -->
    <?php 
    if ($el->amount > 0.0) {
      echo round($el->amount, 2) . " g";
      /*
      if ($serving_size  > 0) {
        echo "<br/><small>".
          round($el->amount / $serving_size, 2) .
          " serving size</small>";
      }
      */
      if ($unit_size > 0.0 /* && $unit_size != $serving_size */ ) {
        echo "<br/><small>".
          round($el->amount / $unit_size, 1) .
          " units</small>";
      }
    }
    else 
    {
      echo "unspecified";  
    }
    ?>
  </td>
  <?php if ($display_time != "none") { ?>
  <td>
    <!-- time -->
    <?php echo H_UiCells::getTimeCell($el); ?>
  </td>
  <?php } ?>
  <td>
    <!-- right side -->
    <?php if (true) { ?>    
    <!--
    <a href='<?php echo $fast_clone_url; ?>'>
      <span class='icon-plus' ></span>
      repeat</a>
    <br/>
    -->
    <a href='<?php echo $clone_url; ?>'>
      <span class='icon-plus' ></span>
      similar</a>
    <br/>
    <?php } ?>
    
    <?php if ($food && true || H_FoodInfo::userCanEdit($food, $user->id))  { ?>
    <a href='<?php echo $edit_food_url; ?>'><span class='icon-pencil' ></span>
      food</a>
    <br/>
    <?php } ?>
  </td>
</tr>