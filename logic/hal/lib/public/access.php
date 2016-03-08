<?php

class H_PublicAccess extends H_BaseTablehelper
{
  static $publicMode = false;
  static $user = null;
  
  public static function check() {
    if (!F_Input::exists("publicToken")) {
      return false;
    }
    
    $tokenCode = F_Input::getAlphanumeric("publicToken");
    if (strlen($tokenCode) < 3) {
      return false;
    }
    
    $token = H_PublicAccessToken::load(array("token" => $tokenCode));
    if (!$token) {
      return false;
    }
    
    $user = JFactory::getUser($token->userid);
    if (!$user) {
      return false;
    }
    
    JFactory::getSession()->set('user', $user);
    self::isPublicMode(true);
    self::$user = $user;
    return $user;
  }
  
  public static function getUser() {
    return self::$user;
  }
  
  public static function isPublicMode($value = null) {
    if ($value !== null) {
      self::$publicMode = $value;
    }
    return self::$publicMode;
  }
}
