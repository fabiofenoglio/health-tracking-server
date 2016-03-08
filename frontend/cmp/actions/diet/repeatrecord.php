<?php defined("_JEXEC") or die();

$user = JFactory::getUser();

$input_id = (int)F_Input::getInteger("fast_clone");
if (!$input_id) {
  F_Log::showError("invalid request");
  return;
}

$obj = H_FoodRecord::load($input_id);
if (!$obj) {
  F_Log::showError("requested record not found :(");
  return;
}

if ((int)$obj->userid !== (int)$user->id) {
  F_Log::showError("you are not allowed >:[");
  return;
}

// save data
$obj->id = null;
$obj->time = time();

if ($obj->store()) {
  F_Log::showInfo("element cloned", "message");
}
else {
  F_Log::showError("error cloning element :(");
}