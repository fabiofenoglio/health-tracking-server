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

$user = JFactory::getUser();
$obj = H_UserInfo::loadCurrent();

// save data
$sex = H_UserInfo::SEX_UNKNOWN;

$inSex = F_Input::getRaw("if_sex");
if ($inSex == "m") {
  $sex = H_UserInfo::SEX_MALE;
}
else if ($inSex == "f") {
  $sex = H_UserInfo::SEX_FEMALE;
}
else if ($inSex == "u") {
  $sex = H_UserInfo::SEX_UNKNOWN;
}
else {
  $sex = null;
}

if ($sex !== null) {
  $obj->sex = $sex;  
}

$obj->birthdate = strtotime(F_Input::getRaw("if_birthdate"));

if ($obj->store()) {
  F_Log::showInfo("changes saved", "message");
}
else {
  F_Log::showError("error saving changes :(");
}