<?php
defined("_JEXEC") or die();

class CronSvc_LmBackupper extends F_CronSvc
{
  public function execute($params)
  {
    if ($params !== null) $params = null;
    $call = $this->activity;

    try
    {
      $result = $this->___run();
    } 
    catch (Exception $ex) 
    {
      $call->addLog("runtime exception #0 : " . $ex->getMessage() . "", F_Log::ERROR);
      $result = null;
    }
    
    return $result;
  }

  private function ___run()
  {
    $call = $this->activity;
    $call->setStatus(F_CronHelper::RESULT_SUCCESS);
    
    // Avoid timeout
    $startTime = time();
    $maxRunTime = 0.8 * ((int)ini_get('max_execution_time'));
    set_time_limit(0);
    
    $classes = array(
      H_BodyRecord::CLASS_NAME,
      H_FitData::CLASS_NAME,
      H_FoodComponent::CLASS_NAME,
      H_FoodInfo::CLASS_NAME,
      H_FoodRecord::CLASS_NAME,
      H_FoodRegime::CLASS_NAME,
      H_UserInfo::CLASS_NAME
    );
    
    $folder = F_Temp::getReservedFolder((int)$maxRunTime * 2);
    if (!$folder) {
      $call->addLog(F_Temp::getError(), F_Log::ERROR);
      return false;
    }
    if (!$folder->valid) {
      $call->addLog($folder->getError(), F_Log::ERROR);
      return false;
    }
    
    $timeReached = false;
    
    foreach ($classes as $class) {
      if ($timeReached) break;
      $offset = 0;
      $perPage = 100;
      
      while (true) {
        $fileName = "".$offset;
        for ($i = strlen($fileName); $i < 10; $i ++)
          $fileName = "0".$fileName;
        $filePath = $folder->getAbsoluteFilePath("data/$class-$fileName.json", true);
        
        $where = "1";
        if ($class == H_FoodInfo::CLASS_NAME) {
          $where = "userid > 0";
        }
        $query = "SELECT * FROM " . F_Table::getClassTable($class) . 
          " WHERE $where ORDER BY id ASC" . 
          " LIMIT $offset, $perPage";
        
        $objects = F_Table::doQuery($query, null, F_Table::LOADMETHOD_OBJECT_LIST);
        if (empty($objects)) {
          break;
        }
        $count = count($objects);
        
        if (!F_Io::writeToFile($filePath, F_Io::MODE_WAC, json_encode($objects))) {
          $call->addLog(F_Io::getError(), F_Log::ERROR);
          return false;
        }
        unset($objects);
        
        if ($count < $perPage) {
          break;
        }
        
        $offset += $perPage;
        
        if ((time()-$startTime) >= ($maxRunTime * 0.8)) {
          $call->addLog("time limit reached", F_Log::ERROR);
          $timeReached = true;
          break;
        }
      }
    }
    
    // now zip it
    $filename = "dump_" . date("YmdHis") . ".zip";
    $file_target = $folder->getAbsoluteFilePath($filename);

    if (! $zip = F_IoZip::zipFolder($folder->getAbsoluteDirectory("data"), $file_target))
    {
      $call->addLog("error zipping results: " . F_IoZip::getError(), F_Log::ERROR);
      return F_CronHelper::RESULT_ERROR;
    }
   
    $dest = JIF_PATH_HAL . "/data/dump.zip";
    @unlink($dest);
    if (!@rename($file_target, $dest)) {
      $call->addLog("error moving file to permanent storage", F_Log::ERROR);
      return F_CronHelper::RESULT_ERROR;
    }
    
    if ($timeReached) return F_CronHelper::RESULT_ERROR;
    return true;
  }
}