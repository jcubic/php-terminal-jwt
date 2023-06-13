<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

require_once('./vendor/autoload.php');

require("json-rpc.php");

function logger($message, $logFile = "error.log") {
   $message .= PHP_EOL;
   return file_put_contents($logFile, $message, FILE_APPEND);
}

$env = parse_ini_file('.env');

class Auth {
    private $service;
    private $date;
    private $payload_base;
    
    public function __construct($service) {
        global $env;
        $this->service = $service;
        $this->date = new DateTimeImmutable();
        $this->payload_base = [
            'iat' => $this->date->getTimestamp(),
            'iss' => $env['domain'], 
            'nbf' => $this->date->getTimestamp()
        ];
    }
    
    private function expiration($shift) {
        return $this->date->modify($shift)->getTimestamp();
    }
    
    private function refresh_token($user) {
        global $env;
        $refresh_token = JWT::encode(
            array_merge([
                'exp' => $this->expiration('+24 hours'),
                'username' => $user
            ], $this->payload_base),
            $env['refresh_token_secret'],
            'HS512'
        );
        setcookie($env['refresh_cookie'], $refresh_token, time()+(24 * 60 * 60 * 1000), "/", $env['domain'], 0, 1);
    }
    
    private function generate_access_token($user) {
        global $env;
        $access_token = JWT::encode(
            array_merge([
              'exp'  => $this->expiration('+1 minutes'),
              'username' => $user
            ], $this->payload_base),
            $env['access_token_secret'],
            'HS512'
        );
        
        return $access_token;
    }
    
    public function refresh() {
        global $env;
        if (!isset($_COOKIE[$env['refresh_cookie']])) {
            throw new Exception("Refresh token not found!");
        }
        $jwt = $_COOKIE[$env['refresh_cookie']];
        try {
            $refresh_token = JWT::decode($jwt, new Key($env['refresh_token_secret'], 'HS512'));
        } catch(ExpiredException $e) {
            throw new Exception("Refresh token expired");
        }
        if (!$this->test_token($refresh_token)) {
            throw new Exception("Invalid refresh token!");
        }
        $user = $refresh_token->username;
        $new_access_token = $this->generate_access_token($user);
        $this->refresh_token($user);
        return $new_access_token;
    }
    
    public function login($user, $password) {
        global $env;
        if ($user != $env['user'] || $password != $env['password']) {
            return null;
        }
        $access_token = $this->generate_access_token($user);

        $this->refresh_token($user);
        
        return $access_token;
    }
    
    private function test_token($token) {
        global $env;
        if (!isset($token->username)) {
            return false;
        }
        if ($token->iss != $env['domain']) {
            return false;
        }
        return $token->nbf < $this->date->getTimestamp();
    }
    
    private function valid_token($jwt) {
        global $env;
        try {
            $token = JWT::decode($jwt, new Key($env['access_token_secret'], 'HS512'));
            return $this->test_token($token);
        } catch(ExpiredException $e) {
            throw new Exception("Access token expired");
        }
    }
    
    public function __call($method, $params) {
        $jwt = array_shift($params);
        if (!$this->valid_token($jwt)) {
            throw new Exception("Invalid token");
        }
        
        $class = get_class($this->service);
        $methods = get_class_methods($class);

        if (!in_array($method, $methods)) {
            throw new Exception("Invalid method $method");
        }
        
        return call_user_func_array(array($this->service, $method), $params);
    }
}

class Service {
  public function _echo($str) {
    return $str;
  }
}


handle_json_rpc(new Auth(new Service()));