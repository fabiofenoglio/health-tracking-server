<?php defined("_JEXEC") or die();

if (F_Input::exists("action")) 
{
    $required_action = F_Safety::sanitize(F_Input::getRaw("action"), F_Safety::ALPHA_NUM_PT_SCORES);
    if (!empty($required_action) && F_Safety::verifyStrLen($required_action, 1, 100))
    {
      F_SimplecomponentHelper::show("cmp.actions." . $required_action);
      F_Input::delete("action");  
    }
}

$user = JFactory::getUser();

// Check last body record
$key = "last_body_record_check_user_" . $user->id;
if (H_UiScheduler::isTimeTo($key, F_UtilsTime::AN_HOUR)) {
  H_UiScheduler::markDone($key);
  
  $lastBodyRecord = H_BodyRecord::loadLast($user->id);
  if (! $lastBodyRecord) {
    F_Log::showWarning("I really need you to give me a body record. Please click on 'weight'");
  }
  else {
    if (time() - $lastBodyRecord->time >= H_BodyRecord::REQUIRE_MEASUREMENT_EVERY_DAYS * F_UtilsTime::A_DAY) {
      F_Log::showWarning("You have not weighted yourself for a long time. Please give we a weight measurement as soon as possible (click on 'Weight')");
    }
  }
}
