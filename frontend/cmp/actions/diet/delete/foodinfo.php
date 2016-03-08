<?php defined("_JEXEC") or die();

$user = JFactory::getUser();

$input_id = (int)F_Input::getInteger("id");
if (!$input_id) {
  F_Log::showError("invalid request :(");
  return;
}

$obj = H_FoodInfo::load(F_Input::getInteger("id"));
if (!$obj) {
  F_Log::showError("requested item not found :(");
  return;
}

if (!H_FoodInfo::userCanEdit($obj, $user->id)) {
  F_Log::showError("you are not allowed >:[");
  return;
}

H_Caching::invalidateBatch("food.info");

if ($obj->delete()) {
  F_Log::showInfo("item deleted", "message");
}
else {
  F_Log::showError("error deleting item :(");
}