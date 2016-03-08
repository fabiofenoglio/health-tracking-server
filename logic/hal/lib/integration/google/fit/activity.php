<?php

class H_IntegrationGoogleFitActivity extends F_BaseStatic
{
  const ID_INVALID = -1;
  const ID_UNKNOWN = 0;
  const ID_INACTIVE = 3;
  const ID_WALKING = 7;
  
  public static function codeCanBeSkipped($code) {
    return ($code < 7);
  }
}
