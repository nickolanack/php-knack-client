<?php

namespace knack;

class Client {

	private $credentials = null;
	private $tableNames=array();
	private $tableDefinitions=array();
	private $cacheDir=__DIR__;
	private $maxResults=1000;
	private $resultFormat='raw';

	public function __construct($credentials) {
		if(is_object($credentials)){
			$credentials=get_object_vars($credentials);
		}
		$this->credentials=$credentials;
	}

	public function useNamedTableDefinitionForObject($name, $id){
		
		return $this
			->defineTableObjectName($name, $id)
			->createCachedTableDefinitionIfNotExists($name)
			->useCachedTableDefinition($name);


	}


	protected function defineTableObjectName($name, $id){
		$this->tableNames[$name]=$id;
		return $this;
	}

	protected function objectIdFromName($name){



		if(key_exists($name, $this->tableNames)){
			return $this->tableNames[$name];
		}
		if(is_numeric($name)){
			return intval($name);
		}

		if (is_string($name)) {
			$parts = explode("_", $name);
			$num=array_pop($parts);
			if(is_numeric($num)){
				return intval($num);
			}

	
			throw new \Exception('Expected int `object_{id}`: ' . $name);
		
		}

		throw new \Exception('Undefined table name: '.$name);
	}

	protected function formatResultFields($records, $name){

		$id=$this->objectIdFromName($name);
		$formattedResults= array_map(function($record)use($id){

			$result=array('knackid'=>$record->id);
			foreach($this->tableDefinitions['object_'.$id]->fields as $fieldDefinition){
				$result[$fieldDefinition->label]=$record->{$fieldDefinition->key};
			}

			return (object) $result;

		}, $records);

		//print_r($formattedResults);
		return $formattedResults;

	}

	protected function createCachedTableDefinitionIfNotExists($name, $file=null){
		if(is_null($file)){
			 $file=$this->getCacheDir().'/cache-'.$name.'-table.json';
		}
		$id=$this->objectIdFromName($name);
		if(!file_exists($file)){
			file_put_contents($file, json_encode($this->getFields($id), JSON_PRETTY_PRINT));
		}

		return $this;

		
	}

	protected function getCacheDir(){
		return $this->cacheDir;
	}
	public function setCacheDir($dir){
		$this->cacheDir=$dir;
		return $this;
	}

	protected function useCachedTableDefinition($name, $file=null){
		if(is_null($file)){
			 $file=$this->getCacheDir().'/cache-'.$name.'-table.json';
		}
		$id=$this->objectIdFromName($name);
		if(file_exists($file)){

			$this->tableDefinitions['object_'.$id]=json_decode(file_get_contents($file));
			return $this;
		}

		throw new \Exception('Did not find cache file for table: '.$name.' at: '.$file);

	}


	public function getObjects() {


		return $this->get("https://api.knackhq.com/v1/objects");

	}

	public function getPages() {


		return $this->get("https://api.knackhq.com/v1/pages/scene_1/views/view_1/records");

	}
	public function getRecords($objectNum) {


		$list=array();
		$this->iterateRecords($objectNum, function($record)use(&$list){
			$list[]=$record;
		});

		return $list;

	}

	public function iterateRecords($objectNum, $callback) {

		$id=$this->objectIdFromName($objectNum);
		$index=0;

		

		$results=(object) array(
			'total_pages' => 1,
    		'current_page' => 1,
    		'total_records' => 0
			//'records'=>array()
		);

		$maxResults=$this->maxResults; 
		$resultFormat=$this->resultFormat; 

		while($results->current_page<=$results->total_pages){


		
			$urlArgs=array(
				'page'=>$results->current_page,
				'format'=>$resultFormat,
				'rows_per_page'=>$maxResults
			);
			$urlArgsString='?'.implode('&', array_map(function($v, $k){
				return $k.'='.$v;
			}, $urlArgs, array_keys($urlArgs)));
			
			$resultObject= $this->get("https://api.knack.com/v1/objects/object_" . $id . "/records".$urlArgsString);

			//$results->records=array_merge($results->records, $resultObject->records);
			

			$results->total_pages=$resultObject->total_pages;
			$results->total_records=$resultObject->total_records;
			$results->current_page++;


			$resultRecords=$resultObject->records;
			if(key_exists('object_'.$id, $this->tableDefinitions)){
				$resultRecords= $this->formatResultFields($resultRecords, $objectNum);
			}

			foreach($resultRecords as $record){
				$callback($record, $index++);
			}

		}




		return $this;

	}

	public function getFields($objectNum) {

		$id=$this->objectIdFromName($objectNum);
		return $this->get("https://api.knackhq.com/v1/objects/object_" . $id."/fields");

	}

	protected function get($url) {

		echo 'GET: '.$url."\n";
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