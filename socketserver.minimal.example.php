<?php

set_time_limit(0);

spl_autoload_register(function ($class_name) {
    require_once dirname(__FILE__).DIRECTORY_SEPARATOR.str_replace("\\", "/", $class_name) . '.php';
});

use SocketServer\SocketServer;

$server = new SocketServer([
    'port' => 8080
]);

$server->afterServerError(function($server, $code, $message) {
     echo "SERVER ERROR [$code]: $message\n";
});

$server->afterClientError(function($server, $clientUid, $code, $mesage) {
    echo "({$clientUid}) [$code]: $message\n";
});

$server->afterShutdown(function($server) {
    echo "SERVER SHUTDOWN\n";
});

$server->clientConnected(function ($server, $clientUid, $data) {
    echo "({$clientUid}) CLIENT CONNECTED: $data\n";    
    return true; //accept client
});

$server->clientDisconnected(function($server, $clientUid, $reason) {
    echo "({$clientUid}) CLIENT DISCONNECTED: {$reason}\n";   
});

//build message which server use to check if client is live
$server->buildPing(function($server, $clientUid) {     
     
});

// listen to all request from clients (request is raw as client it send)
$server->addListener(function($server, $clientUid, $request) {       
    echo "({$clientUid}) MESSAGE FROM CLIENT (LEN:".strlen($request)."): ".$request."\n";    
});

$server->listen();
