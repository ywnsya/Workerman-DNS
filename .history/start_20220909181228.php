<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
require_once __DIR__ . '/vendor/autoload.php';

$udp_worker = new Worker('udp://0.0.0.0:53');
$udp_worker->onMessage = function($connection, $data){
    echo(bin2hex($data));
    $connection->send('get');
};
Worker::runAll();