<?php

class H_FoodCalculator extends F_BaseStatic
{
  // Multiplicators to find maintenance kcal intake based on BMR
  const BMR_MUL_LOWEST =    1.200;  // low intensity activities and leisure activities (primarily sedentary)
  const BMR_MUL_LOW =       1.375;  // light exercise (leisurely walking for 30-50 minutes 3-4 days/week, golfing, house chores)
  const BMR_MUL_MODERATE =  1.550;  // moderate exercise 3-5 days per week (60-70% MHR for 30-60 minutes/session)
  const BMR_MUL_HIGH =      1.725;  // active individuals (exercising 6-7 days/week at moderate to high intensity (70-85% MHR) for 45-60 minutes/session)
  const BMR_MUL_EXTREME =   1.900;  // the extremely active individuals (engaged in heavy/intense exercise like heavy manual labor, heavy lifting, endurance athletes, and competitive team sports athletes 6-7 days/week for 90 + minutes/session)
  const BMR_AVERAGE =       1.320;  // wordly average
  
  const CALORIES_PER_KG =   7800;
  const IDEAL_BMI =         22.50;
  const IDEAL_BMI_VAR =     3.25;
  
  /*
  {OBJ} array (
    'perDayFromBmr' => 1669.41,
    'perDayFromUntracked' => 500.82,
    'perDayFromActivities' => 0,
    'perDayFromFit' => 1774.63,
    'estimated' => 2170.23,
    'result' => 2275.46,
    'is_estimated' => false,
  )
  */
  public static function computeDailyCaloriesOut($userId, $span = 31) {
    $userInfo = H_UserInfo::loadByUser($userId);
    $now = time();
    
    $stat = new JObject();
    
    $bodyInfo = H_BodyRecord::getMerged($userId);
    $bmr = H_FoodCalculator::computeBMR($userInfo, $bodyInfo);
    $stat->perDayFromBmr = $bmr;
    $stat->perDayFromUntracked = $bmr * ($bodyInfo->mul - 1.0);

    $actRecords = H_ActivityRecord::sql(
      "SELECT * FROM {T} WHERE ".
      "calories > 0 AND " .
      "userid=".$userId." AND time>" .
      ($now - F_UtilsTime::A_DAY * $span) .
      " ORDER BY time DESC"
    );

    // compute daily for directly tracked activities
    $oldest = null;
    $stat->totalActivityCalories = 0.0;

    foreach ($actRecords as $record) {
      if ($oldest === null || $record->time < $oldest) {
        $oldest = $record->time;
      }
      $stat->totalActivityCalories += (float)$record->calories;
    }

    if ($oldest !== null) {
      // correct with activities
      $oldestTimeDiff = (float)(($now-$oldest) / F_UtilsTime::A_DAY);
      if ($oldestTimeDiff < 3.0) {
        $oldestTimeDiff = 3.0;
      }
      $stat->perDayFromActivities = $stat->totalActivityCalories / $oldestTimeDiff ;
    }
    else {
      $stat->perDayFromActivities = 0.0;
    }
    
    unset($stat->totalActivityCalories);

    $calRecords = H_FitData::sql(
      "SELECT * FROM {T} WHERE ".
      "type=".H_FitData::TYPE_CALORIES." AND " .
      "value>=".($bmr * 0.8). " AND ".
      "userid=".$userId." AND date>" .
      ($now - F_UtilsTime::A_DAY * $span) .
      " ORDER BY date DESC"
    );

    $stat->estimated = $stat->perDayFromBmr + 
      $stat->perDayFromUntracked + 
      $stat->perDayFromActivities;

    if (count($calRecords) < 10) {
      // not enough to build an average. Return an estimation
      $stat->result = $stat->estimated;		
      $stat->is_estimated = true;
      return $stat;
    }

    $stat->days = array();
    $stat->fitTotalDays = 0;
    $stat->fitTotalCalories = 0.0;

    foreach ($calRecords as $record) {
      $day = date("Y-m-d", $record->date);

      if (!isset($stat->days[$day])) {
        $stat->days[$day] = 0.0;
      }

      $recordCal = (float)$record->value;
      $minValue = $bmr * 0.8;
      if ($recordCal < $minValue) {
        $recordCal = $minValue;
      }
      $stat->days[$day] += $recordCal;
      $stat->fitTotalCalories += $recordCal;
    }

    $stat->fitTotalDays = count($stat->days);

    if ($stat->fitTotalDays > 0) {
      $stat->perDayFromFit = $stat->fitTotalCalories / (float)$stat->fitTotalDays;
    }
    else {
      $stat->perDayFromFit = 0.0;
    }

    $stat->result = $stat->perDayFromUntracked + 
      $stat->perDayFromActivities +
      $stat->perDayFromFit;

    $stat->is_estimated = false;

    unset($stat->days);
    unset($stat->fitTotalDays);
    unset($stat->fitTotalCalories);
    return $stat;
  }

  public static function computeBMI($bodyInfo) {
    if (!$bodyInfo) return null;
    $height_meters = ((float)$bodyInfo->height) / 100.0;
    return ((float)$bodyInfo->weight) / ($height_meters * $height_meters);
  }
  
  public static function computeBMR($userInfo, $bodyInfo, $weight = null) {
    if (!$userInfo) return null;
    if (!$bodyInfo) return null;
    if ($weight === null) $weight = $bodyInfo->weight;
    
    /*
    Men:   BMR =  66.470 + (13.75 x W) + (5.00 x H) - (6.75 x A)
    Women: BMR=   665.09 + (9.560 x W) + (1.84 x H) - (4.67 x A)
    */
    
    $sex = $userInfo->getSex();
    $age = $userInfo->getAgeInYears();
    
    if ($sex == H_UserInfo::SEX_MALE)
    {
      return 66.47 + (13.75 * $weight) + (5.0 * $bodyInfo->height) - (6.75 * $age);
    }
    else if ($sex == H_UserInfo::SEX_FEMALE)
    {
      return 665.09 + (9.56 * $weight) + (1.84 * $bodyInfo->height) - (4.67 * $age);
    }
    else
    {
      // sex unknown, calculating average
      return ((66.47 + (13.75 * $weight) + (5.0 * $bodyInfo->height) - (6.75 * $age)) +
              (665.09 + (9.56 * $weight) + (1.84 * $bodyInfo->height) - (4.67 * $age)))/2.0;
    }
  }
}
