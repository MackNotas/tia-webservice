<?php

include_once "moodleHTMLParser.php";

function performMoodleLogin () {


$debug 		= array_key_exists('debug', $_GET) 			? $_GET['debug'] 		: false;
$debug_logs = array_key_exists('debug_logs', $_GET) 	? $_GET['debug_logs'] 	: false;
$simulate 	= array_key_exists('simulate', $_GET) 		? $_GET['simulate'] 	: false;

$curl = CurlHelper::sharedInstance();
// $curl->closeCurl();
$curl->setMoodleCurl(URL_MOODLE_LOGIN, userMoodleLoginParams());
$curl->getHTMLFromRequest();

//
//	Request na página export.php para obter o 'sesskey'
//

$curl->setMoodleCurl(URL_MOODLE_EXPORT);

$sessKeyContent = $curl->getHTMLFromRequest();

$doc = new DOMDocument();
@$doc->loadHTML($sessKeyContent);

$sessKey;

$inputs = $doc->getElementsByTagName("input");

foreach($inputs as $node) {
	foreach($node->attributes as $attribute) {
	    if($attribute->nodeName == 'name' && $attribute->nodeValue == 'sesskey') {
	        $sessKey = $node->getAttribute('value');
	    }
	}
}

//
//	Request novamente na página, mas enviando o sessKey para pegar a url
//

$curl->setMoodleCurl(URL_MOODLE_EXPORT, userMoodleCalendarParamsWithSessionKey($sessKey));

$calUrlContent = $curl->getHTMLFromRequest();
// echo $calUrlContent;exit;
$calDoc = new DOMDocument();
@$calDoc->loadHTML($calUrlContent);

$calURL;

$inputs = $calDoc->getElementsByTagName("div");

foreach($inputs as $node) {
	foreach($node->attributes as $attribute) {
	    if($attribute->nodeName == 'class' && $attribute->nodeValue == 'generalbox calendarurl') {
	        $calURL = extractCalURLFromText($node->nodeValue);
	    }
	}
}

return parseiCalWithURL($calURL);

}

/**
 * Monta a URL de método POST do Moodle do Mackenzie.
 * @param  Tia do usuário
 * @param  Senha do usuário
 * @return Dicionário montado
 */
function userMoodleLoginParams () {

	return ["username" => USER_TIA,
			"password" => USER_PASS];
}

function userMoodleCalendarParamsWithSessionKey ($sessKey) {

	return ["_qf__core_calendar_export_form" 	=> "1", 
		    "events[exportevents]" 				=> "all", 
		    "period[timeperiod]" 				=> "custom",
		    "generateurl" 						=> "Obter+URL+do+calendário", 
		    "sesskey" 							=> $sessKey];
}

function extractCalURLFromText ($fullText) {

	$pattern = "(http?:\/\/[^\s]+)";
	preg_match($pattern, $fullText, $matches);
	
	return count($matches) > 0 ? $matches[0] : false;
}

?>