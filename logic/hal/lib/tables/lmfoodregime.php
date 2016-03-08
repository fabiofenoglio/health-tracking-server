<?php

class JTableLmfoodregime extends F_TableModel
{
  public $name;
  public $userid;
  public $status;
  /*
  public $data->components = array of H_FoodRegimeComponentSpecification
  */
  
  function getOrderedComponents() {
    $foodComponents = H_FoodComponent::loadOrderedListCached();
    $result = array();
    foreach ($foodComponents as $foodComponent) {
      if (isset($this->data->components[$foodComponent->id])) {
        $result[$foodComponent->id] = $this->data->components[$foodComponent->id];
      }
    }
    return $result;
  }
  
  function getComponent($key, $default = null) {
    if (!isset($this->data->components[$key])) {
      return $default;
    }
    return $this->data->components[$key];
  }
  
  function setStatus($statusValue) {
    $this->status = $statusValue;
  }
  
  function checkStatus($statusValue) {
    return (int)$this->status == (int)$statusValue ? true : false;
  }

  function postClear()
  {
    $this->name = "New Food Regime";
    $this->userid = 0;
    $this->data->components = array();
    $this->status = 0;
  }
  
  public function postBind()
  {
    $this->data->components = unserialize($this->data->components);
    return true;
  }

  public function preStore()
  {
    $this->data->components = serialize($this->data->components);
    return true;
  }
}