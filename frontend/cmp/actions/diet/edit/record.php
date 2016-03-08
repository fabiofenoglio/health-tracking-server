<?php defined("_JEXEC") or die();

$copy = false;
$copy_button = false;

if (F_Input::exists("action-cancel")) {
  return;
}

if (F_Input::exists("action-delete")) {
  F_SimplecomponentHelper::show("cmp.actions.diet.delete.record");
  return;
}

if (F_Input::exists("action-save")) {
  $action = "save";
}
else if (F_Input::exists("action-copy")) {
  $action = "save";
  $copy = true;
  $copy_button = true;
}
else {
  F_Log::showError("unsupported operation request");
  return;
}

$user = JFactory::getUser();

$now = time();
$input_group = F_Safety::getSanitizedInput("if_group", "", F_Safety::ALPHA_NUM_PT_SCORES_SLASH);
$input_time = strtotime(F_Input::getRaw("if_time"));
$copy |= (F_Input::getInteger("if_copy", 0) ? true : false);

$input_fe = array();
$foodRecords = array();
$foodInfos = array();
$success = 0;
$deleted = 0;

// list records
foreach ($_REQUEST as $requestVarK => $requestVarV) {
  if (F_UtilsString::startsWith($requestVarK, "if_fe_")) {
    $expl = explode("_", substr($requestVarK, 6));
    if (count($expl) < 2) {
      continue;
    }
    
    $foodEntryId = $expl[0];
    
    if (!isset($input_fe[$foodEntryId])) {
      $input_fe[$foodEntryId] = new JObject();
    }
    
    $input_fe[$foodEntryId]->set(F_Safety::sanitize($expl[1], F_Safety::ALPHA_NUM_PT_SCORES_SLASH), $requestVarV);
    
    if ($expl[1] == "recordid" && !isset($foodRecords[$requestVarV]) && $requestVarV > 0) {
      $loaded = H_FoodRecord::load($requestVarV);
      $foodRecords[$requestVarV] = $loaded;
      if ($loaded->userid != $user->id) {
        return H_UiLang::notAllowed();
      }
    }
    else if ($expl[1] == "foodid" && !isset($foodInfos[$requestVarV])) {
      $loaded = H_FoodInfo::load($requestVarV);
      $foodInfos[$requestVarV] = $loaded;
      if ($loaded->userid != $user->id && $loaded->privacy != H_FoodInfo::PRIVACY_PUBLIC) {
        return H_UiLang::notAllowed();
      }
    }
  }
}

$revert_on_time = $now;
$revert_on_group = H_FoodRecord::guessGroupByTime($user->id);
$do_revert_on_time = false;

// check if user did not set a new time when copying
if ($copy) {
  foreach ($input_fe as $inputFood) {
    if ($inputFood->status == '0' || $inputFood->recordid < 1) {
      continue;
    }

    $record = $foodRecords[$inputFood->recordid];
    
    if (abs($record->time - $input_time) < 3600) {
      // probably user forgot to update time
      $do_revert_on_time = true;
      F_Log::showWarning("Records have been saved for the current time because similar records were already registered at the time you provided");
      break;
    }
  }
}

foreach ($input_fe as $inputFood) {
  if ($inputFood->recordid > 0) {
    $record = $foodRecords[$inputFood->recordid];
  }
  else {
    $record = H_FoodRecord::create();
  }
  
  if ($inputFood->status == '0') {
    // delete
    if (!$copy && $record->id > 0) {
      if (!$record->delete()) {
        F_Log::showError("Error deleting record: " . $record->getError());
      }
      else {
        $deleted ++;
      }
    }
    continue;
  }
  
  if ($copy) {
    $record->id = null;
  }
  
  if ($do_revert_on_time) {
    // probably user forgot to update time
    $record->time = $revert_on_time;
    $record->group = $revert_on_group;
  }
  else {
    $record->time = $input_time;
    $record->group = $input_group;
  }
  
  $record->userid = $user->id;
  $record->foodid = $inputFood->foodid;
  $record->amount = $inputFood->amount;
  
  if (!$record->store()) {
    F_Log::showError("Error saving record: " . $record->getError());
  }
  else {
    $success ++;
  }
}

if ($success > 0) {
  F_Log::showInfo("$success elements saved");
}
if ($deleted > 0) {
  F_Log::showInfo("$deleted elements deleted");
}