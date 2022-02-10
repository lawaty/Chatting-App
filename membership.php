<?php

require "libraries/api.php";
require "libraries/guardian.php";
require "libraries/db.php";

class Register extends Endpoint{
	private $c_db;
	public function __construct(){
		$this->c_db = new CDB();
		$required = array(
			"username"=>"/^[a-zA-Z0-9_\-.]{6,20}$/",
			"password"=>"/^[a-zA-Z0-9_\-.]{6,20}$/"
		);
		parent::__construct($required, $_POST);
	}

	public function run(){
		$id = $this->newUser();
		if($id){
			new Guardian($this->c_db, $id);
			$this->result['status'] = "success";
		}
		else $this->result['status'] = "Register Error: Username Already Exists";	return $this->result;
	}

	private function newUser(){
		return $this->c_db->insert('users', array_keys($this->required), array_values($this->request));
	}
}

class Login extends Endpoint {
	private $c_db;
	public function __construct(){
		$this->c_db = new CDB();
		$required = array(
			"username"=>"/^[a-zA-Z0-9_\-.]{6,20}$/",
			"password"=>"/^[a-zA-Z0-9_\-.]{6,20}$/"
		);
		parent::__construct($required, $_POST);
	}

	public function run(){
		if(($id = $this->c_db->login($this->request['username'], $this->request['password']))){
			$guard = new Guardian($this->c_db, $id);

			$this->result['status'] = 'success';
			$this->result['token'] = $guard->getToken();
			$this->result['data'] = array_merge(
				array('_id'=>$id, 'username'=>$this->request['username']),
				array('rooms'=>$this->c_db->getRooms($id))
			);
		}
		else $this->result['status'] = 'Wrong Username or Password';
		return $this->result;
	}
}

class Logout extends Endpoint {
	private $c_db;
	public function __construct(){
		$this->c_db = new CDB();
		$required = array(
			"_id"=>"/^[0-9]{1,10}$/",
			"token"=>"/^[^\n]+$/" // (No \n) matches anything in one line
		);
		parent::__construct($required, $_POST);
	}
	public function run(){
		$guard = new Guardian($this->c_db, $this->request['_id'], $this->request['token']);
		if($guard->isAuthorized()){
			$this->c_db->logout($this->request['_id']);
			$this->result['status'] = 'success';
		}
		else $this->result['status'] = 'Wrong Token';
		
		if(($id = $this->c_db->logout($this->request['username'], $this->request['password']))){
			$guard = new Guardian($this->c_db, $id);

			$this->result['status'] = 'success';
			$this->result['token'] = $guard->getToken();
			$this->result['data'] = array_merge(
				array('_id'=>$id, 'username'=>$this->request['username']),
				array('rooms'=>$this->c_db->getRooms($id))
			);
		}
		else $this->result['status'] = 'Wrong Username or Password';
		return $this->result;
	}
}


// Main Section
$result = array();
if(isset($_REQUEST['action']) && class_exists($_REQUEST['action']) && is_subclass_of($_REQUEST['action'], 'Endpoint')){
	$endpoint = new $_REQUEST['action'];

	if($endpoint->validateParams())
		$result = $endpoint->run();

	else 
		$result['status'] = $_REQUEST['action'].' Error: Invalid Parameters';
}

echo json_encode($result);

?>