<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
require_once __DIR__ . '/vendor/autoload.php';
$worker = new Worker('Dns://0.0.0.0:53');
// 注意直接udp协议是有效的，使用自定义协议无效
$worker->transport = 'udp';  
$worker->onMessage = function($connection, $data){
$data=explode('|||', $data);
$type=$data[0];
$name=$data[1];
$rip=$connection->getRemoteIp();
#echo "$type\n$name\n$rip\n";

$connection->send('0000000381800001000000000000047a6c69001062026d6c00001c0001');


};
Worker::runAll();