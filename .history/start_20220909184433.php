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
        case '0005':
            $type='CNAME';
            break;
        case '0010':
            $type='TEXT';
            break;                             
    }
    echo($type);
    echo "\n";
    $name=$type=substr($data,24,-8);
    #$namede=str_split($name,2);
    
    echo hex2bin('01');
    if(!hex2bin('01')){
        echo "none";
    }

    echo "\n";

    $connection->send('get');
};
Worker::runAll();