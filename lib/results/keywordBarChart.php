<?php
/** 
* TestLink Open Source Project - http://testlink.sourceforge.net/ 
* $Id: keywordBarChart.php,v 1.5 2007/05/15 13:56:59 franciscom Exp $ 
*
* @author	Kevin Levy
*/
require_once('../../third_party/charts/charts.php');
require_once('../functions/results.class.php');
require_once('../functions/testplan.class.php');

testlinkInitPage($db);
$tpID = $_SESSION['testPlanId']; 
$tp = new testplan($db);
$builds_to_query = 'a';
$suitesSelected = 'all';
$re = new results($db, $tp, $suitesSelected, $builds_to_query);

$arrDataKeys = $re->getAggregateKeywordResults();
$i = 0;
$arrDataKeys2 = null;

if ($arrDataKeys != null) {
   while ($keywordId = key($arrDataKeys)) {
      $arr = $arrDataKeys[$keywordId];
      $arrDataKeys2[$i] = $arr;
      $i++;
      next($arrDataKeys);
   }
}

$namesOfKeywordsArray = array();
$namesOfKeywordsArray[0] = "";

$passArray = array();
$passArray[0] = lang_get($g_tc_status_verbose_labels["passed"]);

$failArray = array();
$failArray[0] = lang_get($g_tc_status_verbose_labels["failed"]);

$blockedArray = array();
$blockedArray[0] = lang_get($g_tc_status_verbose_labels["blocked"]);

$notRunArray = array();
$notRunArray[0] = lang_get($g_tc_status_verbose_labels["not_run"]);

for ($i = 0 ; $i < sizeOf($arrDataKeys2); $i++)
{
	$keywordArr = $arrDataKeys2[$i];
	$namesOfKeywordsArray[$i + 1] = $keywordArr[0];
	$passArray[$i + 1] = $keywordArr[2];
	$failArray[$i + 1] = $keywordArr[3];
	$blockedArray[$i + 1] = $keywordArr[4];
	$notRunArray[$i + 1] = $keywordArr[5];
}

$chart[ 'chart_data' ] = array ($namesOfKeywordsArray, $passArray,$failArray, $blockedArray,$notRunArray);




$chart[ 'axis_value' ] = array ( 'font'=>"arial", 'bold'=>true, 'size'=>10, 'color'=>"000000", 'alpha'=>50, 'steps'=>6, 'prefix'=>"", 'suffix'=>"", 'decimals'=>0, 'separator'=>"", 'show_min'=>true );

$chart[ 'chart_border' ] = array ( 'color'=>"000000", 'top_thickness'=>0, 'bottom_thickness'=>3, 'left_thickness'=>0, 'right_thickness'=>0 );


$chart[ 'chart_grid_h' ] = array ( 'alpha'=>20, 'color'=>"000000", 'thickness'=>1, 'type'=>"solid" );
$chart[ 'chart_grid_v' ] = array ( 'alpha'=>20, 'color'=>"000000", 'thickness'=>1, 'type'=>"dashed" );
$chart[ 'chart_rect' ] = array ( 'x'=>125, 'y'=>75, 'width'=>500, 'height'=>400, 'positive_color'=>"ffffff", 'negative_color'=>"000000", 'positive_alpha'=>75, 'negative_alpha'=>15 );
$chart[ 'chart_transition' ] = array ( 'type'=>"drop", 'delay'=>0, 'duration'=>2, 'order'=>"series" );
$chart[ 'chart_type' ] = "stacked column"; 

$chart[ 'axis_category' ] = array ('orientation'=>"diagonal_down");

$chart[ 'draw' ] = array ( array ( 'transition'=>"slide_up", 'delay'=>1, 'duration'=>.5, 'type'=>"text", 'color'=>"000033", 'alpha'=>15, 'font'=>"arial", 'rotation'=>-90, 'bold'=>true, 'size'=>64, 'x'=>0, 'y'=>295, 'width'=>300, 'height'=>60, 'text'=>"Keywords", 'h_align'=>"right", 'v_align'=>"middle" ),
                           array ( 'transition'=>"slide_up", 'delay'=>1, 'duration'=>.5, 'type'=>"text", 'color'=>"ffffff", 'alpha'=>40, 'font'=>"arial", 'rotation'=>-90, 'bold'=>true, 'size'=>25, 'x'=>35, 'y'=>300, 'width'=>300, 'height'=>50, 'text'=>"report", 'h_align'=>"right", 'v_align'=>"middle" ) );

$chart[ 'legend_label' ] = array ( 'layout'=>"horizontal", 'font'=>"arial", 'bold'=>true, 'size'=>13, 'color'=>"444466", 'alpha'=>90 ); 
$chart[ 'legend_rect' ] = array ( 'x'=>125, 'y'=>10, 'width'=>250, 'height'=>10, 'margin'=>5, 'fill_color'=>"ffffff", 'fill_alpha'=>35, 'line_color'=>"000000", 'line_alpha'=>0, 'line_thickness'=>0 ); 
$chart[ 'legend_transition' ] = array ( 'type'=>"slide_left", 'delay'=>0, 'duration'=>1 );

$chart[ 'series_color' ] = array ("00FF00", "FF0000", "0000FF", "000000");

SendChartData ( $chart );
?>
