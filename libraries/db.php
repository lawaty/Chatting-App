<?php

require "libraries/sql.php";

class CDB extends DB {
  public function __construct(){
    parent::__construct('chatting.sql');    
  }

  public function login($username, $password){
    /**
     * Function loads account basic data
     * @param string $username: login username
     * @param string $password: login password
     * @return array $data | Boolean false : User Basic data | 
     */
    $res = $this->select(array('_id'), 'users', array('username'=>$username, 'password'=>$password));
    if(count($res))
      return $res[0]['_id'];
    
    else return false;
  }

  public function getRooms($id){
    return $this->joinAndSelect(array('rooms._id', 'rooms.name', 'rooms.admin'), array('users._id'=>'users_rooms.user', 'rooms._id'=>'users_rooms.room'), array('users._id'=>$id));
  }

  public function retrieveRecord($table, $id){
    return $this->select(array('*'), $table, array('_id'=>$id));
  }

  public function getCredentials($id){
    return $this->select(array('secret', 'token'), 'users', array('_id'=>$id))[0];
  }

  public function logout($id){
    $this->update('users', array('token'=>'null'), array('_id'=>$id));
  }

  public function setSecret($id, $secret){
    $this->update('users', array('secret'=>$secret), array('_id'=>$id));
  }

  public function setToken($id, $token){
    $this->update('users', array('token'=>$token), array('_id'=>$id));
  }
}
 

?>