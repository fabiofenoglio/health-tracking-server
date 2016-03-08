<?php

class H_UiRouter extends F_BaseStatic
{
  const UI_COMPONENT = "com_jif";
  const BACKTO_KEY = "backto";
  const REWRITE_RADIX = "lms";
  
  public static function build($module, $params = null, $absolute = false) {
    $cu = JURI::base();
    $pieces = explode("?", $cu);
    if (empty($pieces)) $pieces = array($cu->toString());
    $u = new JURI($pieces[0]);
    
    $u->setPath("/".self::REWRITE_RADIX."/".str_replace(".", "/", $module));
    
    if ($params) {
      foreach ($params as $k => $v) {
        if ($v === null) continue;
        $u->setVar($k, urlencode($v));
      }
    }
    
    if (!$u->getVar(self::BACKTO_KEY) && $params !== null && !array_key_exists(self::BACKTO_KEY, $params)) {
      $backto = JURI::getInstance();
      $backto->delVar(self::BACKTO_KEY);
      $backto = F_Addresses::absoluteUrlToRelativeUrl($backto->toString());
      $u->setVar(self::BACKTO_KEY, urlencode($backto));
    }
    
    $url = trim($u->toString(), "\\/");
    if (!$absolute)
      return F_Addresses::absoluteUrlToRelativeUrl($url);
    else
      return $url;
  }
  
  public static function buildNoRewrite($module, $params = null, $absolute = false) {
    $cu = JURI::base();
    $pieces = explode("?", $cu);
    if (empty($pieces)) $pieces = array($cu->toString());
    $u = new JURI($pieces[0]);
    
    $u->setVar("option", self::UI_COMPONENT);
    $u->setVar(F_SimplecomponentHelper::PK_MODULE, $module);
    
    if ($params) {
      foreach ($params as $k => $v) {
        if ($v === null) continue;
        $u->setVar($k, urlencode($v));
      }
    }
    
    if (!$u->getVar(self::BACKTO_KEY)) {
      $backto = JURI::getInstance();
      $backto->delVar(self::BACKTO_KEY);
      $backto = F_Addresses::absoluteUrlToRelativeUrl($backto->toString());
      $u->setVar(self::BACKTO_KEY, urlencode($backto));
    }
    
    $url = trim($u->toString(), "\\/");
    if (!$absolute)
      return F_Addresses::absoluteUrlToRelativeUrl($url);
    else
      return $url;
  }
  
  public static function getBackto($default = null) {
    if (!F_Input::exists(self::BACKTO_KEY)) {
      return $default;
    }
    
    return F_Input::getString(self::BACKTO_KEY);
  }
  
  public static function getCurrentModule() {
    return F_Input::getString(F_SimplecomponentHelper::PK_MODULE);
  }
  
  public static function getCommonFoodInfosUrl() {
    return self::build("diet.list.foodinfos_common");
  }
  
  public static function getDocumentsUrl() {
    return self::build("docs.list.all");
  }
  
  public static function getBodyRecordsUrl() {
    return self::build("body.list.records");
  }
  
  public static function getActivityRecordsUrl() {
    return self::build("activity.list.records");
  }
  
  public static function getFoodRecordsUrl() {
    return self::build("diet.list.records");
  }
  
  public static function getMyFoodInfosUrl() {
    return self::build("diet.list.foodinfos");
  }
  
  public static function getUserInfoUrl() {
    return self::build("user.settings");
  }
  
  public static function getMoneyRecordsUrl() {
    return self::build("money.list.records");
  }
  
  public static function getFoodDetailPreview($params = null) {
    return self::build(
      "diet.detail.foodplan",
      $params
    );
  }
  
  public static function getFoodDetailForDay($day) {
    return self::build(
      "diet.detail.day",
      array("day" => $day)
    );
  }
  
  public static function getAddFoodRegimeUrl($cloneId = null) {
    return self::build(
      "diet.edit.regime",
      array("clone" => $cloneId)
    );
  }

  public static function getAddDocumentUrl() {
    return self::build(
      "docs.edit.doc"
    );
  }

  public static function getEditDocumentUrl($id = null) {
    return self::build(
      "docs.edit.doc",
      array("id" => $id)
    );
  }

  public static function getEditMoneyRecordUrl($id = null) {
    return self::build(
      "money.edit.record",
      array("id" => $id)
    );
  }

  public static function getAddMoneyRecordUrl($cloneId = null, $params = null) {
    $p = array("clone" => $cloneId);
    if ($params) $p = array_merge($p, $params);
    return self::build(
      "money.edit.record",
      $p
    );
  }
  
  public static function getEditActivityRecordUrl($id = null) {
    return self::build(
      "activity.edit.record",
      array("id" => $id)
    );
  }

  public static function getAddActivityRecordUrl($cloneId = null, $params = null) {
    $p = array("clone" => $cloneId);
    if ($params) $p = array_merge($p, $params);
    return self::build(
      "activity.edit.record",
      $p
    );
  }
  
  public static function getEditFoodRegimeUrl($id = null) {
    return self::build(
      "diet.edit.regime",
      array("id" => $id)
    );
  }

  public static function getAddFoodInfoUrl($cloneId = null, $params = null) {
    $p = array("clone" => $cloneId);
    if ($params) $p = array_merge($p, $params);
    return self::build(
      "diet.edit.foodinfo",
      $p
    );
  }

  public static function getAddFoodRecordUrl($cloneId = null) {
    return self::build(
      "diet.edit.record",
      array("clone" => $cloneId)
    );
  }

  public static function getAddBodyRecordUrl($params = null) {
    $p = array("clone" => $cloneId);
    if ($params) $p = array_merge($p, $params);
    
    return self::build(
      "body.edit.record",
      $p
    );
  }

  public static function getCloneFoodRecordUrl($cloneId) {
    return self::build(
      "diet.list.records",
      array("fast_clone" => $cloneId)
    );
  }

  public static function getEditBodyRecordUrl($foodId) {
    return self::build(
      "body.edit.record",
      array("id" => $foodId)
    );
  }
  
  public static function getEditFoodGroupRecordUrl($group, $day) {
    return self::build(
      "diet.edit.record",
      array("group" => $group, "day" => $day)
    );
  }

  public static function getEditFoodRecordUrl($foodId, $params = null) {
    $p = array("id" => $foodId);
    if ($params) $p = array_merge($p, $params);
    
    return self::build(
      "diet.edit.record",
      $p
    );
  }

  public static function getEditFoodInfoUrl($foodId, $params = null) {
    $p = array("id" => $foodId);
    if ($params) $p = array_merge($p, $params);
    
    return self::build(
      "diet.edit.foodinfo",
      $p
    );
  }
}
