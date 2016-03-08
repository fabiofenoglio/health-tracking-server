<?php

class H_UserInfo extends H_BaseTablehelper
{
  const CLASS_NAME =  "lmuserinfo";
  const CACHING_POLICY = H_Caching::CACHE_OPTION_ALL;
  const CACHING_RADIX = "user.info";
  
  const SEX_MALE =    "m";
  const SEX_FEMALE =  "f";
  const SEX_UNKNOWN = "u";
  
  const GOOGLE_MAX_INFO_LIFE_DAYS = 7;
  const DEFAULT_USER_PICTURE = "actions/view-process-own.png";
  
  public static function loadCurrent($invalidate_cache = false) {
    $user = JFactory::getUser();
    if ($user->guest) { 
      return null; 
    }
    
    return self::loadByUser($user->id);
  }

  public static function loadByUser($userId, $invalidate_cache = false) {
    $o = self::queryOne(array("userid" => $userId));
    if (!$o) {
      $o = F_Table::create(self::CLASS_NAME);
      $o->userid = $userId;
      $o->store();
      return self::queryOne(array("userid" => $userId));
    }
    return $o;
  }
}
