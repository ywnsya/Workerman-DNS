<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
require_once __DIR__ . '/vendor/autoload.php';
$worker = new Worker('Dns://172.17.120.40:53');
// 注意直接udp协议是有效的，使用自定义协议无效
$worker->transport = 'udp';  
$worker->onMessage = function($connection, $data){
$data=json_decode($data);
    $type=$data->type;
$name=$data->name;
$rip=$connection->getRemoteIp();
echo "$type\n$name\n$rip\n";


$connection->send('000281800001000200000000047a6c6962026d6c0000010001c00c000100010000003c0004d466323ac00c000100010000001e0004d466323a');


};
Worker::runAll();