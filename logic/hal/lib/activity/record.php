<?php

class H_ActivityRecord extends H_BaseTablehelper
{
  const CLASS_NAME = "lmactivityrecord";
  
  const SOURCE_USER = 0;
  const SOURCE_FIT = 1;
  const SOURCE_AGGREGATE = 2;
  
  const DEFAULT_DURATION = 30.0;

  public static function loadLast($userId) {
    return self::queryOne(
      array(  "userid" => (int)$userId,
              "source" => H_ActivityRecord::SOURCE_USER), 
      "time DESC"
    );
  }
}
