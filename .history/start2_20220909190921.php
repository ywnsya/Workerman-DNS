<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols;
require_once __DIR__ . '/vendor/autoload.php';
$worker = new Worker('udp://0.0.0.0:53');
$worker->protocol  = 'Dns';  
$worker->onMessage = function($connection, $data){
echo $data;
};
Worker::runAll();