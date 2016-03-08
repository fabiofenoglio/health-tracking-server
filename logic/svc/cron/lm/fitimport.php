<?php
defined("_JEXEC") or die();

class CronSvc_LmFitimport extends F_CronSvc
{
  public function execute($params)
  {
    if ($params !== null) $params = null;
    $call = $this->activity;

    $lock = F_SafetyLock::waitLock(H_FitData::LOCK_KEY, 60);
    try
    {
      $result = $this->___run();
    } 
    catch (Exception $ex) 
    {
      $call->addLog("runtime exception #0 : " . $ex->getMessage() . "", F_Log::ERROR);
      $result = null;
    }

    $lock->release();

    return $result;
  }

  private function ___run()
  {
    $call = $this->activity;
    $call->setStatus(F_CronHelper::RESULT_SUCCESS);
    $this->customReport = new JObject();
    $this->customReport->html = "";
    
    // Avoid timeout
    $startTime = time();
    $maxRunTime = 0.8 * ((int)ini_get('max_execution_time'));
    set_time_limit(0);
    
    // Get all users google-enabled
    $uncheckedUsers = $this->getToProcessList();
    
    $stats = new JObject();
    $stats->processed = 0;
    
    while (true) {
      // get more needful user
      $k = $this->getMoreNeedfulUserKey($uncheckedUsers);
      if (!$k) {
        break;
      }
      
      // process user
      try {
        $userInfo = $uncheckedUsers[$k];
        $results = $this->processOne($userInfo);  
        foreach ($results as $result) {
          $this->processResult($result, $userInfo);  
        }
      }
      catch (Exception $ex) {
        $call->addLog("runtime exception #1 : " . $ex->getMessage() . "", F_Log::ERROR);
        $call->setStatus(F_CronHelper::RESULT_WARNING);
      }
      
      // remove user from list
      unset($uncheckedUsers[$k]);
      $stats->processed ++;
      
      // check if no users left to do
      if(empty($uncheckedUsers)) {
        break;
      }
      
      // check if there is no time to safely run another one
      $stats->elapsedTime = time() - $startTime;
      $stats->avgProcessingTime = (float)$stats->elapsedTime / (float)$stats->processed;
      if ($stats->elapsedTime + ($stats->avgProcessingTime * 2.0) > $maxRunTime) {
        break;
      }
    }
  
    // write report
    $reportFile = JIF_PATH_HAL . "/data/fit_import_log.html";
    @unlink($reportFile);
    F_Io::writeToFile($reportFile, F_Io::MODE_RWCT, $this->customReport->html);
  }
  
  private function processResult($result, $userInfo) {
    $call = $this->activity;
    
    foreach ($result->errors as $e) {
      $call->addLog("runtime exception #2 : " . $e . "", F_Log::ERROR);
      $call->setStatus(F_CronHelper::RESULT_WARNING);
    }
    foreach ($result->weight->errors as $e) {
      $call->addLog("runtime exception #3 : " . $e . "", F_Log::ERROR);
      $call->setStatus(F_CronHelper::RESULT_WARNING);
    }
    foreach ($result->calories->errors as $e) {
      $call->addLog("runtime exception #4 : " . $e . "", F_Log::ERROR);
      $call->setStatus(F_CronHelper::RESULT_WARNING);
    }
    foreach ($result->activities->errors as $e) {
      $call->addLog("runtime exception #6 : " . $e . "", F_Log::ERROR);
      $call->setStatus(F_CronHelper::RESULT_WARNING);
    }
    
    if ($result->success) {
      $userInfo->data->set("last_google_fit_import", time());
    }
    
    /*
    if ($result->weight->changed) {
      F_UserNotify::send("google_fit_import_report_w", 
                         "Your Google Fit weight data has been synchronized",
                         $userInfo->userid);
    }
    if ($result->calories->changed) {
      F_UserNotify::send("google_fit_import_report_c", 
                         "Your Google Fit calories data has been synchronized",
                         $userInfo->userid);
    }
    if ($result->activities->changed) {
      F_UserNotify::send("google_fit_import_report_a", 
                         "Your Google Fit activities data has been synchronized",
                         $userInfo->userid);
    }
    */

    if (!$userInfo->store()) {
      $call->addLog("runtime exception #5 : " . $userInfo->getError() . "", F_Log::ERROR);
      $call->setStatus(F_CronHelper::RESULT_WARNING);
    }
  }
  
  private function processOne($userInfo) {
    $this->customReport->html .= "processing user " . $userInfo->getDisplayName() . "<br/>";
    
    $end = strtotime( date("Y-m-d", time) . " 23:59:59");

    if (($requestedStart = $userInfo->data->get("request_fit_import_from", null)) !== null) {
      unset($userInfo->data->request_fit_import_from);
      $start = $requestedStart;
    }
    else {
      $start = null;
    }
    
    $resW = H_IntegrationGoogleFit::refreshUserData(
      $userInfo->userid,
      $start ? $start : $end - (F_UtilsTime::A_DAY * H_FitData::IMPORT_BATCH_SIZE_DAYS_WEIGHT), 
      $end, 
      array(H_IntegrationGoogleFit::DATASRC_WEIGHT));
    
    $resC = H_IntegrationGoogleFit::refreshUserData(
      $userInfo->userid,
      $start ? $start : $end - (F_UtilsTime::A_DAY * H_FitData::IMPORT_BATCH_SIZE_DAYS_CALORIES), 
      $end, 
      array(H_IntegrationGoogleFit::DATASRC_CALORIES));
    
    $resA = H_IntegrationGoogleFit::refreshUserData(
      $userInfo->userid,
      $start ? $start : $end - (F_UtilsTime::A_DAY * H_FitData::IMPORT_BATCH_SIZE_DAYS_ACTIVITIES), 
      $end, 
      array(H_IntegrationGoogleFit::DATASRC_ACTIVITIES));
    
    $this->customReport->html .= "results: <br/>";
    $this->customReport->html .= "<pre>W:<br/>" . 
      var_export($resW, true) . "<br/><br/>C:<br/>" .
      var_export($resC, true) . "<br/>A:<br/>" .
      var_export($resA, true) . "</pre><br/><br/>";
    
    return array($resW, $resC, $resA);
  }
  
  private function getToProcessList() {
    $rawList = F_Table::loadClassList(H_UserInfo::CLASS_NAME);
    $returnList = array();
    
    foreach ($rawList as $userInfo) {
      if (!$userInfo->getGoogleAccessToken()) {
        continue;
      }
      $returnList[$userInfo->userid] = $userInfo;
    }
    
    $this->customReport->html .= "getToProcessList result: <br/>";
    $this->customReport->html .= "<pre>" . 
      var_export($returnList, true) . "</pre><br/><br/>";
    
    return $returnList;
  }

  private function getMoreNeedfulUserKey($list) {
    $result = null;
    foreach ($list as $k => $userInfo) {
      if ($result === null) {
        $result = $k;
        continue;
      }
      
      $last = $userInfo->data->get("last_google_fit_import", 0);
      if ($last < $list[$result]->data->get("last_google_fit_import", 0)) {
        $result = $k;
      }
    }
    
    return $result;
  }
}