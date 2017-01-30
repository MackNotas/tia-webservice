<?php

include_once "config.php";
include_once "tiaHTMLParser_v2.php";
include_once "tia_JSON_Mobile_Parser_v1.php";
include_once "./moodle/moodleLogin.php";
include_once "curl_helper.php";

// =====================================================================================
//		  __  __            _    _   _       _                            _____ 
//		 |  \/  | __ _  ___| | _| \ | | ___ | |_ __ _ ___          __   _|___ / 
//		 | |\/| |/ _` |/ __| |/ /  \| |/ _ \| __/ _` / __|  _____  \ \ / / |_ \ 
//		 | |  | | (_| | (__|   <| |\  | (_) | || (_| \__ \ |_____|  \ V / ___) |
//		 |_|  |_|\__,_|\___|_|\_\_| \_|\___/ \__\__,_|___/           \_/ |____/
//
//			 					  ____   ___  _ ____  
//			 					 |___ \ / _ \/ | ___| 
//			 					   __) | | | | |___ \ 
//			 					  / __/| |_| | |___) |
//			 					 |_____|\___/|_|____/ 
//  
// =====================================================================================
                                                 
//
//	Pega os headers da request, e por proteção verifica se há um 'Contenttype'.
//

$request_headers = getallheaders();

if ($debug) {
	p_r($request_headers);
}

if ((isset($request_headers['Contenttype']) || array_key_exists('Contenttype', $request_headers))) {
	if ($request_headers['Contenttype'] !== "application/json") {
		missing_params_error();
	}
}
else {
	missing_params_error();
}

//
//	Pegar os Paremetros recebidos na request como JSON. São Eles:
//		- userTia
//		- userPass
//		- userUnidade
//		- tipo
//

$json_body = json_decode(file_get_contents("php://input"), true);

if ($debug) {
	p_r($json_body);
}

$userTia 		= 	!empty($json_body["userTia"])		?	$json_body["userTia"]		:	null;
$userPass		=	!empty($json_body["userPass"])		?	$json_body["userPass"]		:	null;
$userUnidade	=	!empty($json_body["userUnidade"])	?	$json_body["userUnidade"]	:	null;
$reqTipo		=	!empty($json_body["tipo"])			?	$json_body["tipo"]			:	null;

if (!$userTia || !$userPass || !$userUnidade || !$reqTipo) {
	missing_params_error();
}

define("USER_TIA", $userTia);
define("USER_PASS", $userPass);
define("USER_UNIDADE", $userUnidade);
define("USER_REQ_TIPO", $reqTipo);

$json_response_parsed;
$content;

# Inicializa Helpers
$ch = CurlHelper::sharedInstance();
$token_helper = TokenHelper::sharedInstance();

// ======================= FORCE LOGOUT - CONFIGS =========================
if ($logoutUserIfNeeded && USER_REQ_TIPO == TipoRequest::reqNota) {
	if (should_logout_user_parse()) {
		force_user_logout();
	}
}
// ========================================================================

/**
 *	Caso é Nota ou Faltas, verifica se o tia mobile está disponível
 * 	Caso contrário, jogue para o tia comum
 */

$is_mobile_available = false;

if (MOBILE_ALLOWED &&
	(USER_REQ_TIPO == TipoRequest::reqNota || 
	USER_REQ_TIPO == TipoRequest::reqFalta)) {

	$is_mobile_available = is_mobile_available();

	if ($is_mobile_available) {
		$mobile_json = getMobileJSON();

		if (USER_REQ_TIPO == TipoRequest::reqNota) {
			$json_response_parsed = getMackNotasJSONForNotasMobile($mobile_json["resposta"]);
		}

		else if (USER_REQ_TIPO == TipoRequest::reqFalta) {
			$json_response_parsed = getMackNotasJSONForFaltasMobile($mobile_json["resposta"]);
		}
	}
}

if ($debug) {
	p_r("Using Mobile: " . $is_mobile_available);
}


//=============================================================================================================================
//												INICIALIZA A REQUEST DESEJADA
//=============================================================================================================================
if (!$is_mobile_available) {
	$tia_token = getTIAToken();

	if (!$tia_token) {
		show_error("O Token está inválido!");
	}

	// Se o Token ocorreu certo, realiza o Login

	performTIALoginWithToken($tia_token);
}

//
//	Faz a requisição no TIA de acordo com a o reqTipo escolhido pelo usuario (nota, faltas etc...) e joga pro tiaHTMLParser
//
if (USER_REQ_TIPO == TipoRequest::reqNota && !$is_mobile_available) {
	$content = getTIAContentWithURL(URL_TIA_NOTA);
	$json_response_parsed = $notasFaltasMaintenance ? force_nota_maintenance() : tiaParserNotaWithContent($content);
}

else if (USER_REQ_TIPO == TipoRequest::reqHorario) {
	$content = getTIAContentWithURL(URL_TIA_HORARIO);
	$json_response_parsed = tiaParserHorarioWithContent($content);
}

else if (USER_REQ_TIPO == TipoRequest::reqFalta && !$is_mobile_available) {
	$content = getTIAContentWithURL(URL_TIA_FALTA);
	$json_response_parsed = $notasFaltasMaintenance ? force_falta_maintenance() : tiaParserFaltasWithContent($content);
}

else if(USER_REQ_TIPO == TipoRequest::reqLogin) {
	$ch->setTIACurl(URL_TIA_NOTA);
	$content = $ch->getHTMLFromRequest();
	$json_response_parsed = tiaParserValidarLoginWithContent($content);
}

else if(USER_REQ_TIPO == TipoRequest::reqAtivCompl) {
	$content = getTIAContentWithURL(URL_TIA_ATV_COMP);
	$json_response_parsed = tiaParserAtivComplWithContent($content);
}

else if(USER_REQ_TIPO == TipoRequest::reqCalendario) {
	$content = getTIAContentWithURL(URL_TIA_CALENDARIO);
	$iCalArray = performMoodleLogin(USER_TIA, USER_PASS);
	$json_response_parsed = tiaParserCalendarioWithContentAndMoodleCal($content, $iCalArray);
}

else if(USER_REQ_TIPO == TipoRequest::reqDesempenho){
	$content = getTIAContentWithURL(URL_TIA_HISTORICO);
	$json_response_parsed = tiaParserDesempenhoPessoal($content);
}

else if (!$is_mobile_available) {
	missing_params_error();
}

$ch->closeCurl();

if ($debug_logs) {
	date_default_timezone_set('America/Sao_Paulo');
	file_put_contents('./logs/logs_json.txt', $json_response_parsed . "\n\n" . date('l jS \of F Y h:i:s A') . "\n\n==========================================================================\n\n", FILE_APPEND | LOCK_EX);
	file_put_contents('./logs/logs_htmlcontent.txt', $content . "\n\n" . date('l jS \of F Y h:i:s A') . "================================================================================\n\n", FILE_APPEND | LOCK_EX);
}

if ($debug) {
	echo $content;
}
		
echo $json_response_parsed;
exit;

//=============================================================================================================================
//												FIM DO REQUEST DE LOGIN!
//=============================================================================================================================

/**
 * Seta um curl_setopt com o conteudo incial do login ($ch) e a url a ser obtida o content ($url).
 * O TIA pode devolver uma página invalida, mesmo o login estando OK. Por isso, caso o login seja invalido
 * É feita uma nova requisição para validar 100%.
 * 
 * @return HTML Content
 */
function getTIAContentWithURL($url) {

	$content;
	$curl = CurlHelper::sharedInstance();
	$curl->setTIACurl($url);

	for ($i = 0; $i < 2; $i++) {
		$content = $curl->getHTMLFromRequest();
		if (isLoginCorrectWithContent($content)) { break; }
	}
	return $content;
}

function getMobileJSON() {

	if (USER_REQ_TIPO == TipoRequest::reqNota) {
		$URL = URL_TIA_MOBILE_NOTAS;
	}

	else if (USER_REQ_TIPO == TipoRequest::reqFalta) {
		$URL = URL_TIA_MOBILE_FALTAS;
	}

	if ($_GET["debug"]) {
		p_r("TIA Mobile url: $URL\nParams:\n");
		p_r(userMobileLoginParams());
	}

	$curl = CurlHelper::sharedInstance();
	$curl->setMobileCurl($URL, userMobileLoginParams());

	return $curl->getJSONFromRequest();
}

function performTIALoginWithToken($token) {
	$curl = CurlHelper::sharedInstance();
	$curl->setTIACurl(URL_TIA_REQ, userLoginParamsWithToken($token));
	$curl->getHTMLFromRequest();

	if ($debug) {
		p_r(userLoginParamsWithToken($token));
	}
}

/**
 * Realiza o CURL nas seguintes etapas:
 * 		- Faz o login por POST
 * 		- Guarda o Cookie
 * 		- Realiza a requisisão com base no tipo
 */

function getTIAToken() {

	$curl = CurlHelper::sharedInstance();
	$curl->setTIACurl(URL_TIA_REFER);

	$tokenContent = $curl->getHTMLFromRequest();

	$doc = new DOMDocument();
	@$doc->loadHTML($tokenContent);

	$tokenValue;

	$inputs = $doc->getElementsByTagName("input");
	foreach($inputs as $node) {
		foreach($node->attributes as $attribute) {
		    if($attribute->nodeName == 'name' && $attribute->nodeValue == 'token') {
		        $tokenValue = $node->getAttribute('value');
		    }
		}
	}

	return $tokenValue;
}

/**
 * Monta a URL de método POST do TIA do Mackenzie.
 * @param  Tia do usuário
 * @param  Senha do usuário
 * @param  Unidade do usuário
 * @return URL montada
 */
function userLoginParamsWithToken($userToken) {

	return PARAM_TIA_TOKEN 
			. $userToken 
			. "&" 
			. PARAM_TIA_TIA 
			. USER_TIA 
			. "&" 
			. PARAM_TIA_PASS 
			. USER_PASS
			. "&" 
			. PARAM_TIA_UNI 
			. USER_UNIDADE;
}

/**
 * Monta a URL de método POST do TIA do Mackenzie Mobile.
 * @param  Tia do usuário
 * @param  Senha do usuário
 * @param  Unidade do usuário
 * @return URL montada
 */
function userMobileLoginParams() {

	$token_helper = TokenHelper::sharedInstance();

	return PARAM_TIA_MOBILE_TIA 
			. USER_TIA 
			. "&" 
			. PARAM_TIA_MOBILE_PASS 
			. USER_PASS 
			. "&" 
			. PARAM_TIA_MOBILE_UNI 
			. USER_UNIDADE
			. "&" 
			. PARAM_TIA_MOBILE_TOKEN 
			. $token_helper->get_current_token_if_exists();
}

/**
 * @return Devolve uma mensagem de erro em formato JSON e encerra a request.
 */
function missing_params_error() {

	show_error("Fail. Invalid parameters");
}

/**
 * Mostra um JSON Encode com a property 'errormsg' com a mensagem passada e encerra a request.
 * @param  [String] $error [Erro a ser mostrado ao usuario como um ALERT]
 */
function show_error($error) {

	echo json_encode(["errormsg" => $error]);
	exit;
}

function is_mobile_available() {

	$token_helper = TokenHelper::sharedInstance();

	if (!$token_helper->get_current_token_if_exists()) {
		return false;
	}

	$curl = CurlHelper::sharedInstance();
	$curl->setMobileCurl(URL_TIA_MOBILE_LOGIN, userMobileLoginParams());

	$JSON = $curl->getJSONFromRequest();

	return !($JSON["erro"] || $JSON["erro"] == "acesso negado") && 
			($JSON["resposta"][0]["sucesso"]);
}

function force_user_logout() {
	echo json_encode(['login' => false]);
	exit;
}

function force_nota_maintenance() {
	return json_encode([append_ad_notas("Aguarde o Mackenzie atualizar o TIA!")]);
}

function force_falta_maintenance() {
	return json_encode([['isInvalid' => true]]);
}

function should_logout_user_parse() {

	$parseCurl = curl_init(URL_PARSE_VERIFY_LOGOUT);
	curl_setopt($parseCurl, CURLOPT_URL, URL_PARSE_VERIFY_LOGOUT);
	curl_setopt($parseCurl, CURLOPT_HTTPHEADER, PARSE_HEADERS);
	curl_setopt($parseCurl, CURLOPT_POSTFIELDS, "{\"userTia\" : \"" . USER_TIA . "\"}");
	curl_setopt($parseCurl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($parseCurl, CURLOPT_AUTOREFERER, TRUE);
	curl_setopt($parseCurl, CURLOPT_FOLLOWLOCATION, FALSE);
	curl_setopt($parseCurl, CURLOPT_SSL_VERIFYPEER, TRUE);
	curl_setopt($parseCurl, CURLOPT_SSL_VERIFYHOST, 2);

	$JSONResponse = json_decode(curl_exec($parseCurl));

	return $JSONResponse->result;
}

?>