<?php

class JTableLmhealthcheck extends H_BaseTable
{
  public $name;
  public $group;
  public $subject;
  
  public function postClear() {
    $this->name = "";
    $this->group = "";
    $this->subject = array();
    return true;
  }

  private function apply_data_types() {
    /*
    if (is_array($this->subject)) {
      foreach ($this->subject as $k => $v) {
        $this->subject[$k] = $v;
      }
    }
    */
  }

  public function postBind($src, $ignore) {
    $this->___expand_objects();
    $this->apply_data_types();
    return true;
  }

  public function preStore() {
    $this->apply_data_types();
    $this->___serialize_objects();
    return true;
  }

  public function postStore() {
    $this->___expand_objects();
    return true;
  }

  private function ___expand_objects() {
    if (strlen($this->subject) < 1) {
      $this->subject = array();
      return;
    }
    
    $this->subject = explode(",", $this->subject);
    
    if (!is_array($this->subject)) {
      $this->subject = array();
    }
    
    /*
    foreach ($this->subject as $k => $v) {
      $this->subject[$k] = $v;
    }
    */
  }

  private function ___serialize_objects() {
    $str = "";
    if (count($this->subject) < 1)
    {
      $this->subject = "";
      return;
    }
    foreach ($this->subject as $o)
    {
      $str .= $o . ",";
    }

    $this->subject = rtrim($str, ",");
  }
}