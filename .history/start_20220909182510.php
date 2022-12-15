<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
require_once __DIR__ . '/vendor/autoload.php';

$udp_worker = new Worker('udp://0.0.0.0:53');
$udp_worker->onMessage = function($connection, $data){
    $data=bin2hex($data);
    echo($data);
    echo "\n";
    $type=substr($data,-8,4);
    switch($type){
        case '0001':
            $type='A';
            break;
        case '0002':
            $type='NS';
            break;
        case '000c':
            $type='PTR';
            break;
        case '001c':
            $type='AAAA';
            break;            
    }
    echo($type);
    echo "\n";

    echo "\n";

    $connection->send('get');
};
Worker::runAll();