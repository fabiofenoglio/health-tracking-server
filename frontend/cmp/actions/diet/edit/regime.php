<?php defined("_JEXEC") or die();

if (F_Input::exists("action-cancel"))
{
  // F_Log::showWarning("changes discarded");
  return;
}

if (F_Input::exists("action-delete"))
{
  F_SimplecomponentHelper::show("cmp.actions.diet.delete.regime");
  return;
}

$user = JFactory::getUser();
$food_components = H_FoodComponent::loadUnorderedList();

$input_id = (int)F_Input::getInteger("id");
if (!$input_id) {
  // new object
  $obj = H_FoodRegime::create($foodComponents);
  $obj->userid = $user->id;
}
else {
  // load object
  $obj = H_FoodRegime::load(F_Input::getInteger("id"));
  if (!$obj) {
    F_Log::showError("requested regime not found :(");
    return;
  }
}

$user = JFactory::getUser();
if (!H_FoodRegime::userCanEdit($obj, $user->id)) {
  F_Log::showError("you are not allowed >:[");
  return;
}

if (F_Input::exists("action-pressed-on-set-active"))
{
  // activate this regime
  H_FoodRegime::activate($obj->id, $user->id);
  return;
}

if (! F_Input::exists("action-save"))
{
  F_Log::showError("unsupported operation request");
  return;
}

// save data
$obj->name = F_Input::getString("name");
$obj->data->description = F_Input::getString("description");

// search for all food components
foreach ($food_components as $food_component) {
  $if_monitor_key = "fc_monitor_" . $food_component->info_property;
  $if_value_key = "fc_" . $food_component->info_property;
  if (!F_Input::exists($if_value_key)) continue;
  
  $spec = new H_FoodRegimeComponentSpecification();
  $spec->goal_percentage = ((float)F_Input::getInteger($if_value_key, 100))/100.0;
  $spec->min_percentage = $spec->goal_percentage - 0.25;
  if ($spec->min_percentage < 0.0) $spec->min_percentage = 0.0;
  $spec->max_percentage = $spec->goal_percentage + 0.50;
  $spec->monitor = F_Input::getInteger($if_monitor_key, 0) ? true : false; 
  
  $obj->data->components[$food_component->id] = $spec;
}

if ($obj->store()) {
  F_Log::showInfo("changes saved", "message");
}
else {
  F_Log::showError("error saving changes :(");
}