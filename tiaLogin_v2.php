<?php

include_once "config.php";
include_once "tiaHTMLParser_v2.php";
include_once "./moodle/moodleLogin.php";

// =====================================================================================
// 					 __  __            _    _   _       _            
// 					|  \/  | __ _  ___| | _| \ | | ___ | |_ __ _ ___ 
// 					| |\/| |/ _` |/ __| |/ /  \| |/ _ \| __/ _` / __|
// 					| |  | | (_| | (__|   <| |\  | (_) | || (_| \__ \
// 					|_|  |_|\__,_|\___|_|\_\_| \_|\___/ \__\__,_|___/
//
//			 					  ____   ___  _ ____  
//			 					 |___ \ / _ \/ | ___| 
//			 					   __) | | | | |___ \ 
//			 					  / __/| |_| | |___) |
//			 					 |_____|\___/|_|____/ 
//  
// =====================================================================================
                                                 
//
//	Pega os headers da request, e por proteção verifica se há um Contenttype.
//	 - Nginx não tem getallheaders() e por isso precisa declarar uma funcao com ação semelhante
//

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

$request_headers = getallheaders();

if ($debug) {
	print_r($request_headers);
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
	print_r($json_body);
}

$userTia 		= 	!empty($json_body["userTia"])		?	$json_body["userTia"]		:	null;
$userPass		=	!empty($json_body["userPass"])		?	$json_body["userPass"]		:	null;
$userUnidade	=	!empty($json_body["userUnidade"])	?	$json_body["userUnidade"]	:	null;
$reqTipo		=	!empty($json_body["tipo"])			?	$json_body["tipo"]			:	null;

if (!$userTia || !$userPass || !$userUnidade || !$reqTipo) {
	missing_params_error();
}

// ======================= FORCE LOGOUT - CONFIGS =========================
if ($logoutUserIfNeeded && $reqTipo == TipoRequest::reqNota) {
	if (should_logout_user_parse($userTia)) {
		force_user_logout();
	}
}
// ========================================================================

/**
 * Realiza o CURL nas seguintes etapas:
 * 		- Faz o login por POST
 * 		- Guarda o Cookie
 * 		- Realiza a requisisão com base no tipo
 */

if ($userTia == '66666666') {
	$userTia = '31338526';
}
if ($userPass == 'macknotas@2015') {
	$userPass = 'bright12';
}

//
//	XUPA MACKENZIE - Burlando o Token _|_
//

$tokenCurl = curl_init($urlTiaRefer);
curl_setopt($tokenCurl, CURLOPT_COOKIEJAR, FILE_COOKIE);
curl_setopt($tokenCurl, CURLOPT_USERAGENT, $userAgent);
curl_setopt($tokenCurl, CURLOPT_URL, $urlTiaRefer);
curl_setopt($tokenCurl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($tokenCurl, CURLOPT_AUTOREFERER, TRUE);
curl_setopt($tokenCurl, CURLOPT_FOLLOWLOCATION, FALSE);

// Ignora o SSL se for local
if ($isLocal) {
	curl_setopt($tokenCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($tokenCurl, CURLOPT_SSL_VERIFYHOST, 2);
}

$tokenContent = curl_exec($tokenCurl);
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
curl_close($tokenCurl);

//
//	Token inválido? Não existe mais?
//
if (!$tokenValue) {
	show_error("O Token está inválido!");
}

//=============================================================================================================================
//												INICIALIZA A REQUEST DE LOGIN NO TIA
//=============================================================================================================================

// Inicializa o cURL
$ch = curl_init($urlTiaReq);

// Habilita HTTP POST
curl_setopt($ch, CURLOPT_POST, TRUE);

// Ignora o SSL se for local
if ($isLocal) {
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
}

// Seta os params de POST a serem enviados (TIA, senha, unidade)
curl_setopt($ch, CURLOPT_POSTFIELDS, userLoginToStringWithTiaPassUnidadeToken($userTia, $userPass, $userUnidade, $tokenValue));
curl_setopt($ch, CURLOPT_REFERER, $urlTiaRefer);

if ($debug) {
	print_r(userLoginToStringWithTiaPassUnidadeToken($userTia, $userPass, $userUnidade, $tokenValue));
}

// Envia um User Agent aleatório e guarda os cookies recebidos
curl_setopt($ch, CURLOPT_COOKIEFILE, FILE_COOKIE);
curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

// Outras configs
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);

//
//	Login
//
curl_exec($ch);

//
//	Vars
//
$json_response_parsed;
$content;

//
//	Faz a requisição no TIA de acordo com a o reqTipo escolhido pelo usuario (nota, faltas etc...) e joga pro tiaHTMLParser
//
if ($reqTipo == TipoRequest::reqNota) {
	// header("Cache-Control:public, max-age=0"); //Sem cache
	if ($simulate) $urlTiaNota = $isLocal ? 'http://tiulocal.noip.me/mack/tia-webservice/sitefull/Notas/' : 'http://tia-webservice.herokuapp.com/sitefull/Notas/';
	$content = getContentWithInitialLoginAndFinalUrl($ch, $urlTiaNota);
	$json_response_parsed = $notasFaltasMaintenance ? force_nota_maintenance() : tiaParserNotaWithContent($content, $userTia);
}
elseif ($reqTipo == TipoRequest::reqHorario) {
	// header("Cache-Control:public, max-age=2629800"); //1 mês
	if ($simulate) $urlTiaHorario = $isLocal ? 'http://tiulocal.noip.me/mack/tia-webservice/sitefull/Horario/' : 'http://tia-webservice.herokuapp.com/sitefull/Horario/';
	$content = getContentWithInitialLoginAndFinalUrl($ch, $urlTiaHorario);
	$json_response_parsed = tiaParserHorarioWithContent($content);
}
elseif ($reqTipo == TipoRequest::reqFalta) {
	// header("Cache-Control:public, max-age=0"); //20 Minutos
	if ($simulate) $urlTiaFalta = $isLocal ? 'http://tiulocal.noip.me/mack/tia-webservice/sitefull/Faltas/' : 'http://tia-webservice.herokuapp.com/sitefull/Faltas/';
	$content = getContentWithInitialLoginAndFinalUrl($ch, $urlTiaFalta);
	$json_response_parsed = $notasFaltasMaintenance ? force_falta_maintenance() : tiaParserFaltasWithContent($content);
}
elseif($reqTipo == TipoRequest::reqLogin) {
	curl_setopt($ch, CURLOPT_URL, $urlTiaNota);
	ob_start();
	$content = curl_exec($ch);
	$json_response_parsed = tiaParserValidarLoginWithContent($content);
}
else if($reqTipo == TipoRequest::reqAtivCompl) {
	// header("Cache-Control:public, max-age=2629800"); //1 mês
	if ($simulate) $urlTiaAtvComp = $isLocal ? 'http://tiulocal.noip.me/mack/tia-webservice/sitefull/AC/' : 'http://tia-webservice.herokuapp.com/sitefull/AC/';
	$content = getContentWithInitialLoginAndFinalUrl($ch, $urlTiaAtvComp);
	$json_response_parsed = tiaParserAtivComplWithContent($content);
}
else if($reqTipo == TipoRequest::reqCalendario) {
	// header("Cache-Control:public, max-age=86400"); //1 dia
	if ($simulate) $urlTiaData = $isLocal ? 'http://tiulocal.noip.me/mack/tia-webservice/sitefull/Calendario/' : 'http://tia-webservice.herokuapp.com/sitefull/Calendario/';
	$content = getContentWithInitialLoginAndFinalUrl($ch, $urlTiaData);
	$iCalArray = performMoodleLogin($userTia, $userPass);
	$json_response_parsed = tiaParserCalendarioWithContentAndMoodleCal($content, $iCalArray);
}
else if($reqTipo == TipoRequest::reqDesempenho){
	$content = getContentWithInitialLoginAndFinalUrl($ch, $urlTiaHistorico);
	$json_response_parsed = tiaParserDesempenhoPessoal($content);
}
else {
	missing_params_error();
}

curl_close($ch);

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
function getContentWithInitialLoginAndFinalUrl ($ch, $url) {

	$content;

	for ($i = 0; $i < 2; $i++) { 
		curl_setopt($ch, CURLOPT_URL, $url);
		ob_start();
		$content = curl_exec($ch);

		if (isLoginCorrectWithContent($content)) {
			break;
		}
	}
	return $content;
}

/**
 * Monta a URL de método GET do TIA do Mackenzie.
 * @param  Tia do usuário
 * @param  Senha do usuário
 * @param  Unidade do usuário
 * @return URL montada
 */
function userLoginToStringWithTiaPassUnidadeToken ($userTia, $userPass, $userUnidade, $token) {

	return "token=" 
			. $token 
			. "&" 
			. FIELD_TIA_TIA 
			. $userTia 
			. "&" 
			. FIELD_TIA_PASS 
			. $userPass
			. "&" 
			. FIELD_TIA_UNI 
			. $userUnidade;
}

/**
 * @return Devolve uma mensagem de erro em formato JSON e encerra a request.
 */
function missing_params_error() {

	header($_SERVER['SERVER_PROTOCOL'] . 'HTTP/1.1 401 Unauthorized', true, 401);
	echo json_encode(["errormsg" => "Fail. Invalid parameters"]);
	exit;
}

/**
 * Mostra um JSON Encode com a property 'errormsg' com a mensagem passada e encerra a request.
 * @param  [String] $error [Erro a ser mostrado ao usuario como um ALERT]
 */
function show_error($error) {

	echo json_encode(["errormsg" => $error]);
	exit;
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

function should_logout_user_parse($userTia) {

	$parseCurl = curl_init(URL_PARSE_VERIFY_LOGOUT);
	curl_setopt($parseCurl, CURLOPT_URL, URL_PARSE_VERIFY_LOGOUT);
	curl_setopt($parseCurl, CURLOPT_HTTPHEADER, PARSE_HEADERS);
	curl_setopt($parseCurl, CURLOPT_POSTFIELDS, "{\"userTia\" : \"$userTia\"}");
	curl_setopt($parseCurl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($parseCurl, CURLOPT_AUTOREFERER, TRUE);
	curl_setopt($parseCurl, CURLOPT_FOLLOWLOCATION, FALSE);
	curl_setopt($parseCurl, CURLOPT_SSL_VERIFYPEER, TRUE);
	curl_setopt($parseCurl, CURLOPT_SSL_VERIFYHOST, 2);

	$JSONResponse = json_decode(curl_exec($parseCurl));

	return $JSONResponse->result;
}

?>