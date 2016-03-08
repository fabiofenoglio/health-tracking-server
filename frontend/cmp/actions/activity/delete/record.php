<?php defined("_JEXEC") or die();

$user = JFactory::getUser();

$input_id = (int)F_Input::getInteger("id");
if (!$input_id) {
  F_Log::showError("invalid request :(");
  return;
}

$obj = H_ActivityRecord::load(F_Input::getInteger("id"));
if (!$obj) {
  F_Log::showError("requested record not found :(");
  return;
}

if ((int)$obj->userid !== (int)$user->id) {
  F_Log::showError("you are not allowed >:[");
  return;
}

if ($obj->source != H_ActivityRecord::SOURCE_USER) {
  F_Log::showError("you are not allowed >:[");
  return;
}

if ($obj->delete()) {
  F_Log::showInfo("item deleted", "message");
}
else {
  F_Log::showError("error deleting item :(");
}