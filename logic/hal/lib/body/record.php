<?php

class H_BodyRecord extends H_BaseTablehelper
{
  const CLASS_NAME = "lmbodyrecord";
  
  const AVERAGE_WEIGHT_PERIOD_DAYS = 120;
  const AVERAGE_WEIGHT_EMIDW = 3.77;
  const AVERAGE_WEIGHT_CLUSTERING = 15.35;
  
  const SOURCE_USER = 0;
  const SOURCE_FIT = 1;
  const SOURCE_AGGREGATE = 2;
  
  const DEFAULT_WEIGHT = 70.0;
  const DEFAULT_HEIGHT = 170;
  const DEFAULT_MUL = 1.30;
  
  const REQUIRE_MEASUREMENT_EVERY_DAYS = 7;
  
  public static function getMerged($userId) {
    $o = self::create();
    $o->userid = $userId;
    $o->time = time();
    $o->source = self::SOURCE_AGGREGATE;
    
    $o->weight = self::getAverageWeight($userId);
    
    $table = F_Table::getClassTable(self::CLASS_NAME);
    
    $query = "SELECT height FROM $table WHERE " .
      "userid=$userId AND height>0.1 ORDER BY time DESC LIMIT 1";
    $result = F_Table::doQuery($query, null, F_Table::LOADMETHOD_SINGLE_VALUE);
    if ($result) {
      $o->height = (float)$result;
    }
    else {
      $o->height = self::DEFAULT_HEIGHT;
    }
    
    $query = "SELECT mul FROM $table WHERE " .
      "userid=$userId AND mul>0.1 ORDER BY time DESC LIMIT 1";
    $result = F_Table::doQuery($query, null, F_Table::LOADMETHOD_SINGLE_VALUE);
    if ($result) {
      $o->mul = (float)$result;
    }
    else {
      $o->mul = self::DEFAULT_MUL;
    }
    
    return $o;
  }
  
  public static function getAverageWeight($userId) {
    $now = time();
    $where = "userid=".$userId." AND time>=".($now-self::AVERAGE_WEIGHT_PERIOD_DAYS * F_UtilsTime::A_DAY);
    $recordsLastDays = self::query($where, "time DESC");
    
    if (count($recordsLastDays) < 2) {
      $o = self::loadLast($userId);
      if ($o) {
        return $o->weight;
      }
      else {
        return self::DEFAULT_WEIGHT;
      }
    }
    
    $total = 0.0;
    $count = 0;
    $k = (1.0 / (float)self::AVERAGE_WEIGHT_EMIDW) / (float)F_UtilsTime::A_DAY;;
    
    $reachedIndex = -1;
    $lastTime = null;
    
    foreach ($recordsLastDays as $index => $record) {
      if ($index <= $reachedIndex) continue;
      if ($record->weight < 0.1) continue;
      
      if ($lastTime === null) {
        $lastTime = $record->time;
      }
      
      $recordTime = $record->time;
      if ($recordTime > $lastTime) $recordTime = $lastTime;
      
      $cl_w = $record->weight;
      $cl_t = $record->time;
      $cl_c = 1;
      
      $nextIndex = $index + 1;
      while (isset($recordsLastDays[$nextIndex])) {
        $nextRecord = $recordsLastDays[$nextIndex++];
        
        if (($record->time - $nextRecord->time) > self::AVERAGE_WEIGHT_CLUSTERING * F_UtilsTime::AN_HOUR) {
          break;
        }
        
        $cl_w += (float)$nextRecord->weight;
        $cl_t += $nextRecord->time;
        $cl_c ++;
        $reachedIndex = $nextIndex - 1;
      }
      
      $cl_w /= (float)$cl_c;
      $cl_t /= (float)$cl_c;
      
      $weight = 1 / (1 + $k * ($lastTime - $cl_t));
      
      $total += $cl_w * $weight;
      $count += $weight;
    }
    return $total / $count;
  }
  
  public static function loadLast($userId) {
    $o = F_Table::loadClass(self::CLASS_NAME, array("userid" => (int)$userId), "time DESC");
    if (!$o) self::setError(F_Table::getError());
    return $o;
  }

}
