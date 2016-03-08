<?php

class H_FoodRecord extends H_BaseTablehelper
{
  const CLASS_NAME = "lmfoodrecord";
    
  public static function getLastGroup($userId) {
    $o = self::loadLast($userId);
    if (empty($o->group)) return "";
    return $o->group;
  }
  
  public static function loadLast($userId) {
    $o = F_Table::loadClass(self::CLASS_NAME, array("userid" => (int)$userId), "time DESC");
    if (!$o) self::setError(F_Table::getError());
    return $o;
  }
  
  public static function guessGroupByTime($userId, $time = null) {
    if ($time === null) $time = time();
    
    // extract only time
    $time = $time - strtotime(date("Y-m-d", $time));
    
    $groups = self::getAverageGroupTimes($userId);
    
    if (empty($groups)) {
      return null;
    }
    
    $best = null;
    $bestDiff = null;
    
    foreach ($groups as $group) {
      if ($best === null) {
        $best = $group;
        $bestDiff = abs($group->avg_time - $time);
        continue;
      }
      
      $diff = abs($group->avg_time - $time);
      if ($diff < $bestDiff) {
        $best = $group;
        $bestDiff = $diff;
      }
    }
    
    return $best->group;
  }
  
  public static function getAverageGroupTimes($userId, $days = null) {
    $cache_key = "food.record.averagegrouptimes.user#$userId";
    if (($result = H_Caching::get($cache_key))) {
      return $result;
    }
    
    if ($days === null) $days = 90;
    $table = F_Table::getClassTable(self::CLASS_NAME);
    $infTimeLimit = time() - F_UtilsTime::A_DAY * $days;
    $limit = $days * 50;
    $query = 
      "SELECT ".
        "AVG(TIME_TO_SEC(TIME(FROM_UNIXTIME(`time`)))) as avg_time, ".
        "`group` ".
      "FROM ".
        "(SELECT ".
          "* ".
          "FROM "."`$table` ".
          "WHERE ".
            "userid=$userId ".
            "AND `group`<>'' ".
            "AND TIME(FROM_UNIXTIME(`time`))>0 ".
            "AND `time`>$infTimeLimit ".
          "ORDER BY `time` DESC ".
          "LIMIT $limit ".
        ") t ".
      "GROUP BY `group` ".
      "ORDER BY avg_time ASC";
    
    $results = F_Table::doQuery($query, "group", F_Table::LOADMETHOD_OBJECT_LIST);
    if (!empty($results)) {
      foreach ($results as $k => $v) {
        $results[$k]->avg_time = (float)$results[$k]->avg_time;
      }
    }
    
    H_Caching::set($cache_key, $results);
    return $results;
  }
}
