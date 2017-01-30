<?php

/**
* 
*/
class CurlHelper  {

	public $ch;

	public static function sharedInstance() {
        static $inst = null;
        if ($inst === null) {
            $inst = new CurlHelper();
        }
        return $inst;
    }
	
	private function __construct() {
		$this->resetCurl();
	}

	private function resetCurl() {

		if ($this->ch) {
			curl_close($this->ch);
		}

		$this->ch = null;
		$this->ch = curl_init();

		if (IS_LOCAL) {
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		}

		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->ch, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, FALSE);
	}

	public function closeCurl() {
		$this->resetCurl();
	}

	public function setMobileCurl($post_url, $post_params) {
		
		curl_setopt($this->ch, CURLOPT_URL, $post_url);
		curl_setopt($this->ch, CURLOPT_POST, TRUE);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_params);
		curl_setopt($this->ch, CURLOPT_USERAGENT, USER_AGENT_MOBILE);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, FILE_COOKIE);
	}

	public function setTIACurl($post_url, $post_params=NULL) {

		if ($post_params) {
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_params);
			curl_setopt($this->ch, CURLOPT_POST, TRUE);
		}
		else {
			curl_setopt($this->ch, CURLOPT_POST, FALSE);
		}

		curl_setopt($this->ch, CURLOPT_USERAGENT, USER_AGENT);
		curl_setopt($this->ch, CURLOPT_URL, $post_url);
		curl_setopt($this->ch, CURLOPT_REFERER, URL_TIA_REFER);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, FILE_COOKIE);
	}

	public function setMoodleCurl($post_url, $post_params=NULL) {

		curl_setopt($this->ch, CURLOPT_POST, TRUE);
		curl_setopt($this->ch, CURLOPT_URL, $post_url);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_params);
		curl_setopt($this->ch, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, FILE_COOKIE_MOODLE);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, FILE_COOKIE_MOODLE);
		curl_setopt($this->ch, CURLOPT_USERAGENT, USER_AGENT);
	}

	public function getJSONFromRequest() {
		return json_decode(curl_exec($this->ch), true);
	}

	public function getHTMLFromRequest() {
		return curl_exec($this->ch);
	}
}

?>