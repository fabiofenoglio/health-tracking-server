<?php defined("_JEXEC") or die();

if (F_Input::exists("action-cancel"))
{
  // F_Log::showWarning("changes discarded");
  return;
}

if (F_Input::exists("action-delete"))
{
  F_SimplecomponentHelper::show("cmp.actions.activity.delete.record");
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
  $obj = F_Table::create(H_ActivityRecord::CLASS_NAME);
  $obj->userid = $user->id;
  $obj->source = H_ActivityRecord::SOURCE_USER;
}
else {
  // load object
  $obj = H_ActivityRecord::load(F_Input::getInteger("id"));
  if (!$obj || $obj->source != H_ActivityRecord::SOURCE_USER) {
    F_Log::showError("requested record not found :(");
    return;
  }
}

if ((int)$obj->userid !== (int)$user->id) {
  F_Log::showError("you are not allowed >:[");
  return;
}

// save data
$obj->activity = F_Input::getRaw("if_activity", "???");
$obj->duration = F_Input::getInteger("if_duration_h", 0) * 3600 + F_Input::getInteger("if_duration_m", 0) * 60;
$obj->calories = (float)F_Safety::getSanitizedInput("if_calories", "", F_Safety::NUM_PT);
$obj->relative_fatigue = F_Input::getInteger("if_relative_fatigue", 100);
$obj->time = strtotime(F_Input::getRaw("if_time"));
$obj->data->note = F_Input::getRaw("if_note", null);
if ($obj->data->note == "") $obj->data->note = null;

if ($obj->store()) {
  F_Log::showInfo("changes saved", "message");
}
else {
  F_Log::showError("error saving changes :(");
}