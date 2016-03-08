<?php

class H_DataTimespan extends F_BaseStatic
{
  public static function getDayFromTime($time) {
    return strtotime(date("Y-m-d", $time) . " 00:00:00");
  }
}
