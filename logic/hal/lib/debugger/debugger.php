<?php

class H_Debugger extends F_BaseStatic
{
  const MIN_WIDTH = 100;
  
  public static function _($obj) {
    return self::var_dump($obj);
  }
  
  public static function var_dump($obj) {
    if (is_object($obj)) {
      echo "<pre>{OBJ} ".var_export( get_object_vars($obj), true )."</pre>";  
    }
    else if (is_array($obj)) {
      echo "<pre>{ARRAY} ".var_export($obj, true)."</pre>";
    }
    else {
      echo "<pre>{VAR} ".var_export($obj, true)."</pre>";
    }
    
  }
  
  public static function dump($obj, $maxLevel = 2, $level = 0) {
    if ($level > $maxLevel) {
      echo " ... ";
      return;
    }
    if (!is_object($obj) && !is_array($obj)) {
      echo $obj;
      return;
    }
    
    echo "<table>";
    foreach( $obj as $k=>$v )
    {
      echo "<tr>";
      // while ($level-- > 0) echo "&nbsp;";
      echo "<td style='width:".self::MIN_WIDTH."px;'>";
      echo $k;
      echo "</td>";
      
      echo "<td style='width:".self::MIN_WIDTH."px;'>";
      if (is_object($v) || is_array($v)) {
        self::dump($v, $maxLevel, $level + 1);
      }
      else {
        echo $v;	
      }
      echo "</td>";
      
      echo "</tr>";
    }
    echo "</table>";
  }
}
