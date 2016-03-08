<?php defined("_JEXEC") or die();

if (F_Input::exists("action-cancel"))
{
  // F_Log::showWarning("changes discarded");
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
$class = H_BodyRecord::CLASS_NAME;
$user = JFactory::getUser();

// clone with default last values
$obj = H_BodyRecord::loadLast($user->id);
if (!$obj) {
  $obj = F_Table::create($class);
}
else {
  $obj->id = null;
}

$obj->userid = $user->id;
$obj->time = time();
$obj->source = H_BodyRecord::SOURCE_USER;
$obj->height = null;
$obj->mul = null;

// save data
$obj->weight =  (float)F_Safety::getSanitizedInput("if_weight", "", F_Safety::NUM_PT);

if ($obj->store()) {
  F_Log::showInfo("changes saved", "message");
}
else {
  F_Log::showError("error saving changes :(");
}