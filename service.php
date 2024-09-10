<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require('Auth.php');
require("json-rpc.php");

// Main JSON-RPC service
class Service {
  public function _echo($str) {
    return $str;
  }
}

handle_json_rpc(new Auth(new Service()));
