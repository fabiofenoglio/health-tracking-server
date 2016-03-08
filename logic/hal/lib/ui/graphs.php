<?php

F_Library::importExternal("highcharts");
use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;

class H_UiGraphs extends F_BaseStatic
{
  public static function getSequentialId() {
    static $cnt = 0;
    $callId = F_UtilsRandom::generateRandomAlphaNum(20) . "_" . ($cnt++);
    return $callId;
  }
  
  public static function getBasicPie() {
    $callId = self::getSequentialId();
    $renderTo = "call".$callId."_container";
    
    $c = new Highchart();
		$c->chart->renderTo = $renderTo;
		$c->chart->plotBackgroundColor = null;
		$c->chart->plotBorderWidth = null;
		$c->chart->plotShadow = false;
		$c->tooltip->formatter = new HighchartJsExpr(
				"function() { return '<b>'+ this.point.name +'</b><br/>'+ this.y +' %'; }");
		$c->plotOptions->pie->allowPointSelect = 1;
		$c->plotOptions->pie->cursor = "pointer";
		$c->plotOptions->pie->dataLabels->enabled = false;
		$c->plotOptions->pie->showInLegend = 0;
		
    return array($c, $renderTo, $callId);
  }
  
  public static function getBasicColumn() {
    $callId = self::getSequentialId();
    $renderTo = "call".$callId."_container";
    
    $c = new Highchart();
    $c->chart->renderTo = $renderTo;
    $c->chart->type = "column";
    $c->subtitle->text = "";
    $c->yAxis->min = 0;
    $c->legend->layout = "vertical";
    $c->legend->backgroundColor = "#FFFFFF";
    $c->legend->align = "left";
    $c->legend->verticalAlign = "top";
    $c->legend->x = 100;
    $c->legend->y = 70;
    $c->legend->floating = 1;
    $c->legend->shadow = 1;
    $c->tooltip->formatter = new HighchartJsExpr("function() {
        return '' + this.x +': '+ this.y +' %';}");
    $c->plotOptions->column->pointPadding = 0.2;
    $c->plotOptions->column->borderWidth = 0;
    $c->plotOptions->column->showInLegend = 0;
		$c->plotOptions->line->showInLegend = 0;
    $c->xAxis->categories = array();
    
    return array($c, $renderTo, $callId);
  }
	
	public static function getBasicBar() {
    $callId = self::getSequentialId();
    $renderTo = "call".$callId."_container";
    
    $c = new Highchart();
    $c->chart->renderTo = $renderTo;
    $c->chart->type = "bar";
    $c->subtitle->text = "";
    $c->yAxis->min = 0;
    $c->legend->layout = "vertical";
    $c->legend->backgroundColor = "#FFFFFF";
    $c->legend->align = "left";
    $c->legend->verticalAlign = "top";
    $c->legend->x = 100;
    $c->legend->y = 70;
    $c->legend->floating = 1;
    $c->legend->shadow = 1;
    $c->tooltip->formatter = new HighchartJsExpr("function() {
        return '' + this.x +': '+ this.y +' %';}");
    $c->plotOptions->bar->pointPadding = 0.2;
    $c->plotOptions->bar->borderWidth = 0;
    $c->plotOptions->bar->showInLegend = 0;
		$c->plotOptions->line->showInLegend = 0;
    $c->xAxis->categories = array();
    
    return array($c, $renderTo, $callId);
  }
}
