<?php defined("_JEXEC") or die();

if (F_Input::exists("action-cancel"))
{
  // F_Log::showWarning("changes discarded");
  return;
}

if (F_Input::exists("action-delete"))
{
  F_SimplecomponentHelper::show("cmp.actions.diet.delete.foodinfo");
  return;
}

if (! F_Input::exists("action-save"))
{
  F_Log::showError("unsupported operation request");
  return;
}

$user = JFactory::getUser();

$input_id = (int)F_Input::getInteger("id");
if (!$input_id) {
  // new object
  $obj = F_Table::create(H_FoodInfo::CLASS_NAME);
  $obj->userid = $user->id;
  $obj->privacy = H_FoodInfo::PRIVACY_PRIVATE;
}
else {
  // load object
  $obj = H_FoodInfo::load(F_Input::getInteger("id"));
  if (!$obj) {
    F_Log::showError("requested food info not found :(");
    return;
  }
}

$user = JFactory::getUser();

if (!H_FoodInfo::userCanView($obj, $user->id)) {
  F_Log::showError("you are not allowed >:[");
  return;
}

$canEdit = H_FoodInfo::userCanEdit($obj, $user->id);

H_Caching::invalidateBatch("food.info");

$name = F_Input::getString("name");

if (!$canEdit) {
  $obj->id = null;
  $obj->userid = $user->id;
  
  if ($name) {
    $name = $name . " (" .
      H_UserInfo::loadCurrent()->getDisplayName() . "'s customized)";
  }
}

// save data
$obj->name = $name;
$obj->description =   F_Input::getString("description");
$obj->group =   F_Safety::getSanitizedInput("group", "", F_Safety::ALPHA_NUM_PT_SCORES_SLASH);
$obj->serving_size =  (float)F_Safety::getSanitizedInput("serving_size", "", F_Safety::NUM_PT);
$obj->unit_size =  (float)F_Safety::getSanitizedInput("unit_size", "", F_Safety::NUM_PT);
$obj->privacy = F_Input::getInteger("private", 0) ? H_FoodInfo::PRIVACY_PRIVATE : H_FoodInfo::PRIVACY_PUBLIC;

// search for all food components
$food_components = H_FoodComponent::loadUnorderedListCached();
foreach ($food_components as $food_component) {
  $inputKey = "fc_" . $food_component->info_property;
  if (!F_Input::exists($inputKey)) continue;
  $obj->set($food_component->info_property, (float)F_Safety::getSanitizedInput($inputKey, "0.0", F_Safety::NUM_PT));
}

if ($obj->store()) {
  F_Log::showInfo("changes saved", "message");
}
else {
  F_Log::showError("error saving changes :(");
}