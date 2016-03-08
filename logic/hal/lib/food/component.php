<?php

class H_FoodComponent extends H_BaseTablehelper
{
  const CLASS_NAME = "lmfoodcomponent";
  const CACHING_POLICY = H_Caching::CACHE_OPTION_ALL;
  
  const ID_ENERGY = 2;
  
  public static function loadOrderedListCached() {
    return self::loadOrderedList();
  }
  
  public static function loadUnorderedListCached() {
    return self::loadOrderedList();
  }
  
  public static function loadOrderedList() {
    return self::query("1", "`order` DESC, display_name ASC", "id");
  }

  public static function loadUnorderedList() {
    return self::loadOrderedList();
  }  
}
