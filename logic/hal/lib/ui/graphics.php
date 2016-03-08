<?php

class H_UiGraphics extends F_BaseStatic
{
  const PIE_LEFT_COLOR = 			'#CCCCCC';
	const COLUMN_ADDING_COLOR = '#C59BDE';
	const LINE_TARGET_COLOR =		'#667744';
	
	public static function getColumnColorFromPercentage($value, $target) {
		$diff = ($value - $target) / $target;
    
    if ($diff >= 0.0) {
			$k = 150;
      if ($diff > 0.5) $diff = 0.5;
      $r = (int)(30 + $k * ($diff / 0.5));
      $g = (int)(200 - $k * ($diff / 0.5));
    }
    else {
			$k = 110;
      $diff = - $diff;
      if ($diff > 0.5) $diff = 0.5;
      $r = (int)(30 + $k * ($diff / 0.5));
      $g = (int)(200 - $k * ($diff / 0.5));
    }
    
    $b = 40;
    $cr = $r < 16 ? "0".dechex($r) : dechex($r);
    $cg = $g < 16 ? "0".dechex($g) : dechex($g);
    $cb = $b < 16 ? "0".dechex($b) : dechex($b);
		
		return "#".$cr.$cg.$cb;
	}
}
