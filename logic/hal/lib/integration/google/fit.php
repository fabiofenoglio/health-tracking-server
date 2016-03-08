<?php

class H_IntegrationGoogleFit extends F_BaseStatic
{
  /*
  TODO
  find and delete data deleted from google fit during updates
  */
  
  const DATASRC_WEIGHT = "derived:com.google.weight:com.google.android.gms:merge_weight";
  const DATASRC_CALORIES = "derived:com.google.calories.expended:com.google.android.gms:merge_calories_expended";
  const DATASRC_ACTIVITIES = "derived:com.google.activity.segment:com.google.android.gms:merge_activity_segments";
  
  public static function refreshUserData($userId, $start, $end, $what = null) {
    if (!$what) {
      $what = array(self::DATASRC_WEIGHT, self::DATASRC_CALORIES, self::DATASRC_ACTIVITIES);
    }
    
    if (!($client = H_IntegrationGoogle::getClient($userId))) {
      self::setError(H_IntegrationGoogle::getError());
      return null;
    }
    
    $result = new JObject();
    $result->errors = array();
    $result->success = true;
    
    try {
      if (in_array(self::DATASRC_CALORIES, $what)) {
        $caloriesData = self::requestUserCaloriesData($userId, $start, $end, $client);
        $result->calories = self::writeUserCaloriesData($userId, $start, $end, $caloriesData);
      }
      else {
        $result->calories = null;
      }
      
      if (in_array(self::DATASRC_WEIGHT, $what)) {
        $weightData = self::requestUserWeightData($userId, $start, $end, $client);
        $result->weight = self::writeUserWeightData($userId, $start, $end, $weightData);
      }
      else {
        $result->weight = null;
      }
      
      if (in_array(self::DATASRC_ACTIVITIES, $what)) {
        $actData = self::requestUserActivitiesData($userId, $start, $end, $client);
        $result->activities = self::writeUserActivitiesData($userId, $start, $end, $actData);
      }
      else {
        $result->activities = null;
      }
    }
    catch (Exception $e) {
      self::setError($e->getMessage());
      $result->errors[] = $e->getMessage();
      $result->success = false;
    }
    
    return $result;
  }
  
  
  private static function writeUserActivitiesData(
      $userId, $rawStartTime, $rawEndTime, $data) 
  {
    $result = new JObject();
    $result->errors = array();
    $result->added = 0;
    $result->uptodate = 0;
    $result->updated = 0;
    $result->startTime = time();
    
    $startTime = strtotime(date("Y-m-d", $rawStartTime) . ' 00:00:00');
    $endTime = strtotime(date("Y-m-d", $rawEndTime) . ' 23:59:59');
    $where = "userid=$userId AND time>=$startTime AND time<=$endTime AND source=".H_ActivityRecord::SOURCE_FIT;
    
    // not indexing because there could be multiple activities at the same time
    $currentList = H_ActivityRecord::query($where, "time ASC");
    
    foreach ($data->activities as $str_day => $record_array) {
      $int_day = strtotime($str_day . ' 00:00:00');
      
      foreach ($record_array as $record) {
        // search for a corresponding record
        $currentRecord = null;
        $currentRecordKey = null;
        foreach ($currentList as $k => $currentListRecord) {
          if ($currentListRecord->time == $record->time && 
              $currentListRecord->data->get("activity_code", -1) == $record->activityCode) {
            $currentRecord = $currentListRecord;
            $currentRecordKey = $k;
            break;
          } 
        }
        if ($currentRecordKey !== null) {
          unset($currentList[$currentRecordKey]);
        }
        
        if (!$currentRecord) {
          // no data currently in the database
          $new = H_ActivityRecord::create();
          $new->userid = $userId;
          $new->source = H_ActivityRecord::SOURCE_FIT;
          $new->time = $record->time;
          $new->duration = $record->duration;
          $new->data->activity_code = $record->activityCode;
          $new->data->edit_time = $record->editTime;
          
          if (!$new->store()) {
            $result->errors[] = $new->getError();
          }
          else {
            $result->added ++;
          }
        }
        else {
          // data already in database, check if the same

          if ($currentRecord->duration == $record->duration &&
              $currentRecord->data->edit_time == $record->editTime) {
            // up to date
            $result->uptodate ++;
          }
          else {
            // needs updating
            $currentRecord->duration = $record->duration;
            $currentRecord->data->edit_time = $record->editTime;
            
            if (!$currentRecord->store()) {
              $result->errors[] = $currentRecord->getError();
            }
            else {
              $result->updated ++;
            }
          }
        }
      }
    }
    
    $result->endTime = time();
    $result->changed = $result->updated + $result->added;
    return $result;
  }
  
  private static function writeUserWeightData(
      $userId, $rawStartTime, $rawEndTime, $data) 
  {
    $result = new JObject();
    $result->errors = array();
    $result->added = 0;
    $result->uptodate = 0;
    $result->updated = 0;
    $result->startTime = time();
    
    $startTime = strtotime(date("Y-m-d", $rawStartTime) . ' 00:00:00');
    $endTime = strtotime(date("Y-m-d", $rawEndTime) . ' 23:59:59');
    $where = "userid=$userId AND time>=$startTime AND time<=$endTime AND source=".H_BodyRecord::SOURCE_FIT;
    
    $currentList = H_BodyRecord::query($where, "time ASC", "time");
    
    foreach ($data->weight_log as $str_day => $record_array) {
      $int_day = strtotime($str_day . ' 00:00:00');
      
      // compute average record
      $sum = 0.0;
      $cnt = 0;
      foreach ($record_array as $record) {
        $sum += (float)$record[0];
        $cnt ++;
      }
      $sum /= $cnt;
      
      if (!isset($currentList[$int_day])) {
        // no data currently in the database
        $new = H_BodyRecord::create();
        $new->userid = $userId;
        $new->source = H_BodyRecord::SOURCE_FIT;
        $new->time = $int_day;
        $new->weight = $sum;
        if (!$new->store()) {
          $result->errors[] = $new->getError();
        }
        else {
          $result->added ++;
        }
      }
      else {
        // data already in database, check if the same
        $current = $currentList[$int_day];
        if (abs((float)$current->weight - (float)$sum) < 0.01) {
          // up to date
          $result->uptodate ++;
        }
        else {
          // needs updating
          $current->weight = $sum;
          if (!$current->store()) {
            $result->errors[] = $current->getError();
          }
          else {
            $result->updated ++;
          }
        }
      }
    }
    
    $result->endTime = time();
    $result->changed = $result->updated + $result->added;
    return $result;
  }
  
  private static function writeUserCaloriesData(
      $userId, $rawStartTime, $rawEndTime, $data) 
  {
    $result = new JObject();
    $result->errors = array();
    $result->added = 0;
    $result->uptodate = 0;
    $result->updated = 0;
    $result->startTime = time();
    
    $startTime = strtotime(date("Y-m-d", $rawStartTime) . ' 00:00:00');
    $endTime = strtotime(date("Y-m-d", $rawEndTime) . ' 23:59:59');
    $where = "userid=$userId AND date>=$startTime AND date<=$endTime AND type=".H_FitData::TYPE_CALORIES;
    
    $currentList = H_FitData::query($where, "date ASC", "date");
    
    foreach ($data->calories_log as $str_day => $total) {
      $int_day = strtotime($str_day . ' 00:00:00');
      if (!isset($currentList[$int_day])) {
        // no data currently in the database
        $new = H_FitData::create();
        $new->userid = $userId;
        $new->type = H_FitData::TYPE_CALORIES;
        $new->date = $int_day;
        $new->value = $total;
        if (!$new->store()) {
          $result->errors[] = $new->getError();
        }
        else {
          $result->added ++;
        }
      }
      else {
        // data already in database, check if the same
        $current = $currentList[$int_day];
        if (abs((float)$current->value - (float)$total) < 0.1) {
          // up to date
          $result->uptodate ++;
        }
        else {
          // needs updating
          $current->value = $total;
          if (!$current->store()) {
            $result->errors[] = $current->getError();
          }
          else {
            $result->updated ++;
          }
        }
      }
    }
    
    $result->endTime = time();
    $result->changed = $result->updated + $result->added;
    return $result;
  }
  
  private static function requestUserCaloriesData(
      $userId, $rawStartTime, $rawEndTime, $client) 
  {
    $startTime = strtotime(date("Y-m-d", $rawStartTime) . ' 00:00:00');
    $endTime = strtotime(date("Y-m-d", $rawEndTime) . ' 23:59:59');
    $reqStartTime = $startTime - F_UtilsTime::A_DAY;
    $reqEndTime = $endTime + F_UtilsTime::A_DAY;
    
    $stat = new JObject();
    $stat->userid = $userId;
    $stat->calories_log = array();
    $stat->startTime = time();
    $stat->datasets = 0;
    
    $service = new Google_Service_Fitness($client);
    
    $listDatasets = $service->users_dataSources_datasets->get(
      "me", 
      self::DATASRC_CALORIES, 
      $reqStartTime.'000000000'.'-'.$reqEndTime.'000000000'
    );
    
    while($listDatasets->valid()) {
      if (($dataSet = $listDatasets->next()) === false) continue;
      $stat->datasets ++;
      $values = $dataSet["value"];
      $sTime = (int)($dataSet["startTimeNanos"] / 1000000000);
      $eTime = (int)($dataSet["endTimeNanos"] / 1000000000);
      $sDay = date("Y-m-d", $sTime);
      $eDay = date("Y-m-d", $eTime);

      if (empty($values)) continue;

      $values_partial = 0.0;

      foreach ($values as $value) {
        $values_partial += (float)$value["fpVal"];
      }

      if ($sDay != $eDay) {
        $tokStart = $sTime;
        $tokEnd = strtotime($sDay . " 00:00:00") + F_UtilsTime::A_DAY;
        $calc_time_total = $eTime - $sTime;
        
        while (true) {
          $pc = ($tokEnd - $tokStart) / ($calc_time_total);
          if ($pc > 0.0) {
            $tokStartDayStr = date("Y-m-d", $tokStart);
            if (!isset($stat->calories_log[$tokStartDayStr])) $stat->calories_log[$tokStartDayStr] = 0.0;
            $stat->calories_log[$tokStartDayStr] += $values_partial * $pc;
          }
          
          if ($tokEnd == $eTime) {
            break;
          }
          
          $tokStart = $tokEnd;
          $tokEnd += F_UtilsTime::A_DAY;
          if ($tokEnd > $eTime) {
            $tokEnd = $eTime;
          }
        }
      }
      else {
        if (!isset($stat->calories_log[$sDay])) $stat->calories_log[$sDay] = 0.0;
        $stat->calories_log[$sDay] += $values_partial;
      }
    }

    $to_remove = array();
    foreach ($stat->calories_log as $day => $value) {
      $day_int = strtotime($day . " 00:00:00");
      if ($day_int < $startTime) $to_remove[] = $day;
      else if ($day_int > $endTime) $to_remove[] = $day;
    }
    foreach ($to_remove as $key) {
      unset($stat->calories_log[$key]);
    }
    
    $stat->endTime = time();
    return $stat;
  }

  private static function requestUserWeightData(
      $userId, $rawStartTime, $rawEndTime, $client) 
  {
    $startTime = strtotime(date("Y-m-d", $rawStartTime) . ' 00:00:00');
    $endTime = strtotime(date("Y-m-d", $rawEndTime) . ' 23:59:59');
    $reqStartTime = $startTime - F_UtilsTime::A_DAY;
    $reqEndTime = $endTime + F_UtilsTime::A_DAY;
    
    $stat = new JObject();
    $stat->userid = $userId;
    $stat->weight_log = array();
    $stat->startTime = time();
    $stat->datasets = 0;
    
    $service = new Google_Service_Fitness($client);
    
    $listDatasets = $service->users_dataSources_datasets->get(
      "me", 
      self::DATASRC_WEIGHT, 
      $reqStartTime.'000000000'.'-'.$reqEndTime.'000000000'
    );
    
    while($listDatasets->valid()) {
      if (($dataSet = $listDatasets->next()) === false) continue;
      $stat->datasets ++;
      $values = $dataSet["value"];
      $sTime = (int)($dataSet["startTimeNanos"] / 1000000000);
      $sDay = date("Y-m-d", $sTime);

      if (empty($values)) continue;

      foreach ($values as $value) {
        $logVoice = array((float)$value["fpVal"], (int)$sTime);
        if (!isset($stat->weight_log[$sDay])) $stat->weight_log[$sDay] = array();
        array_push($stat->weight_log[$sDay], $logVoice);
      }
    }

    $to_remove = array();
    foreach ($stat->weight_log as $day => $values_array) {
      $day_int = strtotime($day . " 00:00:00");
      if ($day_int < $startTime) $to_remove[] = $day;
      else if ($day_int > $endTime) $to_remove[] = $day;
    }
    foreach ($to_remove as $key) {
      unset($stat->weight_log[$key]);
    }
    
    $stat->endTime = time();
    return $stat;
  }
  
  private static function requestUserActivitiesData(
      $userId, $rawStartTime, $rawEndTime, $client) 
  {
    $startTime = strtotime(date("Y-m-d", $rawStartTime) . ' 00:00:00');
    $endTime = strtotime(date("Y-m-d", $rawEndTime) . ' 23:59:59');
    $reqStartTime = $startTime - F_UtilsTime::A_DAY;
    $reqEndTime = $endTime + F_UtilsTime::A_DAY;
    
    $stat = new JObject();
    $stat->userid = $userId;
    $stat->activities = array();
    $stat->startTime = time();
    $stat->datasets = 0;
    
    $service = new Google_Service_Fitness($client);
    
    $listDatasets = $service->users_dataSources_datasets->get(
      "me", 
      self::DATASRC_ACTIVITIES, 
      $reqStartTime.'000000000'.'-'.$reqEndTime.'000000000'
    );
    
    while($listDatasets->valid()) {
      if (($dataSet = $listDatasets->next()) === false) continue;
      $stat->datasets ++;
      $values = $dataSet["value"];
      $sTime = (int)($dataSet["startTimeNanos"] / 1000000000);
      $sDay = date("Y-m-d", $sTime);

      try {
        $actCode = $values[0]["intVal"];  
      }
      catch (Exception $e) {
        $actCode = H_IntegrationGoogleFitActivity::ID_INVALID;
        continue;
      }

      if (H_IntegrationGoogleFitActivity::codeCanBeSkipped($actCode)) {
        continue;
      }
      if (empty($values)) {
        continue;
      }

      $logVoice = new JObject();
      $logVoice->activityCode = $actCode;
      $logVoice->time = $sTime;
      $logVoice->duration = (int)($dataSet["endTimeNanos"] / 1000000000) - $sTime;
      $logVoice->editTime = (int)($dataSet["modifiedTimeMillis"] / 1000);
      if (!isset($stat->activities[$sDay])) 
        $stat->activities[$sDay] = array();

      array_push($stat->activities[$sDay], $logVoice);
      /* $logVoice :
      object(JObject)#314 (5) {
        ["_errors":protected]=>
        array(0) {
        }
        ["activityCode"]=>
        int(44)
        ["time"]=>
        int(1452186000)
        ["duration"]=>
        int(9000)
        ["editTime"]=>
        int(1452200044)
      }
      */
    }

    $to_remove = array();
    foreach ($stat->activities as $day => $values_array) {
      $day_int = strtotime($day . " 00:00:00");
      if ($day_int < $startTime) $to_remove[] = $day;
      else if ($day_int > $endTime) $to_remove[] = $day;
    }
    foreach ($to_remove as $key) {
      unset($stat->activities[$key]);
    }
    
    $stat->endTime = time();
    return $stat;
  }
}
