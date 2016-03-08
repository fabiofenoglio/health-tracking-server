<?php

class H_IntegrationGoogle extends F_BaseStatic
{
  const AUTH_FILE_NAME = "{{{OBFUSCATED}}}";
  
  public static function testUserToken($userId = null) {
    if (!($client = H_IntegrationGoogle::getClient($userId))) {
      self::setError(H_IntegrationGoogle::getError());
      return false;
    }

    try {
      $oauth = new Google_Service_Oauth2($client);
      $res = $oauth->userinfo->get();    
      $res = new JObject(get_object_vars($res));
      return true;
    }
    catch (Exception $e) {
      self::setError($e->getMessage());
      return false;
    }
  }
  
  public static function getClient($userId = null) {
    static $cache = null;
    if ($cache === null) $cache = array();
    
    if (!$userId) return self::buildClient();
    
    $cacheKey = "c".$userId;
    
    if (isset($cache[$cacheKey])) {
      return $cache[$cacheKey];
    }
    
    $o = self::buildClient($userId);
    $cache[$cacheKey] = $o;
    return $o;    
  }
  
  public static function buildClient($userId = null) {
    self::includeLibrary();
    
    $client = new Google_Client();
    $client->setAuthConfigFile(self::getAuthFile());
    $client->setRedirectUri(self::getRedirectUrl());
    
    foreach (self::getScopes() as $scope) {
      $client->addScope($scope);  
    }
    
    $client->setAccessType('offline');
    
    if ($userId !== null) {
      $userInfo = H_UserInfo::loadByUser($userId);
      if (!$userInfo) {
        self::setError(H_UserInfo::getError());
        return null;
      }

      $token = $userInfo->getGoogleAccessToken();
      if (!$token) {
        self::setError("No google access token for the user");
        return null;
      }
      
      $client->setAccessToken($token);
      if ($client->isAccessTokenExpired()) {
        // try to refresh token
        $refreshed = false;
        
        try {
          $refreshToken = json_decode($token);
          $refreshToken = $refreshToken->refresh_token;
          
          $client->refreshToken($refreshToken);
          $userInfo->setGoogleAccessToken($client->getAccessToken());
          $userInfo->store();
          $refreshed = true;
        } 
        catch (Exception $e) {
          self::setError($e->getMessage());
          $errorCode = "E".$e->getCode().".";
          if ($errorCode[1] == '4') {
            // auth error detected, removing token
            $userInfo->invalidateGoogleToken();
            $userInfo->store();
          }
          $refreshed = false;
        }

        // if failing
        if (!$refreshed) {
          return null;
        }
      }
    }

    return $client;
  }
  
  public static function getAuthFile() {
    return JIF_PATH_HAL . "/data/" . self::AUTH_FILE_NAME;
  }
  
  public static function includeLibrary() {
    if (defined("GOOGLE_CLIENT_LIB")) return;
    define("GOOGLE_CLIENT_LIB", true);
    F_Library::importExternal("googleapi");
  }
  
  public static function getRedirectUrl() {
    $cu = JURI::base();
    $pieces = explode("?", $cu);
    if (empty($pieces)) $pieces = array($cu->toString());
    $u = new JURI($pieces[0]);
    $u->setVar("option", H_UiRouter::UI_COMPONENT);
    $u->setVar(F_SimplecomponentHelper::PK_MODULE, "integration.google.callback");   
    $url = trim($u->toString(), "\\/");
    return $url;
  }
  
  public static function getScopes() {
    self::includeLibrary();

    $result = array(
      Google_Service_Fitness::FITNESS_ACTIVITY_READ,
      Google_Service_Fitness::FITNESS_BODY_READ,
      'https://www.googleapis.com/auth/userinfo.email',
      'https://www.googleapis.com/auth/userinfo.profile'
    );
    
    return $result;
  }
}
