<?php

class H_FitActivityInfo extends H_BaseTablehelper
{
  const CLASS_NAME = "lmfitactivityinfo";
  const CACHING_POLICY = H_Caching::CACHE_OPTION_ALL;
  
  public static function loadByFitCode($code, $invalidate_cache = false) {
    return self::queryOne(array("fit_code" => (int)$code));
  }
}
