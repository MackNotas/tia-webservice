<?php

include_once "DynamoDB_helper.php";
include_once "./token-manager/token_helper.php";

/*
|--------------------------------------------------------------------------
| FUNCTIONS UTILS
|--------------------------------------------------------------------------
*/

function getRandomCookieFileName() {

    $keysTable = KEYS_TABLE;
    $randomCookieKey = "";
    $keysTableSize = count($keysTable) - 1;

    for ($i=0; $i < 25; $i++) { 
        $randomCookieKey .= $keysTable[rand(0, $keysTableSize)];
    }
    
    return  "./cookie" . $randomCookieKey . ".cookie";
}


function p_r() {
    $args = func_get_args();
    foreach ($args as $a) {
        echo '<pre>';
        print_r($a);
        echo '</pre>';
    }
}

//	 - Nginx não tem getallheaders() e por isso precisa declarar uma funcao com ação semelhante

if (!function_exists('getallheaders')) {
	function getallheaders() {
	    $headers = '';
	    foreach ($_SERVER as $name => $value) {
	    	if (substr($name, 0, 5) == 'HTTP_') {
	        	$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
	        }
	    }
	    return $headers;
	}
} 

/*
|--------------------------------------------------------------------------
| DATE HELPER
|--------------------------------------------------------------------------
*/

function getWeekDay($strDate) {
	setlocale(LC_TIME, 'pt_BR.UTF-8');
	date_default_timezone_set('America/Sao_Paulo');
	$diaMesAno = getFullDate($strDate);
	return trim($strDate) != "" ? ucwords(strftime("%a", strtotime($diaMesAno))) : "";	
}

function getMonthString($strDate) {
	setlocale(LC_TIME, 'pt_BR.UTF-8');
	date_default_timezone_set('America/Sao_Paulo');
	$diaMesAno = getFullDate($strDate);
	return trim($strDate) != "" ? ucwords(strftime("%B", strtotime($diaMesAno))) : "";	
}

function getMonthNumber($strDate) {
	setlocale(LC_TIME, 'pt_BR.UTF-8');
	date_default_timezone_set('America/Sao_Paulo');
	$diaMesAno = getFullDate($strDate);
	return trim($strDate) != "" ? strftime("%m", strtotime($diaMesAno)) : "";	
}

function getDay($strDate) {
	setlocale(LC_TIME, 'pt_BR.UTF-8');
	date_default_timezone_set('America/Sao_Paulo');
	$diaMesAno = getFullDate($strDate);
	return trim($strDate) != "" ? trim(strftime("%e", strtotime($diaMesAno))) : "";	
}

function getHourComplete($strDate) {
	setlocale(LC_TIME, 'pt_BR.UTF-8');
	date_default_timezone_set('America/Sao_Paulo');
	$diaMesAno = getFullDate($strDate);
	return trim($strDate) != "" ? trim(strftime("%H:%M", strtotime($diaMesAno))) : "";	
}

function getFullDate($strDate) {
	setlocale(LC_TIME, 'pt_BR.UTF-8');
	date_default_timezone_set('America/Sao_Paulo');
	return str_replace("/", "-", trim($strDate)) . "-" . date("Y");
}

function getFullDateFromUnix($strDateUnix) {
	setlocale(LC_TIME, 'pt_BR.UTF-8');
	date_default_timezone_set('America/Sao_Paulo');
	return date("d/m/Y", $strDateUnix);
}

function getCurrentDate() {
	setlocale(LC_TIME, 'pt_BR.UTF-8');
	date_default_timezone_set('America/Sao_Paulo');
	return date("dmY");
}

function getCurrentDateYMD() {
	setlocale(LC_TIME, 'pt_BR.UTF-8');
	date_default_timezone_set('America/Sao_Paulo');
	return date("Ymd");
}

function renderHTML($htmlFile) {
	include_once $htmlFile;
}

?>