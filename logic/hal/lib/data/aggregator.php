<?php

class H_DataAggregator extends F_BaseStatic
{
	public static function aggregateFoodRecordsEnergy($foodRecords, $foodInfos = null) {
		$d = 0.0;
			
		if ($foodInfos === null) $foodInfos = array();
  	$foodComponent = H_FoodComponent::loadOrderedListCached()[H_FoodComponent::ID_ENERGY];
		
		foreach ($foodRecords as $foodRecord) {
			if ($foodRecord->foodid < 1) continue;
			if ($foodRecord->amount <= 0.0) continue;

			if (!isset($foodInfos[$foodRecord->foodid])) {
				$foodInfos[$foodRecord->foodid] = H_FoodInfo::load($foodRecord->foodid);
			}
			$foodInfo = $foodInfos[$foodRecord->foodid];

			$property_name = $foodComponent->info_property;
			$toAdd = (float)$foodRecord->amount * (float)$foodInfo->$property_name / 100.0;
			$d += $toAdd;
		}
		
		return $d;
	}
	
	public static function aggregateFoodRecords($foodRecords, $foodInfos = null, $regime = null) {
		$d = array();
			
		if ($foodInfos === null) $foodInfos = array();
  	$foodComponents = H_FoodComponent::loadOrderedListCached();
		
		foreach ($foodComponents as $foodComponent) {
			if ($regime !== null) {
				if (!isset($regime->data->components[$foodComponent->id])) {
					continue;
				}
			}
			$d[$foodComponent->id] = 0.0;
		}
		
		foreach ($foodRecords as $foodRecord) {
			if ($foodRecord->foodid < 1) continue;
			if ($foodRecord->amount <= 0.0) continue;

			if (!isset($foodInfos[$foodRecord->foodid])) {
				$foodInfos[$foodRecord->foodid] = H_FoodInfo::load($foodRecord->foodid);
			}
			$foodInfo = $foodInfos[$foodRecord->foodid];

			foreach ($foodComponents as $foodComponent) {
				if ($regime !== null) {
					if (!isset($regime->data->components[$foodComponent->id])) {
						continue;
					}
				}
				
				$property_name = $foodComponent->info_property;
				$toAdd = (float)$foodRecord->amount * (float)$foodInfo->$property_name / 100.0;
				$d[$foodComponent->id] += $toAdd;
			}
		}
		
		return $d;
	}
	
  public static function getFoodData($userId, $dayStart, $dayEnd = null) {
    if ($dayEnd === null)
      $dayEnd = $dayStart + F_UtilsTime::A_DAY;
    
    if ($dayStart > $dayEnd)
    {
      $swap = $dayEnd;
      $dayEnd = $dayStart;
      $dayStart = $swap;
    }
    
    $cacheKey = "data.aggregated.food.user#".$userId.".interval#".$dayStart."-".$dayEnd;
    if (($result = H_Caching::get($cacheKey))) {
      return $result;
    }
    
    $data = new JObject();
    $data->user = F_User::getUserById($userId);
    $data->userInfo = H_UserInfo::loadByUser($userId);
    $data->mergedBodyRecord = H_BodyRecord::getMerged($userId);
		
    $data->caloriesRecordsFit = H_FitData::query(
      "userid=$userId AND date>=$dayStart AND date<$dayEnd AND type=".H_FitData::TYPE_CALORIES, 
      "date DESC"
    );
    
    $data->foodRecords = H_FoodRecord::query(
      "userid=$userId AND time>=$dayStart AND time<$dayEnd", 
      "time DESC"
    );
    
    $data->activeRegime = H_FoodRegime::getUserActiveOrDefault($userId);

    $data->foodInfos = array();
    foreach ($data->foodRecords as $foodRecord) {
      if ($foodRecord->foodid < 1) continue;
      if (isset($data->foodInfos[$foodRecord->foodid])) continue;
      
      $data->foodInfos[$foodRecord->foodid] = H_FoodInfo::load($foodRecord->foodid);
    }
    
    $data->foodComponents = H_FoodComponent::loadUnorderedListCached();
    
    $data->completed = true;
    if (empty($data->userInfo) ||
        empty($data->activeRegime) || 
        empty($data->mergedBodyRecord)) 
      $data->completed = false;
    
    if ($data->completed) {
			$data->dailyCaloriesOut = H_DataProviderFood::getDailyCaloriesOut($userId);
				
      $data->bmr = H_FoodCalculator::computeBMR(
				$data->userInfo, 
				$data->mergedBodyRecord
			);
			
      $data->energyGoal = 
        $data->dailyCaloriesOut *
		    $data->activeRegime->getComponent(H_FoodComponent::ID_ENERGY)->goal_percentage;
      
      $data->targetComponents = array();
      
      foreach ($data->activeRegime->getOrderedComponents() as $componentId => $componentSpecification) {
        if (!$componentSpecification->monitor) continue;
        $foodInfo = $data->foodComponents[$componentId];

        if (!$foodInfo) continue;
        
        if ($componentId == H_FoodComponent::ID_ENERGY) {
          $target_amount = $data->energyGoal;
        }
        else {
          $target_amount = 
            $data->energyGoal *
            $componentSpecification->goal_percentage *
            ($data->userInfo->getSex() == H_UserInfo::SEX_MALE ? $foodInfo->gda_m : $foodInfo->gda_f) *
            (0.01);
        }
        $data->targetComponents[$componentId] = $target_amount;
      }
    }
    else {
      $data->bmr = null;
      $data->energyGoal = null;
      $data->targetComponents = null;
			$data->dailyCaloriesOut = null;
    }
    
    H_Caching::set($cacheKey, $data);
    return $data;
  }
}
