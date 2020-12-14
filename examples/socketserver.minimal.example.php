<?php

set_time_limit(0);

require "../src/SocketServer.php";

use pekand\SocketServer\SocketServer;

echo "Server\n";

$server = new SocketServer([
    'ip' => "127.0.0.1",
    'port' => 8080
]);

$server->afterServerError(function($server, $code, $message) {
    echo "SERVER ERROR [$code]: $message\n";
});

$server->afterClientError(function($server, $clientUid, $code, $message) {
    echo "({$clientUid}) [$code]: $message\n";
});

$server->afterShutdown(function($server) {
    echo "SERVER SHUTDOWN\n";
});
 
$server->clientConnected(function ($server, $clientUid, $data) {
    echo "({$clientUid}) CLIENT CONNECTED: $data\n"; 
    $server->sendData($clientUid, 'accept');   
    return true; //accept client
});

$server->clientDisconnected(function($server, $clientUid, $reason) {
    echo "({$clientUid}) CLIENT DISCONNECTED: {$reason}\n";   
});

//build message which server use to check if client is live
$server->buildPing(function($server, $clientUid) {     
    $server->sendData($clientUid, 'ping');
});

// listen to all request from clients (request is raw as client it send)
$server->addListener(function($server, $clientUid, $request) {       
    echo "({$clientUid}) MESSAGE FROM CLIENT (LEN:".strlen($request)."): ".$request."\n";  

    if($request == 'first_message') {
        $server->sendData($clientUid, 'first_response');
    }  

    if($request == 'second_message') {
        $server->sendData($clientUid, 'close');
    }

    if($request == 'closed') {
        $server->closeClient($clientUid);
    }
});

$server->listen();
