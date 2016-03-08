<?php defined("_JEXEC") or die();

$class = H_MoneyRecord;

if (F_Input::exists("action-cancel"))
{
  // F_Log::showWarning("changes discarded");
  return;
}

if (F_Input::exists("action-delete"))
{
  F_SimplecomponentHelper::show("cmp.actions.money.delete.record");
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
  $obj = $class::create();
  $obj->userid = $user->id;
  $obj->source = H_Data::SOURCE_USER;
}
else {
  // load object
  $obj = $class::load(F_Input::getInteger("id"));
  if (!$obj || $obj->source != H_Data::SOURCE_USER) {
    return H_UiLang::notFound();
  }
}

if ((int)$obj->userid !== (int)$user->id) {
  return H_UiLang::notAllowed();
}

// save data
$obj->name =    F_Input::getRaw("if_name", "???");
$obj->group =   F_Input::getRaw("if_group", "???");
$obj->amount =  (float)F_Safety::getSanitizedInput("if_amount", "", F_Safety::NUM_PT);
$obj->time =    strtotime(F_Input::getRaw("if_time"));
$obj->data->note = F_Input::getRaw("if_note", null);
if ($obj->data->note == "") $obj->data->note = null;

if ($obj->store()) {
  F_Log::showInfo("changes saved", "message");
}
else {
  F_Log::showError("error saving changes :(");
}