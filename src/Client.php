<?php

namespace knack;

class Client {

	private $credentials = null;
	private $tableNames=array();
	private $tableDefinitions=array();
	private $cacheDir=__DIR__;
	private $maxResults=1000;
	private $resultFormat='raw';
	private $limit=-1;
	private $cacheRequestData=false;

	private $shouldShuffle=false;

	public function __construct($credentials) {
		if(is_object($credentials)){
			$credentials=get_object_vars($credentials);
		}
		$this->credentials=$credentials;
	}

	public function limitApiCalls($num){
		$this->limit=$num;

		return $this;
	}

	public function hasReachedLimit(){

		$path=$this->cacheDir.'/knack-api-counter-'.date('Y-m-d').'.json';
		$data=(object)array('count'=>0);
		if(file_exists($path)){
			$data=json_decode(file_get_contents($path));
		}

		if($this->limit>0){
			if($data->count>=$this->limit){
				return true;
			}
		}

		return false;


	}

	protected  function  incrementApiCounter(){

		$path=$this->cacheDir.'/knack-api-counter-'.date('Y-m-d').'.json';
		$data=(object)array('count'=>0);
		if(file_exists($path)){
			$data=json_decode(file_get_contents($path));
		}

		if($this->limit>0){
			if($data->count>=$this->limit){
				throw new \Exception('Reached Api request limit: '.$data->count);
			}
		}

		$data->count++;
		file_put_contents($path, json_encode($data));



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

			if(!key_exists('object_'.$id, $this->tableDefinitions)){
				return $record;
			}

			$result=array('knackid'=>$record->id);
			foreach($this->tableDefinitions['object_'.$id]->fields as $fieldDefinition){
				$result[$fieldDefinition->label]=$record->{$fieldDefinition->key};
			}

			return (object) $result;

		}, $records);

		//print_r($formattedResults);
		return $formattedResults;

	}

	protected function formatValues($values, $name){
		$id=$this->objectIdFromName($name);
		

		if(!key_exists('object_'.$id, $this->tableDefinitions)){
			return $values;
		}

		$formattedValues=array();
		foreach($this->tableDefinitions['object_'.$id]->fields as $fieldDefinition){

			if(key_exists($fieldDefinition->label, $values)){
				$formattedValues[$fieldDefinition->key]= $values[$fieldDefinition->label];
			}

			if(key_exists($fieldDefinition->key, $values)){
				$formattedValues[$fieldDefinition->key]= $values[$fieldDefinition->key];
			}
		}


		//print_r($formattedResults);
		return $formattedValues;

	}

	protected function fieldMapFilters($filter, $name){

		if(key_exists('field', $filter)){
			$filter=array($filter);
		}

		$id=$this->objectIdFromName($name);
		$formattedFilter = array_map(function($filterItem)use($id){



			if(!key_exists('object_'.$id, $this->tableDefinitions)){
				if($filterItem['field']=='knackid'){
					$filterItem['field']='id';
				}
				return $filterItem;
			}

			foreach($this->tableDefinitions['object_'.$id]->fields as $fieldDefinition){

				if($filterItem['field']===$fieldDefinition->label){
					$filterItem['field']=$fieldDefinition->key;
				}
				if($filterItem['field']=='knackid'){
					$filterItem['field']='id';
				}
			}


			return $filterItem;
		}, $filter);

		return $formattedFilter;
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

	public function cacheRequests(){

		$this->cacheRequestData=true;

		return $this;
	}

	protected function cacheRequest($url, $data){

		if(!$this->cacheRequestData){
			return;
		}

		$name=preg_replace("/[^a-zA-Z0-9 _-]/", "", $url);
		file_put_contents($this->getCacheDir().'/'.$name.'.json', json_encode($data, JSON_PRETTY_PRINT));

	}

	protected function getCachedRequest($url){


		if(!$this->cacheRequestData){
			return false;
		}


		$name=preg_replace("/[^a-zA-Z0-9 _-]/", "", $url);
		$file=$this->getCacheDir().'/'.$name.'.json';
		if(file_exists($file)){
			return json_decode(file_get_contents($file));
		}

		return false;
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
		return $this->iterateRecordsWithFilter($objectNum, null, $callback);
	}


	public function shuffleResults(){
		$this->shouldShuffle=true;
		return $this;
	}

	public function iterateRecordsWithFilter($objectNum, $filter, $callback){


		if($this->shouldShuffle){
			$this->shouldShuffle=false;
			$list=array();
			$this->iterateRecordsWithFilter($objectNum, $filter, function($result, $i)use(&$list){

				$list[]=array($result, $i);

			});

			shuffle($list);
			foreach($list as $item){
				$callback($item[0], $item[1]);
			}

			return $this;

		}

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

			if(is_array($filter)&&(!empty($filter))){


				$urlArgs['filters']=urlencode(json_encode($this->fieldMapFilters($filter, $objectNum)));

			}

			$urlArgsString='?'.implode('&', array_map(function($v, $k){
				return $k.'='.$v;
			}, $urlArgs, array_keys($urlArgs)));
			
			$resultObject= $this->get("https://api.knack.com/v1/objects/object_" . $id . "/records".$urlArgsString);

			//$results->records=array_merge($results->records, $resultObject->records);
			

			$results->total_pages=$resultObject->total_pages;
			$results->total_records=$resultObject->total_records;
			$results->current_page++;


			$resultRecords= $this->formatResultFields($resultObject->records, $objectNum);
			

			foreach($resultRecords as $record){
				$callback($record, $index++);
			}

		}




		return $this;

	}

	public function setRecordValues($objectNum, $id, $values, $callback=null){

		$objectId=$this->objectIdFromName($objectNum);

		$urlArgsString='';


		$resultObject= $this->put("https://api.knack.com/v1/objects/object_" . $objectId . "/records/".$id, $this->formatValues($values, $objectNum));
		
		if($callback){
			$callback($resultObject);
		}
		//print_r($resultObject);
		return $this;

	}

	public function getFields($objectNum) {

		$id=$this->objectIdFromName($objectNum);
		return $this->get("https://api.knackhq.com/v1/objects/object_" . $id."/fields");

	}

	protected function get($url) {

		if($cached=$this->getCachedRequest($url)){
			return $cached;
		}
		
		$this->incrementApiCounter();		

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

		} catch (\GuzzleHttp\Exception\RequestException $e) {
			//echo $e->getRequest();
			if ($e->hasResponse()) {
				$httpcode = $e->getResponse()->getStatusCode();
				// echo $e->getResponse()->getBody();
			}
		}

		if ($httpcode !== 200) {
			throw new \Exception('Request Error: ' . $httpcode);
		}

		$result = json_decode($response->getBody());

		$this->cacheRequest($url, $result);

		return $result;
	}

	protected function put($url, $fields = array()) {


		$this->incrementApiCounter();	

		$client = new \GuzzleHttp\Client();

		$args = array(
			'timeout' => 15,
			'headers' => array(
				"X-Knack-Application-Id" => $this->credentials['id'],
				"X-Knack-REST-API-KEY" => $this->credentials['key'],
				"content-type" =>'application/json'
			)
		);

		print_r($fields);

		if (count($fields)) {
			$args['json'] = $fields;
		}
		$httpcode = 0;
		try {
			$response = $client->request('PUT', $url, $args);
			$httpcode = $response->getStatusCode();

		} catch (\GuzzleHttp\Exception\RequestException $e) {
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