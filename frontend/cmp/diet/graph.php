<?php defined("_JEXEC") or die();

// Import graph library
F_Library::importExternal("highcharts");
use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;

$chart = new Highchart();
$chart->chart->renderTo = "graph0";
$chart->chart->type = "bar";

$chart->title->text = "Historic World Population by Region";
$chart->subtitle->text = "Source: Wikipedia.org";

$chart->xAxis->categories = array(
    'Africa',
    'America',
    'Asia',
    'Europe',
    'Oceania'
);
$chart->xAxis->title->text = null;

$chart->yAxis->min = 0;
$chart->yAxis->title->text = "Population (millions)";
$chart->yAxis->title->align = "high";

$chart->tooltip->formatter = new HighchartJsExpr(
    "function() {
    return '' + this.series.name +': '+ this.y +' millions';}");

$chart->plotOptions->bar->dataLabels->enabled = 1;

$chart->legend->layout = "vertical";
$chart->legend->align = "right";
$chart->legend->verticalAlign = "top";
$chart->legend->x = - 100;
$chart->legend->y = 100;
$chart->legend->floating = 1;
$chart->legend->borderWidth = 1;
$chart->legend->backgroundColor = "#FFFFFF";
$chart->legend->shadow = 1;

$chart->credits->enabled = false;

$chart->series[] = array(
    'name' => "Year 1800",
    'data' => array(
				107, 30,
        31,
        635,
        203,
        2
    )
);
$chart->series[] = array(
    'name' => "Year 1900",
    'data' => array(
        133,
        156,
        947,
        408,
        6
    )
);
$chart->series[] = array(
    'name' => "Year 2008",
    'data' => array(
        973,
        914,
        4054,
        732,
        34
    )
);

echo $chart->printScripts(true);

echo "<div id='graph0'></div>";

echo "<script>";
echo $chart->render("chart");
echo "</script>";
