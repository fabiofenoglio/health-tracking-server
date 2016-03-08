<?php

class JTableLmfitdata extends F_TableModel
{
  public $userid;
  public $type;
  public $date;
  public $value;
  
  function postClear()
  {
    $this->type = H_FitData::TYPE_UNKNOWN;
    $this->date = 0;
    $this->value = 0.0;
  }
  
  function preStore() {
    $this->data->str_date = date("Y-m-d", $this->date);
    return true;
  }
}