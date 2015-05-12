<?php
/**
 * @file
 * PHP Input filter.
 * 
 * @version $Id$
 */

define('TOKEN_TYPE', 0);
define('TOKEN_STRING', 1);
define('TOKEN_LINE', 2);
define('FUNCTION_PARAMETERS', 'FUNCTION_PARAMETERS');

$__PREV__POSITION__ = 0;

$sFileName = $argv[1];
if (!file_exists($sFileName)) {
	exit(1);
}

$sFileContents = file_get_contents($sFileName);
$aTokens = token_get_all($sFileContents);

while($mToken = current($aTokens)) {
//	printf('(%s) %s', is_array($mToken) ? token_name($mToken[TOKEN_TYPE]) : '_', is_array($mToken) ? $mToken[TOKEN_STRING] : $mToken);
	
	if (!is_array($mToken)) {
		next($aTokens);
		continue;
	}
	if ($mToken[TOKEN_TYPE] == T_VARIABLE) {
		setVariableType($aTokens);
	} else if ($mToken[TOKEN_TYPE] == T_FUNCTION) {
		setFunctionType($aTokens);
		setFunctionParamType($aTokens);
	}
	next($aTokens);
}


foreach($aTokens as $mToken) {
	echo is_array($mToken) ? $mToken[TOKEN_STRING] : $mToken;
}

function setFunctionParamType(&$aTokens)
{
	_s_pos($aTokens);
	$aToken = current($aTokens);
	if (!is_array($aToken) || $aToken[TOKEN_TYPE] != T_FUNCTION) {
		return false;
	}
	$bInParamList = $bTypedParam = false;
	while($mToken = next($aTokens)) {
		if (is_string($mToken) && trim($mToken) == '(') {
			$bInParamList = true;
			continue;
		}
		if (is_string($mToken) && trim($mToken) == ')') {
			break;
		}
		if ($bInParamList) {
			if (is_string($mToken) && trim($mToken) == ',') {
				$bTypedParam = false;
			}
			if (!is_array($mToken)) {
				continue;
			}
			if ($mToken[TOKEN_TYPE] == T_STRING || $mToken[TOKEN_TYPE] == T_ARRAY) {
				$bTypedParam = true;
				continue;
			}
			if (!$bTypedParam && $mToken[TOKEN_TYPE] == T_VARIABLE) {
				if (is_array($aToken[FUNCTION_PARAMETERS]) &&
					isset($aToken[FUNCTION_PARAMETERS][$mToken[TOKEN_STRING]])) {
					$aTokens[key($aTokens)][TOKEN_STRING] = sprintf('%s %s',
						$aToken[FUNCTION_PARAMETERS][$mToken[TOKEN_STRING]],
						$mToken[TOKEN_STRING]
					);
				}
			}
		}
	}
	_r_pos($aTokens);
}

function setFunctionType(&$aTokens)
{
	_s_pos($aTokens);
	$aToken = current($aTokens);
	$nTokenPos = key($aTokens);
	if (!is_array($aToken) || $aToken[TOKEN_TYPE] != T_FUNCTION) {
		return false;
	}
	while($mToken = prev($aTokens)) {
		if (!is_array($mToken)) {
			continue;
		}
		// Next token starts at same line...
		if ($mToken[TOKEN_LINE] == $aToken[TOKEN_LINE]) {
			continue;
		}
		// Nor a 'comment' token nor 'space' token nor an 'access type' token...
		if (!in_array($mToken[TOKEN_TYPE], array(T_DOC_COMMENT, T_WHITESPACE, T_PRIVATE, T_PUBLIC, T_PROTECTED))) {
			break;
		}
		// Not a 'comment' token...
		if ($mToken[TOKEN_TYPE] != T_DOC_COMMENT) {
			continue;
		}
		// Embedded @return comment
		$sPattern = '~(\p{Z}+@return\p{Z}+)@type\p{^L}+([\p{L}_0-9\|]+)\p{Z}?(.*)~';
		if (preg_match($sPattern, $mToken[TOKEN_STRING], $aMatch)) {
			$aTokens[$nTokenPos][TOKEN_STRING] = $aMatch[2] . ' ' . $aTokens[$nTokenPos][TOKEN_STRING];
			if (!empty($aMatch[3])) {
				$aTokens[key($aTokens)][TOKEN_STRING] = $mToken[TOKEN_STRING] = preg_replace($sPattern, '\1\3', $mToken[TOKEN_STRING]);
			}
		} else {
			$aTokens[$nTokenPos][TOKEN_STRING] = 'void ' . $aTokens[$nTokenPos][TOKEN_STRING];
		}
		// Embedded @param comment (collects params to be used in setFunctionParamType)
		$sPattern = '~(\p{Z}+@param\p{Z}+)(\$[\p{L}_0-9\|]+)\p{Z}+@type\p{^L}+([\p{L}_0-9\|]+)~';
		if (preg_match_all($sPattern, $mToken[TOKEN_STRING], $aMatch)) {
			$aParams = array();
			for ($i = 0; $i < count($aMatch[0]); $i++) {
				$aParams[$aMatch[2][$i]] = $aMatch[3][$i];
			}
			$aTokens[$nTokenPos][FUNCTION_PARAMETERS] = $aParams;
			$aTokens[key($aTokens)][TOKEN_STRING] = preg_replace($sPattern, '\1\2', $mToken[TOKEN_STRING]);
		}
		break;
	}
	_r_pos($aTokens);
}

function setVariableType(&$aTokens)
{
	_s_pos($aTokens);
	$aToken = current($aTokens);
	$nTokenPos = key($aTokens);
	if (!is_array($aToken) || $aToken[TOKEN_TYPE] != T_VARIABLE) {
		return false;
	}
	while($mToken = next($aTokens)) {
		if (!is_array($mToken)) {
			continue;
		}
		// Next token starts at new line...
		if ($mToken[TOKEN_LINE] > $aToken[TOKEN_LINE]) {
			break;
		}
		// Not a 'comment' token...
		if ($mToken[TOKEN_TYPE] != T_COMMENT) {
			continue;
		}
		// Embedded @type comment
		$sPattern = '~\p{Z}+@type\p{^L}+([\p{L}_0-9\|]+)~';
		if (preg_match($sPattern, $mToken[TOKEN_STRING], $aMatch)) {
			$aTokens[$nTokenPos][TOKEN_STRING] = $aMatch[1] . ' ' . $aTokens[$nTokenPos][TOKEN_STRING];
			$aTokens[key($aTokens)][TOKEN_STRING] = preg_replace($sPattern, '', $mToken[TOKEN_STRING]);
		}
		break;
	}
	_r_pos($aTokens);
}

function _s_pos(&$aTokens)
{
	$GLOBALS['__PREV__POSITION__'] = key($aTokens);
}

function _r_pos(&$aTokens)
{
	if (key($aTokens) == $GLOBALS['__PREV__POSITION__']) {
		return;
	}
	if (key($aTokens) > $GLOBALS['__PREV__POSITION__']) {
		while(prev($aTokens) && key($aTokens) > $GLOBALS['__PREV__POSITION__']);
		return;
	}
	if (key($aTokens) < $GLOBALS['__PREV__POSITION__']) {
		while(next($aTokens) && key($aTokens) < $GLOBALS['__PREV__POSITION__']);
		return;
	}
}
