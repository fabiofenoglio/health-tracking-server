<?php

class H_UiScheduler extends F_BaseStatic
{
  const GENERIC_KEY = "H_UiScheduler_InfoHolder";
  private static $cache = null;
  
  public static function isTimeTo($key, $interval) {
    $list = self::getNoticeInfoHolder();
    if (!isset($list[$key]))  {
      return true;
    }
    
    if (time() - $list[$key] >= $interval) {
      return true;
    }
    
    return false;
  }
  
  public static function markDone($key, $time = null) {
    $list = self::getNoticeInfoHolder();
    if ($time === null) $time = time();
    $list[$key] = $time;
    self::$cache->setd("last_execution", $list);
    self::saveNoticeInfoHolder();
  }
  
  private static function getNoticeInfoHolder() {
    if (self::$cache !== null) return self::$cache->getd("last_execution");
    
    self::$cache = F_DataGeneric::loadOrCreate(self::GENERIC_KEY, F_DataGeneric::DO_NOT_EXPIRE);
    $testObj = self::$cache->getd("last_execution", null);
    if ($testObj === null || !is_array($testObj)) {
      self::$cache->setd("last_execution", array());
    }
    
    return self::$cache->getd("last_execution");
  }
  
  private static function saveNoticeInfoHolder() {
    if (!self::$cache->store()) {
      self::setError(self::$cache->getError());
      return false;
    }
    return true;
  }
}
