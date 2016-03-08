<?php

class H_FitData extends H_BaseTablehelper
{
  const CLASS_NAME = "lmfitdata";
  
  const LOCK_KEY = "fit-data";
  const IMPORT_BATCH_SIZE_DAYS_CALORIES = 31;
  const IMPORT_BATCH_SIZE_DAYS_WEIGHT = 120;
  const IMPORT_BATCH_SIZE_DAYS_ACTIVITIES = 31;
  
  const TYPE_UNKNOWN = 0;
  const TYPE_CALORIES = 1;
  const TYPE_WEIGHT = 2;
  const TYPE_HEIGHT = 3;
}
