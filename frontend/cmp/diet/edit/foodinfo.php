<?php defined("_JEXEC") or die();

$class = H_FoodInfo::CLASS_NAME;
$user = JFactory::getUser();

if (F_Input::exists("id"))
{
    if (!($obj = F_Table::loadClass($class, array("id" => F_Input::getInteger("id", 0)))))
    {
        echo "<p class='error'>invalid ID</p>";
        return;
    }
}
else if (F_Input::exists("clone")) {
  if (!($obj = F_Table::loadClass($class, array("id" => F_Input::getInteger("clone", 0)))))
  {
      echo "<p class='error'>invalid ID</p>";
      return;
  }
  if (!H_FoodInfo::userCanView($obj, $user->id)) {
    F_Log::showError("you are not allowed >:[");
    return;
  }

  $obj->id = null;
  $obj->userid = $user->id;
  $obj->privacy = H_FoodInfo::PRIVACY_PRIVATE;
}
else
{
  $obj = F_Table::create($class);
  $obj->userid = $user->id;
  $obj->privacy = H_FoodInfo::PRIVACY_PRIVATE;
}

if (!H_FoodInfo::userCanView($obj, $user->id)) {
  F_Log::showError("you are not allowed >:[");
  return;
}

$canEdit = H_FoodInfo::userCanEdit($obj, $user->id);

// load attribute list
$foodComponents = H_FoodComponent::loadOrderedList();

function writeActionButtons($canEdit) {
  if ($canEdit) {
  ?>
  <div class="form-actions">
    <button type="submit" name="action-save" class="btn btn-primary">Save</button>
    <button type="submit" name="action-cancel" class="btn">Cancel</button>
    <button type="submit" name="action-delete" class="btn btn-danger" style="float:right;">
      Delete</button>
  </div>
  <?php
  }
  else {
  ?>
  <div class="form-actions">
    <button type="submit" name="action-save" class="btn btn-primary">Copy to my list</button>
    <button type="submit" name="action-cancel" class="btn">Back</button>
  </div>
  <?php
  }
}

?>
<form action="<?php echo H_UiRouter::getBackto(H_UiRouter::getMyFoodInfosUrl()); ?>" method="post">
  <?php writeActionButtons($canEdit); ?>
  
<table class='table table-noborders'>
  <input type="hidden" name="action" value="diet.edit.foodinfo" />
  <input type="hidden" name="id" value="<?php echo $obj->id ? $obj->id : 0; ?>" />
  
  <tr>
      <td>Name</td>
      <td>
        <input class="input input-large" type="text" placeholder="food name" id="name-field" name="name" value="<?php echo htmlspecialchars($obj->name); ?>" />
        <br/>
      </td>
  </tr>
  <tr>
      <td>Description</td>
      <td>
        <input class="input input-large" type="text" placeholder="food description" id="description-field" name="description" value="<?php echo htmlspecialchars($obj->description); ?>" />
        <br/>
      </td>
  </tr>
  <tr>
      <td>Group</td>
      <td>
        <input class="input" type="text" id="group-field" placeholder="group" name="group" value="<?php echo $obj->group; ?>" />
          <small><div id="group-suggestion"></div></small>
        <br/>
      </td>
  </tr>
  
  <tr>
      <td>Serving size</td>
      <td>
        <div class="input-append">
          <input class="input" type="text" name="serving_size" id="serving-size-field" value="<?php echo $obj->serving_size; ?>" />
          <span class="add-on">grams</span>
        </div>
        <br/>
      </td>
  </tr>
  
  <tr>
      <td>Unit size</td>
      <td>
        <div class="input-append">
          <input class="input" type="text" name="unit_size" id="unit-size-field" value="<?php echo $obj->unit_size; ?>" />
          <span class="add-on">grams</span>
        </div>
        <br/>
      </td>
  </tr>
  
  <tr>
      <td>Privacy</td>
      <td>        
          <input class="input" type="checkbox" name="private" id="private-field" value="1" <?php 
                 echo $obj->privacy == H_FoodInfo::PRIVACY_PRIVATE ? "checked=1" : ""; ?> />
          Make this private
        <br/>
        <small>Only you can see this food info if you check this.</small>
        <br/>
      </td>
  </tr>
  
  <tr>
      <td>Component data</td>
      <td>
        <small>
          Please insert the following component data <b>about 100g of product</b>, according to the displayed measure unit
        </small>
        <br/>
      </td>
  </tr>
  <?php foreach ($foodComponents as $foodComponent) { 
  $in_field_val = $obj->get($foodComponent->info_property, 0.0);
  $in_field_val = (float)$in_field_val;
  if ($in_field_val <= 0.0) {
    $in_field_val = "";
  }
  ?>
  <tr>
      <td><?php echo $foodComponent->display_name; ?></td>
      <td>
        <div class="input-append">
          <input class="input" type="text" name="fc_<?php echo $foodComponent->info_property; ?>" id="fc-field-<?php echo $foodComponent->info_property; ?>" value="<?php echo $in_field_val; ?>" />
          <span class="add-on"><?php echo $foodComponent->unit; ?></span>
        </div>
        <br/>
      </td>
  </tr>
  <?php } ?>
</table>
  
  <?php writeActionButtons($canEdit); ?>
</form>

<script>
function setFoodGroup(group) {
  jQuery("#group-field")[0].value = group;
}

jQuery.ajax("<?php echo F_Service::getRequestUrl("lm.food.getinfogroups"); ?>", 
      {"dataType": "json"} 
  )
  .done(function(data) {
    if (data.error || !data.list.length) return;
  
    var sugg = "";
    for (var i = 0; i < data.list.length; i++) {
      if (!data.list[i]) continue;
      sugg += "<a class='btn btn-small' onclick='javascript:setFoodGroup(\"" + escape(data.list[i]) + "\");'>" + escape(data.list[i]) + "</a> ";
    }
    sugg = sugg.substring(0, sugg.length - 1);

    jQuery("#group-suggestion").html(sugg);
  });
</script>
