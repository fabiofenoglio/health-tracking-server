<?php

class H_FoodInfo extends H_BaseTablehelper
{
  const CLASS_NAME = "lmfoodinfo";
  const CACHING_POLICY = H_Caching::CACHE_OPTION_ALL;
  
  const PRIVACY_PUBLIC = 0;
  const PRIVACY_PRIVATE = 1;
  
  public static function userCanEdit($food, $userId) {
    return ($food->userid >0 && (int)$food->userid == (int)$userId);
  }
  
  public static function userCanView($food, $userId) {
    if ($food->userid < 1) return true;
    if ((int)$food->userid == (int)$userId) return true;
    if ((int)$food->privacy == self::PRIVACY_PUBLIC) return true;
    return false;
  }
  
  public static function getDifferentGroupsForUser($userId) {
    $sql_query = "SELECT DISTINCT `group` FROM " . F_Table::getClassTable(self::CLASS_NAME) . 
      " WHERE userid=" .$userId . " ORDER BY `group`";

    $list = F_Table::doQuery($sql_query, null, F_Table::LOADMETHOD_COLUMN);

    if (! $list)
    {
        self::setError(F_Table::getError());
        return null;
    }
    
    return $list;
  }
}
