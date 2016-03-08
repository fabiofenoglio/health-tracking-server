<?php

class H_UiCells extends F_BaseStatic
{
  public static function getTimeCell($el) {
		$date_format = (time() - $el->time >= F_UtilsTime::AN_YEAR) ? 
			"j M y" : "j M";
		
		$timeToken = date("H:i", $el->time);
		
		return date($date_format, $el->time) . "<br/>" . 
			($timeToken != "00:00" ? 
			 "<small>".date("H:i", $el->time)."</small>" : 
			 ""
			);
	}
	
	public static function getMissingData() {
		return "<small>???</small>";
	}
}
