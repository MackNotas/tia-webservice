<?php

use Aws\DynamoDb\DynamoDbClient;

class DynamoDBHelper {

	# ------------------------------------
	# Public Vars
	# ------------------------------------
	public $client;

	# ------------------------------------
	# Const
	# ------------------------------------
	const TOKENS_TABLE_NAME = "macktia_tokens";

	# ------------------------------------
	# Initialize Methods
	# ------------------------------------

	public static function sharedInstance() {
        static $inst = null;
        if ($inst === null) {
            $inst = new DynamoDBHelper();
        }
        return $inst;
    }
	
	private function __construct() {
		$this->client = $this->initAwsClient();
	}

	# ------------------------------------
	# Private Methods
	# ------------------------------------

	private function initAwsClient() {
	return new DynamoDbClient([
								"version" => "latest",
								"region"  => "us-east-1"
							]);
	}

	# ------------------------------------
	# Public Methods
	# ------------------------------------

	public static function describe_token_table() {
		p_r($response = $this->client->describeTable(["TableName" => self::TOKENS_TABLE_NAME]));
	}

	public function get_hash_by_date($currentYMD) {
		
		$response;

		try {
		$response = $this->client->getItem([
											"TableName" => self::TOKENS_TABLE_NAME,
											"Key" => [
												"current_date" => ["N" => $currentYMD]
											]
										   ]);
		}
		catch (Exception $e) {
			return null;
		}
		finally {
			return $response["Item"]["hash"]["S"];
		}
	}

	public function scan_token_table() {
	
		$scan_table;

		try {
			$scan_table = $this->client->scan(["TableName" => self::TOKENS_TABLE_NAME]);
		} 
		catch (Exception $e) {
			return null;
		} 
		finally {
			return $scan_table;
		}
	}

	public function insert_new_key_value($key, $value) {

		try {
			$response = $this->client->putItem([
										"TableName" => self::TOKENS_TABLE_NAME,
										"Item" => [
													"current_date" => ["N" => $key],
													"hash" => ["S" => $value]
												  ]
									]);
		} 
		catch (Exception $e) {
			return false;
		}

		// p_r($response);exit;
		return true;
	}

	public function remove_key($key) {
		try {
			$this->client->deleteItem([
										"TableName" => self::TOKENS_TABLE_NAME,
										"Key" => ["current_date" => ["N" => $key]]
									  ]);
		} 
		catch (Exception $e) { 
			return false;
		}

		return true;
	}
}

?>