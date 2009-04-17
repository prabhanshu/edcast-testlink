<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * Filename $RCSfile: inputparameter.inc.php,v $
 *
 * @version $Revision: 1.4 $
 * @modified $Date: 2009/04/17 19:57:32 $ by $Author: schlundus $
 * 
**/
require_once("object.class.php");
require_once("inputparameter.class.php");

function P_PARAMS($paramInfo)
{
	return GPR_PARAMS("POST",$paramInfo);
}

function G_PARAMS($paramInfo)
{
	return GPR_PARAMS("GET",$paramInfo);
}

function R_PARAMS($paramInfo)
{
	return GPR_PARAMS("REQUEST",$paramInfo);
}

function GPR_PARAMS($source,$paramInfo)
{
	foreach($paramInfo as $pName => &$info)
	{
		array_unshift($info,$source);
	}
	return I_PARAMS($paramInfo);
}

function P_PARAM_STRING_N($name,$minLen = null,$maxLen = null,$regExp = null,
                          $pfnValidation = null,$pfnNormalization = null)
{
	return GPR_PARAM_STRING_N("POST",$name,$minLen,$maxLen,$regExp,$pfnValidation,$pfnNormalization);
}

function P_PARAM_INT($name,$minVal = null,$maxVal = null,$pfnValidation = null)
{
	return GPR_PARAM_INT("POST",$name,$minVal,$maxVal,$pfnValidation);
}

function P_PARAM_INT_N($name,$maxVal = null,$pfnValidation = null)
{
	return GPR_PARAM_INT_N("POST",$name,$maxVal,$pfnValidation);
}

function G_PARAM_STRING_N($name,$minLen = null,$maxLen = null,$regExp = null,$pfnValidation = null,$pfnNormalization = null)
{
	return GPR_PARAM_STRING_N("GET",$name,$minLen,$maxLen,$regExp,$pfnValidation,$pfnNormalization);
}

function G_PARAM_INT($name,$minVal = null,$maxVal = null,$pfnValidation = null)
{
	return GPR_PARAM_INT("GET",$name,$minVal,$maxVal,$pfnValidation);
}

function G_PARAM_INT_N($name,$maxVal = null,$pfnValidation = null)
{
	return GPR_PARAM_INT_N("GET",$name,0,$maxVal,$pfnValidation);
}

function GPR_PARAM_INT_N($gpr,$name,$maxVal = null,$pfnValidation = null)
{
	return GPR_PARAM_INT($gpr,$name,0,$maxVal,$pfnValidation);
}

function G_PARAM_ARRAY_INT($name,$pfnValidation = null)
{
	return GPR_PARAM_INT("GET",$name,$pfnValidation);
}

function P_PARAM_ARRAY_INT($name,$pfnValidation = null)
{
	return GPR_PARAM_INT("POST",$name,$pfnValidation);
}

function I_PARAMS($paramInfo)
{
	$params = null;
	foreach($paramInfo as $pName => $info)
	{
		$source = $info[0];
		$type = $info[1];
		for($i = 1;$i <= 5;$i++)
		{
			$varName = "p{$i}";
			$value = isset($info[$i+1]) ? $info[$i+1] : null;
			$$varName = $value;
		}
		
		switch($type)
		{
			case tlInputParameter::ARRAY_INT:
				$pfnValidation = $p1;
				$value = GPR_PARAM_ARRAY_INT($source,$pName,$pfnValidation);
				break;
			case tlInputParameter::ARRAY_STRING_N:
				$pfnValidation = $p1;
				$value = GPR_PARAM_ARRAY_STRING_N($source,$pName,$pfnValidation);
				break;
			case tlInputParameter::INT_N:
				$maxVal = $p1;
				$pfnValidation = $p2;
				$value = GPR_PARAM_INT_N($source,$pName,$maxVal,$pfnValidation);
				break;
			case tlInputParameter::INT:
				$minVal = $p1;
				$maxVal = $p2;
				$pfnValidation = $p3;
				$value = GPR_PARAM_INT($source,$pName,$minVal,$maxVal,$pfnValidation);
				break;
			case tlInputParameter::STRING_N:
				$minLen = $p1;
				$maxLen = $p2;
				$regExp = $p3;
				$pfnValidation = $p4;
				$pfnNormalization = $p5;
				$value = GPR_PARAM_STRING_N($source,$pName,$minLen,$maxLen,$regExp,$pfnValidation,$pfnNormalization);
				break;
		}
		$params[$pName] = $value;
	}
	return $params;
}


function GPR_PARAM_STRING_N($gpr,$name,$minLen = null,$maxLen = null,$regExp = null,
                            $pfnValidation = null,$pfnNormalization = null)
{
	$vInfo = new tlStringValidationInfo();
	if (!is_null($minLen))
		$vInfo->minLen = $minLen;
	if (!is_null($maxLen))
		$vInfo->maxLen = $maxLen;
	$vInfo->trim = tlStringValidationInfo::TRIM_BOTH;
	$vInfo->bStripSlashes = true;
	if (!is_null($regExp))
		$vInfo->regExp = $regExp;
	if (!is_null($pfnValidation))
		$vInfo->pfnValidation = $pfnValidation;
	if (!is_null($pfnNormalization))
		$vInfo->pfnNormalization = $pfnNormalization;
	
	$pInfo = new tlParameterInfo();
	$pInfo->source = $gpr;
	$pInfo->name = $name;
	
	$iParam = new tlInputParameter($pInfo,$vInfo);
	return $iParam->value();
}

function GPR_PARAM_INT($gpr,$name,$minVal = null,$maxVal = null,$pfnValidation = null)
{
	$vInfo = new tlIntegerValidationInfo();
	if (!is_null($minVal))
		$vInfo->minVal = $minVal;
	if (!is_null($maxVal))
		$vInfo->maxVal = $maxVal;
	if (!is_null($pfnValidation))
		$vInfo->pfnValidation = $pfnValidation;
		
	$pInfo = new tlParameterInfo();
	$pInfo->source = $gpr;
	$pInfo->name = $name;
	
	$iParam = new tlInputParameter($pInfo,$vInfo);
	return $iParam->value();
}

function GPR_PARAM_ARRAY_INT($gpr,$name,$pfnValidation = null)
{
	return GPR_PARAM_ARRAY($gpr,tlInputParameter::INT,$name,$pfnValidation);
}

function GPR_PARAM_ARRAY_STRING_N($gpr,$name,$pfnValidation = null)
{
	return GPR_PARAM_ARRAY($gpr,tlInputParameter::STRING_N,$name,$pfnValidation);
}

function GPR_PARAM_ARRAY($gpr,$type,$name,$pfnValidation)
{
	$vInfo = new tlArrayValidationInfo();
	if (!is_null($pfnValidation))
	{
		$vInfo->pfnValidation = $pfnValidation;
    }
    if ($type == tlInputParameter::STRING_N) 
		$vInfo->validationInfo = new tlStringValidationInfo();
	else
		$vInfo->validationInfo = new tlIntegerValidationInfo();
		
	$pInfo = new tlParameterInfo();
	$pInfo->source = $gpr;
	$pInfo->name = $name;
	
	$iParam = new tlInputParameter($pInfo,$vInfo);
	
	return $iParam->value();
}
/*
function check($value)
{
	if (strlen($value) != 4)
		return false;
	return true;
}
function norm($value)
{
	return str_replace("b","",$value);
}
$_POST["HelloInt"] = "a5";
$_POST["Hello"] = utf8_encode("xabbababa");
//$iParam = P_PARAM_INT("HelloInt",null,null,"check");
//$iParam = P_PARAM_INT_N("HelloInt",null,"check");
$_POST["Hello"] = utf8_encode("abbababa");
$iParam = P_PARAM_STRING_N("Hello",1,15,null,"check","norm");
$_POST["Hello"] = utf8_encode("aaaa");
$iParam = P_PARAM_STRING_N("Hello",1,15,'/^aaaa$/');
$iParam = P_PARAM_INT("HelloInt");

$params = array(
	"Hello" => array("POST",tlInputParameter::STRING_N,1,15,null,"check","norm"),
	"HelloInt" => array("POST",tlInputParameter::INT_N),
);
var_dump(I_PARAMS($params));
*/
?>