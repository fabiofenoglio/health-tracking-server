<?php defined("_JEXEC") or die();

if (F_Input::exists("action-cancel"))
{
  // F_Log::showWarning("changes discarded");
  return;
}

if (F_Input::exists("action-delete"))
{
  F_SimplecomponentHelper::show("cmp.actions.body.delete.record");
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
  $obj = F_Table::create(H_BodyRecord::CLASS_NAME);
  $obj->userid = $user->id;
  $obj->source = H_BodyRecord::SOURCE_USER;
}
else {
  // load object
  $obj = H_BodyRecord::load(F_Input::getInteger("id"));
  if (!$obj || $obj->source != H_BodyRecord::SOURCE_USER) {
    F_Log::showError("requested record not found :(");
    return;
  }
}

if ((int)$obj->userid !== (int)$user->id) {
  F_Log::showError("you are not allowed >:[");
  return;
}

// save data
$obj->weight =  (float)F_Safety::getSanitizedInput("if_weight", "", F_Safety::NUM_PT);
$obj->height =  (float)F_Safety::getSanitizedInput("if_height", "", F_Safety::NUM_PT);
$obj->mul =     (float)( 1.0 + (2.0 - 1.0) * ((float)F_Input::getInteger("if_mul", 0)) / 1000.0 );
$obj->time =    strtotime(F_Input::getRaw("if_time"));

if ($obj->mul < 1.0 || $obj->mul > 2.5) {
  F_Log::showError("BMR multiplicator is not valid");
  return;
}

if ($obj->store()) {
  F_Log::showInfo("changes saved", "message");
}
else {
  F_Log::showError("error saving changes :(");
}