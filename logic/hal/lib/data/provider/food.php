<?php

class H_DataProviderFood extends F_BaseStatic
{
	public static function getDailyCaloriesOut($userId) {
		$cacheKey = "data.provider.food.dailycaloriesout.user#$userId";
		if (($result = H_Caching::get($cacheKey))) {
			return $result;
		}
		
		$o = H_FoodCalculator::computeDailyCaloriesOut($userId)->result;
		H_Caching::set($cacheKey, $o);
		return $o;
	}
}
