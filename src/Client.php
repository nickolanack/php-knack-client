<?php

namespace knack;

class Client {

	private $credentials = null;

	public function __construct($credentials) {
		if(is_object($credentials)){
			$credentials=get_object_vars($credentials);
		}
		$this->credentials=$credentials;
	}

	public function getObjects() {


		return $this->get("https://api.knackhq.com/v1/objects");

	}

	public function getPages() {


		return $this->get("https://api.knackhq.com/v1/pages/scene_1/views/view_1/records");

	}

	public function getRecords($objectNum) {

		if (is_string($objectNum)) {
			$parts = explode("_", $objectNum);
			$objectNum = (int) array_pop($parts);
		}

		if (!is_int($objectNum)) {
			throw new \Exception('Expected int `object_{id}`: ' . $objectNum);
		}


		
		return $this->get("https://api.knack.com/v1/objects/object_" . $objectNum . "/records");

	}

	public function getFields($objectNum) {

		if (is_string($objectNum)) {
			$parts = explode("_", $objectNum);
			$objectNum = (int) array_pop($parts);
		}

		if (!is_int($objectNum)) {
			throw new \Exception('Expected int `object_{id}`: ' . $objectNum);
		}


		return $this->get("https://api.knackhq.com/v1/objects/object_" . $objectNum."/fields");

	}

	protected function get($url) {
		$client = new \GuzzleHttp\Client();

		$args = array(
			'timeout' => 15,
			'headers' => array(
				"X-Knack-Application-Id" => $this->credentials['id'],
				"X-Knack-REST-API-KEY" => $this->credentials['key'],
				"content-type" =>'application/json'
			)
		);

		$httpcode = 0;
		try {
			$response = $client->request('GET', $url, $args);
			$httpcode = $response->getStatusCode();

		} catch (\RequestException $e) {
			//echo $e->getRequest();
			if ($e->hasResponse()) {
				$httpcode = $e->getResponse()->getStatusCode();
				// echo $e->getResponse()->getBody();
			}
		}

		if ($httpcode !== 200) {
			throw new \Exception('Request Error: ' . $httpcode);
		}

		return json_decode($response->getBody());
	}

	protected function post($url, $fields = array()) {

		$client = new \GuzzleHttp\Client();

		$args = array(
			'timeout' => 15,
		);

		//print_r($fields);

		if (count($fields)) {
			$args['form_params'] = $fields;
		}
		$httpcode = 0;
		try {
			$response = $client->request('POST', $url, $args);
			$httpcode = $response->getStatusCode();

		} catch (\RequestException $e) {
			//echo $e->getRequest();
			if ($e->hasResponse()) {
				$httpcode = $e->getResponse()->getStatusCode();
				// echo $e->getResponse()->getBody();
			}
		}

		if ($httpcode !== 200) {
			throw new \Exception('Request Error: ' . $httpcode);
		}

		return json_decode($response->getBody());
	}

}