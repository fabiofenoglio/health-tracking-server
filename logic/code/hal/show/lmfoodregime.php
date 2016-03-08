<?php defined("_JEXEC") or die();

$params = F_Snippet::getParams();

if ($params->get("header") == true)
{
  echo "<tr>";
  echo "<th></th>";
  echo "<th>Regime</th>";
  echo "<th></th>";
  echo "<th>Status</th>";
  echo "<th></th>";
  echo "</tr>";
  return;
}

$el = $params->get("element");
if (!$el) die("param error");

$user = JFactory::getUser();
$edit_url = H_UiRouter::getEditFoodRegimeUrl($el->id);
$clone_url = H_UiRouter::getAddFoodRegimeUrl($el->id);

$is_active = ($el->status & H_FoodRegime::STATUS_ACTIVE);
$food_components = H_FoodComponent::loadOrderedListCached();
?>
<tr <?php if ($is_active) { echo 'class="success"'; } ?>>
  <td>
    <!-- left side -->
    <?php if (true)  { ?>
    <a href='<?php echo $edit_url; ?>'><span class='icon-pencil' ></span>
      </a>
    <?php } ?>
  </td>
  <td>
    <!-- regime -->
    <?php 
    if ($is_active) echo "<strong>";
    echo $el->name ? 
      preg_replace("/,([^\s])/", ", $1", $el->name)
      : 
      "no name"; 
    if ($is_active) echo "</strong>";
    if ($is_active) {
      echo "<br/><small><font color='#33AA33'>currently active</font></small>";
    }
    ?>
  </td>
  <td>
    <!-- components -->
    <small>
    <?php 
    foreach ($food_components as $food_component) {
      if (!isset($el->data->components[$food_component->id])) continue;
      $component_spec = $el->data->components[$food_component->id];
      
      if (! $component_spec->monitor) continue;
      if ((float)$component_spec->goal_percentage == 1.0) continue;
      echo "<small>" . $food_component->display_name .
        " " . ($component_spec->goal_percentage > 1.0 ? "+" : "") .
        (100.0 * ($component_spec->goal_percentage - 1.0)) . " %" .
        " </small><br/>";
    }
    ?>
    </small>
  </td>
  <td>
    <!-- status -->
    <?php  if ($is_active) { ?>
      <button name="action-pressed-on-active" 
              class="btn btn-success" disabled="disabled">
        active</button>
    <?php }
    else { ?>
    <form action="<?php echo H_UiRouter::getBackto(F_SimplecomponentHelper::getUrl("diet.list.regimes")); ?>" method="post">
      <input type="hidden" name="action" value="diet.edit.regime" />
      <input type="hidden" name="id" value="<?php echo $el->id; ?>" />
      <button type="submit" name="action-pressed-on-set-active" 
              class="btn">idle</button>
     </form>
    <?php } ?>
  </td>
  <td>
    <!-- right side -->
    <a href='<?php echo $clone_url; ?>'>
      <span class='icon-plus' ></span>
      </a>
    <br/>
  </td>
</tr>