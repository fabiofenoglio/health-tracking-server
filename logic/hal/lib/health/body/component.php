<?php

class H_HealthBodyComponent extends H_BaseTablehelper
{
  const CLASS_NAME = "lmhealthbodycomponent";
  const CACHING_POLICY = H_Caching::CACHE_OPTION_ALL;
  
  public static function loadOrderedList() {
    return self::query("1", "`order` DESC", "id");
  }
}
