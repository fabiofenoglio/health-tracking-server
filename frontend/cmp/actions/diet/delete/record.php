<?php defined("_JEXEC") or die();

$user = JFactory::getUser();

$input_fe = array();
$foodRecords = array();

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
  }
}

$deleted = 0;

foreach ($foodRecords as $record) {
  if ($record->id < 1) {
    continue;
  }
  
  if ($record->delete()) {
    $deleted ++;
  }
  else {
    F_Log::showError("error deleting item :(");
  }
}

if ($deleted > 0) {
  F_Log::showInfo("$deleted records deleted");
}