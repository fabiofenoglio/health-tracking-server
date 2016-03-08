<?php

class H_IntegrationGoogleUserinfo extends F_BaseStatic
{
  public static function requestUserInfo($userId) {
    if (!($client = H_IntegrationGoogle::getClient($userId))) {
      self::setError(H_IntegrationGoogle::getError());
      return null;
    }

    try {
      $oauth = new Google_Service_Oauth2($client);
      $res = $oauth->userinfo->get();    
      $res = new JObject(get_object_vars($res));
      return $res;
    }
    catch (Exception $e) {
      self::setError($e->getMessage());
      return null;
    }
  }
}
