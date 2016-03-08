<?php

class H_FoodRegime extends F_BaseStatic
{
  const CLASS_NAME = "lmfoodregime";
  
  const STATUS_IDLE = 0;
  const STATUS_ACTIVE = 1;
  
  public static function getUserActiveOrDefault($id) {
    $o = F_Table::loadClass(self::CLASS_NAME, array("userid" => (int)$id, "status" => self::STATUS_ACTIVE));
    if (!$o) {
      $o = self::create();
      $o->userid = $id;
      $o->name = "Default Regime";
    }
    return $o;
  }
  
  public static function getUserActive($id) {
    $o = F_Table::loadClass(self::CLASS_NAME, array("userid" => (int)$id, "status" => self::STATUS_ACTIVE));
    if (!$o) self::setError(F_Table::getError());
    return $o;
  }
  
  public static function activate($id, $userId) {
    // first deactivate others
    $query = "UPDATE " . F_Table::getClassTable(self::CLASS_NAME) .
      " SET status=" . self::STATUS_IDLE . " WHERE userid=".$userId;
    
    $result = F_Table::doQuery($query, null, F_Table::LOADMETHOD_SINGLE_VALUE);
    
    $regime = self::load($id);
    if (!$regime) return null;
    
    if ($regime->userid != $userId) {
      self::setError("not allowed");
      return false;
    }
    
    $regime->setStatus(self::STATUS_ACTIVE);
    if (!$regime->store()) {
      self::setError($regime->getError());
      return false;
    }
    
    return true;
  }
  
  public static function load($id) {
    $o = F_Table::loadClass(self::CLASS_NAME, array("id" => (int)$id));
    if (!$o) self::setError(F_Table::getError());
    return $o;
  }
  
  public static function loadByUser($id) {
    $o = F_Table::loadClassList(self::CLASS_NAME, 
                                array("userid" => (int)$id),
                                "(status & 1) DESC, name ASC");
    if (!$o) self::setError(F_Table::getError());
    return $o;
  }
  
  public static function userCanEdit($regime, $userId) {
    return ($regime->userid >0 && (int)$regime->userid == (int)$userId);
  }
  
  public static function create($foodComponents = null) {
    $o = F_Table::create(self::CLASS_NAME);
    
    if ($foodComponents === null)
      $foodComponents = H_FoodComponent::loadOrderedListCached();
    
    $spec_list = array();
    
    foreach ($foodComponents as $foodComponent) {
      $spec = new H_FoodRegimeComponentSpecification();
      
      $spec->goal_percentage = 1.00;
      $spec->min_percentage = 0.75;
      $spec->max_percentage = 1.40;
      $spec->monitor = ($foodComponent->default_tracked ? true : false);
      
      $spec_list[$foodComponent->id] = $spec;
    }
    
    $o->data->components = $spec_list;
    
    return $o;
  }

}
