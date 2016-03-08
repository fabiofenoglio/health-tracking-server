<?php

class JTableLmactivityrecord extends F_TableModel
{
  public $userid;
  public $time;

  public $source;

  public $activity;
  public $duration;
  public $relative_fatigue;
  public $calories;
    
  function postClear()
  {
    $this->relative_fatigue = 100;
    $this->duration = H_ActivityRecord::DEFAULT_DURATION;
  }
}