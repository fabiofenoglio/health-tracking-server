<?php

class JTableLmuserinfo extends F_TableModel
{
  public $userid;
  public $sex;
  public $birthdate;
  /*
  data->displayName
  data->google_info
  data->google_info_age
  data->google_access_token
  */

  public function getGoogleAccessToken() {
    return $this->data->get("google_access_token", null);
  }
  
  public function setGoogleAccessToken($value) {
    return $this->data->set("google_access_token", $value);
  }
  
  public function invalidateGoogleToken() {
    unset($this->data->google_info);
    unset($this->data->google_info_age);
    unset($this->data->google_access_token);
  }
  
  public function setGoogleInfo($g) {
    $this->data->google_info = $g;
    $this->data->google_info_age = time();
    
    $currentName = $this->data->get("displayName", null);
    if (empty($currentName)) {
      if (isset($g->givenName) && !empty($g->givenName)) {
        $this->data->displayName = $g->givenName;
      }
      else if (isset($g->name) && !empty($g->name)) {
        $this->data->displayName = $g->name;
      }  
    }
    
    $this->sex = $this->getSex();
  }
  
  public function refreshGoogleInfo() {
    try {
      $res = H_IntegrationGoogleUserinfo::requestUserInfo($this->userid);
      if (isset($res->email)) {
        $this->setGoogleInfo($res);
        if ($this->store()) {
          return true;
        }
        else {
          return false;
        }
      }
    }
    catch (Exception $e) {
      $this->setError($e->getMessage());
      $errorCode = "E".$e->getCode().".";
      if ($errorCode[1] == '4') {
        // auth error detected, removing token
        $this->invalidateGoogleToken();
        $this->store();
      }
      return false;
    }
  }
  
  public function getGoogleInfo() {
    if (!isset($this->data->google_info)) {
      if (!$this->getGoogleAccessToken() || !$this->refreshGoogleInfo()) {
        $result = null;
        return $result;
      }
    }
    
    if (
      (time() - $this->data->get("google_info_age", 0))
      >  H_UserInfo::GOOGLE_MAX_INFO_LIFE_DAYS * 
          F_UtilsTime::A_DAY) {
      unset($this->data->google_info);
      unset($this->data->google_info_age);
      
      if (!$this->refreshGoogleInfo()) {
        $result = null;
        return $result;
      }
    }
    
    $result = $this->data->google_info;
    return $result;
  }
  
  public function getAgeInYears() {
    $bd = $this->getBirthday();
    
    if ($bd)
      return ((float)(time() - $bd)) / 31553280.0;
    else
      return null;
  }

  public function getDisplayName() {
    // I have a custom name?
    $n = $this->data->get("displayName", null);
    if (!empty($n)) {
      return $n;
    }
    
    // I have google's name?
    if (($g = $this->getGoogleInfo())) {
      if (isset($g->givenName) && !empty($g->givenName)) {
        return $g->givenName;
      }
      if (isset($g->name) && !empty($g->name)) {
        return $g->name;
      }
    }
    
    // user is current user?? load his name if he is
    $currentUser = JFactory::getUser();
    if ((int)($currentUser->id) == (int)$this->userid) {
      return ucwords(strtolower($currentUser->name));
    }
    
    // load user and get his name
    $user = F_User::getUserById((int)$this->$userid);
    if ($user) {
      return ucwords(strtolower($user->name));
    }
    
    // wtf
    return "???";
  }
  
  public function getBirthday() {
    if (($g = $this->getGoogleInfo())) {
      if (isset($g->birthday)) {
        if ($g->birthday[0] != '0') return strtotime($g->birthday);
      }
    }
    
    return $this->birthdate;
  }
  
  public function getSex() {
    if (($g = $this->getGoogleInfo())) {
      if (isset($g->gender)) {
        if ($g->gender == 'male')   return H_UserInfo::SEX_MALE;
        if ($g->gender == 'female') return H_UserInfo::SEX_FEMALE;
      }
    }
    
    if ($this->sex == 'm' || $this->sex == 'M') return H_UserInfo::SEX_MALE;
    if ($this->sex == 'f' || $this->sex == 'f') return H_UserInfo::SEX_FEMALE;
    return H_UserInfo::SEX_UNKNOWN;
  }

  function postClear()
  {
    // nothing to do here right now
  }
}