<?php

/*
|--------------------------------------------------------------------------
| BOOTSTRAPPING
|--------------------------------------------------------------------------
*/

if (file_exists("local_bootstrap.php")) {
    include_once "local_bootstrap.php";
}

require __DIR__ . "/vendor/autoload.php";

include_once "helpers.php";
include_once "./resources/random-user-agent.php";

error_reporting(0);

/*
|--------------------------------------------------------------------------
| FORCE CONFIGS
|--------------------------------------------------------------------------
|
| Forçar todos os usuários a deslogarem, ao verificar as notas (iOS Somente)
|
*/

$logoutUserIfNeeded = false;

/*
|
| Enviar forçadamente aviso de notas em manutenção a todos os usuários.
|
*/

$notasFaltasMaintenance = false;

/*
|
| Habilita o uso da API do TIA Mobile
|
*/

define("MOBILE_ALLOWED", false);

/*
|--------------------------------------------------------------------------
| VARIAVEIS GLOBAIS
|--------------------------------------------------------------------------
*/

$urlsLocal = ["tiulocal.noip.me", "127.0.0.1", "localhost", "192.168.1.100"];
define("IS_LOCAL", array_key_exists("HTTP_HOST", $_SERVER) ? in_array($_SERVER["HTTP_HOST"], $urlsLocal) : false);
define("IS_FROM_IOS", (strpos($_SERVER["HTTP_USER_AGENT"], "iPhone") !== false));

/*
|--------------------------------------------------------------------------
| VARIAVEIS DA REQUEST
|--------------------------------------------------------------------------
*/

$debug      = $_GET["debug"];
$debug_logs = $_GET["debug_logs"];
$simulate   = $_GET["simulate"];

/*
|--------------------------------------------------------------------------
| CONFIGURAÇÕES DE REQUEST GLOBAL
|--------------------------------------------------------------------------
|
| Headers, Response, Encoding etc...
|
*/

header("Content-type: application/json;charset=utf-8");

/*
|--------------------------------------------------------------------------
| OUTRAS CONTANTES
|--------------------------------------------------------------------------
*/

$keysTable = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P",
              "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f",
              "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
              "w", "x", "y", "z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];

define("KEYS_TABLE", $keysTable);

/*
|--------------------------------------------------------------------------
| URLs DO TIA
|--------------------------------------------------------------------------
|
| URLs de acesso ao TIA
|
*/

define("URL_TIA_REQ",         "https://www3.mackenzie.com.br/tia/verifica.php");
define("URL_TIA_REFER",       "https://www3.mackenzie.com.br/tia/index.php");
define("URL_TIA_NOTA",        "https://www3.mackenzie.com.br/tia/notasChamada.php");
define("URL_TIA_HORARIO",     "https://www3.mackenzie.com.br/tia/horarChamada.php");
define("URL_TIA_FALTA",       "https://www3.mackenzie.com.br/tia/faltasChamada.php");
define("URL_TIA_ATV_COMP",    "https://www3.mackenzie.com.br/tia/acpaChamada.php");
define("URL_TIA_CALENDARIO",  "https://www3.mackenzie.com.br/tia/datasChamada.php"); 
define("URL_TIA_HISTORICO",   "https://www3.mackenzie.com.br/tia/historChamada.php");
  
/*
|--------------------------------------------------------------------------
| URLs DO TIA MOBILE
|--------------------------------------------------------------------------
|
| URLs de acesso ao TIA MOBILE
|
*/

define("URL_TIA_MOBILE_LOGIN",    "https://www3.mackenzie.com.br/tia/tia_mobile/ping.php");
define("URL_TIA_MOBILE_FALTAS",   "https://www3.mackenzie.com.br/tia/tia_mobile/faltas.php");
define("URL_TIA_MOBILE_NOTAS",    "https://www3.mackenzie.com.br/tia/tia_mobile/notas.php");
define("URL_TIA_MOBILE_HORARIO",  "https://www3.mackenzie.com.br/tia/tia_mobile/horarios.php");

/*
|--------------------------------------------------------------------------
| URLs DO MOODLE
|--------------------------------------------------------------------------
|
| URLs de acesso ao Moodle
|
*/

define("URL_MOODLE_EXPORT",   "https://moodle.mackenzie.br/moodle/calendar/export.php");
define("URL_MOODLE_LOGIN",    "https://moodle.mackenzie.br/moodle/login/index.php");
define("URL_MOODLE_HOME",     "https://moodle.mackenzie.br/moodle/index.php");
  

/*
|--------------------------------------------------------------------------
| URLs PARSE
|--------------------------------------------------------------------------
*/

define("URL_PARSE_VERIFY_LOGOUT", "https://api.parse.com/1/functions/logoutUserIfNeeded");

/*
|--------------------------------------------------------------------------
| VARIAVEIS DE PARAMETRO DE REQUEST - TIA
|--------------------------------------------------------------------------
|
| Variaveis que compõe os parametros da URL de acesso ao TIA
|
*/

define("PARAM_TIA_TIA", "alumat=");
define("PARAM_TIA_PASS", "pass=");
define("PARAM_TIA_UNI", "unidade=");
define("PARAM_TIA_TOKEN", "token=");

/*
|--------------------------------------------------------------------------
| VARIAVEIS DE PARAMETRO DE REQUEST - TIA MOBILE
|--------------------------------------------------------------------------
|
| Variaveis que compõe os parametros da URL de acesso ao TIA MOBILE
|
*/

define("PARAM_TIA_MOBILE_TIA", "mat=");
define("PARAM_TIA_MOBILE_PASS", "pass=");
define("PARAM_TIA_MOBILE_UNI", "unidade=");
define("PARAM_TIA_MOBILE_TOKEN", "token=");
// define("PARAM_TIA_MOBILE_TOKEN_VALUE", getCurrentToken());

/*
|--------------------------------------------------------------------------
| VARIAVEIS DE PARAMETRO DE REQUEST - PARSE
|--------------------------------------------------------------------------
*/

define("PARSE_HEADERS", ["X-Parse-Application-Id: key",
                        "X-Parse-REST-API-Key: key",
                        "Content-Type: application/json"]);

/*
|--------------------------------------------------------------------------
| COOKIE FILE
|--------------------------------------------------------------------------
*/

define("FILE_COOKIE", getRandomCookieFileName());
define("FILE_COOKIE_MOODLE", getRandomCookieFileName());

/*
|--------------------------------------------------------------------------
| USER AGENT
|--------------------------------------------------------------------------
*/

define("USER_AGENT", random_user_agent());
define("USER_AGENT_MOBILE", "MackTIA/13 CFNetwork/758.2.8 Darwin/15.0.0");

/*
|--------------------------------------------------------------------------
| ENUMS
|--------------------------------------------------------------------------
*/

abstract class TipoRequest {
    const reqNota		=	1;
    const reqHorario	=	2;
    const reqFalta		=	3;
    const reqLogin		=	4;
    const reqAtivCompl  =   5;
    const reqCalendario = 	6;
    const reqDesempenho = 	7;
}

abstract class DifferentCurso {
    const cursoEM       =   1;
    const cursoPOS      =   2;
    const cursoArq      =   3;
    const cursoDesign   =   4;
}

?>