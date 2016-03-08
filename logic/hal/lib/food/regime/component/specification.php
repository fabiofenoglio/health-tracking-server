<?php

class H_FoodRegimeComponentSpecification extends JObject
{
  const CLASS_NAME = "lmfoodregimecomponentspecification";
  
  public $goal_percentage;
  public $min_percentage;
  public $max_percentage;
  public $monitor;          // if the value should be monitored
}
