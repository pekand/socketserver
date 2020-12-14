<?php

set_time_limit(0);

require "../src/SocketClient.php";
require "../src/SocketPool.php";

use pekand\SocketServer\SocketClient;
use pekand\SocketServer\SocketPool;

echo "Client\n";

$client = new SocketClient([
    'ip' => "127.0.0.1",
    'port' => 8080
]);

$client->afterClientError(function($client, $errorcode, $errormsg) {
    echo "SERVER Eroor [$errorcode]: $errormsg\n";
});

$client->afterReceiveHeader(function($client, $headerFromServer) {
    echo "Server send header: $headerFromServer\n";
    return true; // accept server header
});

$client->afterClientConnected(function($client) {
    echo "Client connected to server\n";
    $client->sendData("first_message");
    return true;
});

$client->addListener(function($client, $data) {
    echo "SERVER Request: $data\n";

    if($data=='ping') {
        echo "CLIENT Response: pong\n";
        $client->sendData("pong");
    }

    if($data=='first_response'){
        echo "CLIENT Response: second_message\n";
        $client->sendData("second_message");
    }

    if($data=='close') {
        echo "CLIENT Response: client closed\n";
        $client->sendData("closed");
        $client->close();
    }
});

// Listen for one client
//$client->listen();

// create pool for listening multiple clients and execution delayed and repeating actions
$pool = new SocketPool();

$pool->addAction(['delay'=>2000000], function() { // one time action after 2 seconds
    echo "Action1\n";
});

$pool->addAction(['repeat'=>3000000], function() { // delay action 3 secends and repeat action every 3 seconds
    echo "Action2\n";
});

$pool->addAction(['delay'=>4000000, 'repeat'=>5000000], function() { // delay action 4 seconds and repeat every 5 seconds
    echo "Action3\n";
});

// Listen for multiple clients
$pool->listen([
    $client
]);
