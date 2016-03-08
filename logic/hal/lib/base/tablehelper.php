<?php

class H_BaseTablehelper extends F_BaseStatic
{
  const CACHING_POLICY = H_Caching::CACHE_OPTION_NONE;
  const CACHING_TIME = 0;
  const CACHING_RADIX = "";
  
  private static function getCacheKeyById($id) {
    $cacheKey = static::CLASS_NAME . "#" . $id;
    if (strlen(static::CACHING_RADIX) > 0) {
      $cacheKey = static::CACHING_RADIX . "." . $cacheKey;
    }
    return $cacheKey;
  }
  
  public static function load($id) {
    if (static::CACHING_POLICY == H_Caching::CACHE_OPTION_NONE) {
      $o = F_Table::loadClass(static::CLASS_NAME, array("id" => (int)$id));   
      if (!$o) self::setError(F_Table::getError());
      return $o;
    }
    
    if (static::CACHING_POLICY & H_Caching::CACHE_OPTION_ELEMENTS) {
      $cacheKey = self::getCacheKeyById($id);

      if (($result = H_Caching::get($cacheKey))) {
        return $result;
      }
      
      $o = F_Table::loadClass(static::CLASS_NAME, array("id" => (int)$id));
      if (!$o) {
        self::setError(F_Table::getError());
      }
      else {
        H_Caching::set($cacheKey, $o, static::CACHING_TIME);
      }
      
      return $o;
    }
    
    return null;
  }
  
  private static function getSqlCacheKey($sql, $index_prop, $loadMethod) {
    $queryCacheKey = "query.".static::CLASS_NAME.".sql#" . $sql . ".loadmethod#" . $loadMethod;
      
    if ($index_prop !== null) {
      $queryCacheKey .= ".index#".$index_prop;
    }
    
    if (strlen(static::CACHING_RADIX) > 0) {
      $queryCacheKey = static::CACHING_RADIX . "." . $queryCacheKey;
    }
    
    return $queryCacheKey;
  }
  
  private static function getQueryCacheKey($where, $sort, $index_prop) {
    $queryCacheKey = "query.".static::CLASS_NAME.".where#";
      
    if (is_array($where)) {
      foreach ($where as $k => $v) {
        $queryCacheKey .= $k . "=" . $v . ";";
      }
    }
    else {
      $queryCacheKey .= $where . ";";
    }

    if ($sort !== null) {
      $queryCacheKey .= ".sort#".$sort;
    }
    if ($index_prop !== null) {
      $queryCacheKey .= ".index#".$index_prop;
    }
    
    if (strlen(static::CACHING_RADIX) > 0) {
      $queryCacheKey = static::CACHING_RADIX . "." . $queryCacheKey;
    }
    
    return $queryCacheKey;
  }
  
  private static function fromCacheOrInsert($el, $key = null) {
    if ($key === null) {
      $key = self::getCacheKeyById($el->id);  
    }
    
    if (($result = H_Caching::get($key))) {
      return $result;
    }
    
    H_Caching::set($key, $el, static::CACHING_TIME);
    return $el;
  }
  
  private static function completeQuery($query) {
    $query = str_replace("{T}", F_Table::getClassTable(static::CLASS_NAME), $query);
    return $query;
  }
  
  public static function sql($query, $index_prop = null, $loadMethod = null) {
    if ($loadMethod === null) $loadMethod = F_Table::LOADMETHOD_OBJECT_LIST;
    $query = self::completeQuery($query);
    
    if (static::CACHING_POLICY == H_Caching::CACHE_OPTION_NONE) { 
      return F_Table::loadClassListFromQuery(static::CLASS_NAME, $query, $index_prop, $loadMethod);
    }
    
    if (static::CACHING_POLICY & H_Caching::CACHE_OPTION_QUERIES) {
      $cacheKey = self::getSqlCacheKey($sql, $index_prop, $loadMethod);
      if (($result = H_Caching::get($cacheKey))) {
        return $result;
      }
    }
    else {
      $cacheKey = null;
    }
    
    $o = F_Table::loadClassListFromQuery(static::CLASS_NAME, $query, $index_prop, $loadMethod);
    
    if (!empty($o) && 
        ($loadMethod == F_Table::LOADMETHOD_OBJECT_LIST || $loadMethod == F_Table::LOADMETHOD_OBJECT) && 
        (static::CACHING_POLICY & H_Caching::CACHE_OPTION_ELEMENTS)) 
    {
      if ($loadMethod == F_Table::LOADMETHOD_OBJECT) {
        $o = self::fromCacheOrInsert($o);
      }
      else {
        foreach ($o as $k => $v) {
          $o[$k] = self::fromCacheOrInsert($v);  
        }
      }
    }
    
    if ($cacheKey) {
      H_Caching::set($cacheKey, $o, static::CACHING_TIME);  
    }
    
    return $o;
  }
  
  public static function query($where = null, $sort = null, $index_prop = null) {
    if ($where === null) $where = "1";
    
    if (static::CACHING_POLICY == H_Caching::CACHE_OPTION_NONE) {
      $o = F_Table::loadClassList(static::CLASS_NAME, $where, $sort, $index_prop);
      if (!$o) self::setError(F_Table::getError());
      return $o;
    }
    
    if (static::CACHING_POLICY & H_Caching::CACHE_OPTION_QUERIES) {
      $cacheKey = self::getQueryCacheKey($where, $sort, $index_prop);
      if (($result = H_Caching::get($cacheKey))) {
        return $result;
      }
    }
    else {
      $cacheKey = null;
    }
    
    $o = F_Table::loadClassList(static::CLASS_NAME, $where, $sort, $index_prop);
    if (!$o) {
      self::setError(F_Table::getError());
    }
    
    // maybe need to load some element from cache
    if (!empty($o) && (static::CACHING_POLICY & H_Caching::CACHE_OPTION_ELEMENTS)) {
      foreach ($o as $k => $v) {
        $o[$k] = self::fromCacheOrInsert($v);  
      }
    }

    if ($cacheKey) {
      H_Caching::set($cacheKey, $o, static::CACHING_TIME);  
    }
    
    return $o; 
  }
  
  public static function queryOne($where, $sort = null) {
    
    if (static::CACHING_POLICY == H_Caching::CACHE_OPTION_NONE) {
      $o = F_Table::loadClass(static::CLASS_NAME, $where, $sort);
      if (!$o) self::setError(F_Table::getError());
      return $o;
    }
    
    if (static::CACHING_POLICY & H_Caching::CACHE_OPTION_QUERIES) {
      $cacheKey = self::getQueryCacheKey($where, $sort, null);
      if (($result = H_Caching::get($cacheKey))) {
        return $result;
      }
    }
    else {
      $cacheKey = null;
    }
    
    $o = F_Table::loadClass(static::CLASS_NAME, $where, $sort);
    if (!$o) {
      self::setError(F_Table::getError());
    }
    else {
      $o = self::fromCacheOrInsert($o);
    }
    
    if ($cacheKey) {
      H_Caching::set($cacheKey, $o, static::CACHING_TIME);  
    }
    
    return $o; 
  }
  
  public static function create() {
    return F_Table::create(static::CLASS_NAME);
  }
}
