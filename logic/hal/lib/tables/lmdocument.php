<?php

class JTableLmdocument extends H_BaseTable
{
  public $name;
  public $group;
  public $filename;
  public $path;
  
  function postClear()
  {
    $this->userid = JFactory::getUser()->id;
    $this->source = H_Data::SOURCE_USER;
    $this->time = time();
    $this->group = "";
    $this->name = "";
    $this->path = "";
    $this->filename = "";
    $this->data->attachments = array();
  }
  
  public function hasAttachments() {
    return (!empty($this->data->attachments));
  }
  
  public function getAttachments() {
    return $this->data->attachments;
  }
  
  public function getFullAttachmentPath($path) {
   $p = JIF_PATH_HAL . "/data/u/".($this->userid)."/docs/";
    if ($this->group !== null && $this->group !== "") {
      $p .= F_Safety::sanitize($this->group, F_Safety::ALPHA_NUM_PT_SCORES) . "/";
    }
    $p .= $path;
    
    return $p; 
  }
}