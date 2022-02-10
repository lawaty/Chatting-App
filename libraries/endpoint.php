<?php

abstract class Endpoint{
	/**
		* Generic signature for every endpoint
	*/
	protected $required;
	protected $optional;
	protected $request;
	protected $result;

	private function neglectExcessParams($request){
		$result = array();
		foreach($this->required as $key => $value){
			$result[$key] = $request[$key];
		}
		return $result;
	}

	public function __construct($required, $request, $optional=null){
		/**
			* Initializing endpoint
			* @param required: parameters required for this endpoint to operate
			* @param request: The request body sent from the client
			* @param optional: optional parameters for this endpoint to operate
			* @example required or optional array form:-
			* array(
			* "field1" => "regex pattern for validation",
			* "field2" => "regex pattern for validation"
			* )
		*/
		$this->required = $required;
		$this->request = $request ? $this->neglectExcessParams($request) : array();
		$this->optional = $optional;
		$this->result = array();
	}

	public function validateParams(){
		/**
			* Validate the request using the endpoint params criteria
			* @return : boolean validation result
		*/
		$new_request = array();
		foreach($this->required as $param => $pattern){
			if( !(isset($this->request[$param]) && preg_match($pattern, $this->request[$param])) )
				return false;
			
			$new_request[$param] = $this->request[$param];
		}
		$this->request = $new_request;
		return true;
	}

	abstract public function run();
}

?>