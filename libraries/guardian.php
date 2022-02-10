<?php

use Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Guardian {
    /**
     * This class guards the data of every user separately
     * Generates secret key for every user and uses it for his data security
     */
    private $user_id;
    private $secret;
    private $token;
    private $valid_token; // for validating sent token
    private $c_db;
    
    public function __construct($db_handle, $user_id, $token=null){
        /**
         * Guardian is used to encrypt/decrypt using JWT and creates secrets and tokens
         * @param CDB $db_handle: CDB instant for db communication
         * @param int $user_id: To retrieve the necessary information about this user
         * @param array|null $credentials: array of credentials if they are already fetched instead of consuming another query 
         */
        $this->c_db = $db_handle;
        $this->user_id = $user_id;
        $this->loadCredentials();
        if($token)
            $this->validate($token);
        $this->updateToken();
    }

    private function loadCredentials(){
        /**
         * Loads user's secret and current token
         * Generates secret if not found
         */
        $credentials = $this->c_db->getCredentials($this->user_id);
        $this->secret = $credentials['secret'];
        if(!$this->secret)
            $this->newSecret();
        $this->token = $credentials['token'];
    }

    private function validate($sent_token){
        $this->valid_token = $sent_token == $this->token;
    }

    private function updateToken(){
        if($this->token)
            $creation_time = $this->decode($this->token);
        else $creation_time = 0;
        if(time() - $creation_time > 86400)
            $this->token = $this->newToken();
    }

    private function newToken(){
        $new_token = JWT::encode(time(), $this->secret, 'HS256');
        $this->c_db->setToken($this->user_id, $new_token);
        return $new_token;
    }

    private function newSecret(){
        $this->secret = md5(time());
        $this->c_db->setSecret($this->user_id, $this->secret);
    }

    public function isAuthorized(){
        return $this->valid_token;
    }

    public function getToken(){
        return $this->token;
    }

    public function encode($data){
        return JWT::encode($data, $this->secret, 'HS256');
    }

    public function decode($cypher){
        return JWT::decode($cypher, new Key($this->secret, 'HS256'));
    }
}
?>