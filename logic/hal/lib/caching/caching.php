<?php

class H_Caching extends F_BaseStatic
{
  const CACHE_OPTION_NONE = 0;
  const CACHE_OPTION_ELEMENTS = 1;
  const CACHE_OPTION_QUERIES = 2;
  
  const CACHE_OPTION_ALL = 0xFFFF;
  
  private static $cache = array();
  private static $stats = null;
  
  private static $miss = array();
  
  public static function ___init___() {
    self::clear();
  }
  
  public static function clear() {
    self::$stats = new JObject();
    self::$stats->hits = 0;
    self::$stats->invalidated = 0;
    self::$stats->inserts = 0;
    self::$stats->clears = 0;
    self::$stats->saved = 0.0;
    
    foreach (self::$cache as $el) {
      unset($el);
    }
    self::$cache = array();
  }
  
  public static function _($key, $func) {
    return self::handle($key, $func);
  }
  
  public static function handle($key, $func) {
    if (($result = self::get($key)) !== null) {
      return $result;
    }
    
    $result = $func();
    self::set($key, $result);
    return $result;
  }
  
  public static function getStats() {
    return self::$stats;
  }
  
  public static function getRaw() {
    $o = new JObject();
    $o->cache = self::$cache;
    $o->stats = self::$stats;
    return $o;
  }
  
  private static function needPurging() {
    static $limit = null;
    static $limitCalculated = false;
    
    if (!$limitCalculated) {
      $limit = self::getMemoryLimit() * 0.8;
      $limitCalculated = true;
    }
    
    if (!$limit) {
      return false;
    }
    
    if (memory_get_usage() > $limit) {
      return true;
    }
    
    return false;
  }
  
  private static function getMemoryLimit() {
    $memory_limit = ini_get('memory_limit');
    if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
      if ($matches[2] == 'M') {
        $memory_limit = $matches[1] * 1024 * 1024; // nnnM -> nnn MB
      } 
      else if ($matches[2] == 'K') {
        $memory_limit = $matches[1] * 1024; // nnnK -> nnn KB
      }
      else if ($matches[2] == 'G') {
        $memory_limit = $matches[1] * 1024 * 1024 * 1024;
      }
    }
    
    return $memory_limit > 0 ? $memory_limit : null;
  }
  
  private static function in_cache($key) {
    if (!isset(self::$cache[$key])) { 
      return false;
    }
    return true;
  }
  
  public static function purge() {
    self::$stats->clears ++;
    self::clear();
  }
  
  public static function invalidateBatch($regex) {
    if (!$regex) return;
    if ($regex[0] != '/') $regex = "/".$regex."/i";
    
    $toDelete = array();
    
    foreach (self::$cache as $key => $v) {
      if (preg_match($regex, $key)) {
        $toDelete[] = $key;
      }
    }
    
    foreach ($toDelete as $key) {
      unset(self::$cache[$key]);
      self::$stats->invalidated ++;
    }
  }
  
  public static function invalidate($key) {
    if (self::in_cache($key)) {
      unset(self::$cache[$key]);
      self::$stats->invalidated ++;
    }
  }
  
  public static function set($key, $value, $expire_time = null) {
    if (self::needPurging()) {
      F_Log::showWarning("memory limit reached: cache purged");
      self::purge();
    }
    
    if (isset(self::$cache[$key])) {
      F_Log::showError("caching error: duplicate entry " . $key);
    }
    
    if ($expire_time === null) $expire_time = 0;
    self::$stats->inserts ++;
    $now = time();
    
    if (isset(self::$miss[$key])) {
      $el_time = microtime(true) - self::$miss[$key];
      unset(self::$miss[$key]);
    }
    else {
      $el_time = 0;
    }
    
    $token = array($value, $expire_time, $now, $now, $el_time);
    self::$cache[$key] = $token;
    return $value;
  }
  
  public static function get($key) {
    $result = self::get_core($key);
    return $result;
  }
  
  private static function get_core($key) {
    $now = time();
    
    if (!self::in_cache($key)) {
      self::$miss[$key] = microtime(true);
      return null;
    }
    
    // token = array(data, expire_time, start_time, last_hit, el_time);
    $token = self::$cache[$key];
    
    if (
        ($token[1] > 0) && 
        (($now - $token[2]) >= $token[1])
       ) 
    {
      // token expired
      unset(self::$cache[$key]);
      return null;
    }
    
    // token still valid
    self::$stats->hits ++;
    self::$stats->saved += $token[4];
    
    /*
    // enable this if implementing selective cache purging
    $token[3] = $now;
    self::$cache[$key] = $token;
    */
    
    return $token[0];
  }
}
