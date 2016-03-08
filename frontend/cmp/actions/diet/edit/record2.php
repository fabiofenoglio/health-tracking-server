<?php defined("_JEXEC") or die();

if (F_Input::exists("action-cancel"))
{
  // F_Log::showWarning("changes discarded");
  return;
}

if (F_Input::exists("action-delete"))
{
  F_SimplecomponentHelper::show("cmp.actions.diet.delete.record");
  return;
}

if (F_Input::exists("action-save"))
{
  $action = "save";
}
else
{
  F_Log::showError("unsupported operation request");
  return;
}

$user = JFactory::getUser();

$input_id = (int)F_Input::getInteger("id");
if (!$input_id) {
  // new object
  $obj = F_Table::create(H_FoodRecord::CLASS_NAME);
  $obj->userid = $user->id;
}
else {
  // load object
  $obj = H_FoodRecord::load(F_Input::getInteger("id"));
  if (!$obj) {
    F_Log::showError("requested record not found :(");
    return;
  }
}

if ((int)$obj->userid !== (int)$user->id) {
  F_Log::showError("you are not allowed >:[");
  return;
}

// save data
$obj->foodid =  F_Input::getInteger("foodid");
$obj->group =   F_Safety::getSanitizedInput("group", "", F_Safety::ALPHA_NUM_PT_SCORES_SLASH);
$obj->amount =  (float)F_Safety::getSanitizedInput("amount", "", F_Safety::NUM_PT);
$obj->time =    strtotime(F_Input::getRaw("time"));

if (!$obj->foodid) $obj->foodid = 0;

if ($obj->store()) {
  F_Log::showInfo("changes saved", "message");
}
else {
  F_Log::showError("error saving changes :(");
}