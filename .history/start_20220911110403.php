<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
require_once __DIR__ . '/vendor/autoload.php';

$udp_worker = new Worker('udp://172.17.120.40:53');
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
    $namede=str_split($name,2);
    $realname='';
    foreach($namede as $cha){
        $chat=hex2bin($cha);
        if(!ctype_alnum($chat)){
            $chat='.';
        }
        $realname=$realname.$chat;
    }
    $realname=substr($realname,1,-1);

    echo "$realname";

    echo "\n";

    $connection->send('get');
};
Worker::runAll();