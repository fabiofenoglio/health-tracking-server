<?php

class H_UtilsArray extends F_BaseStatic
{
  private static function checkElementForCriteria($el, $criteria) {
    $is_func = is_callable($criteria);
    
    if ($is_func) {
      if ($criteria($el)) {
        return true;
      }
      else {
        return false;
      }
    }
    else {
      $valid = true;
      $is_obj = is_object($el);
      foreach ($criteria as $k => $v) {
        if ($is_obj) {
          if ($el->$k != $v) {
            $valid = false;
            break;
          }
        }
        else {
          $val = isset($el[$k]) ? $el[$k] : null;
          if ($val != $v) {
            $valid = false;
            break;
          }
        }
      }

      return $valid;
    }
  }
  
  public static function filter($array, $criteria) {
    $result = array();
    
    foreach ($array as $k => $el) {
      if (self::checkElementForCriteria($el, $criteria)) {
        $result[$k] = $el;
      }
    }
    
    return $result;
  }
  
  public static function first($array, $criteria) {
    foreach ($array as $el) {
      if (self::checkElementForCriteria($el, $criteria)) {
        return $el;
      }
    }
    return null;
  }
  
  public static function best($array, $func) {
    $best = null;
    $bestValue = null;
    
    foreach ($array as $el) {
      $val = $func($el);
      
      if ($best === null) {
        $best = $el;
        $bestValue = $val;
        continue;
      }
      
      if ($val > $bestValue) {
        $best = $el;
        $bestValue = $val;
      }
    }
    return $best;
  }
}
