<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
require_once __DIR__ . '/vendor/autoload.php';
$worker = new Worker('Dns://172.17.120.40:53');
$worker->transport = 'udp';  
$worker->onMessage = function($connection, $data){
$data=json_decode($data);
    $type=$data->type;
$name=$data->name;
$rip=$connection->getRemoteIp();

#输出信息
#echo "Type: $type \n Domain: $name\n Client IP: $rip \n";

/** A类记录返回 
$send['type']='A';
$send['detail']='119.29.29.29';
$send['ttl']=30;
**/


$send['type']='NS';
$send['detail']='coco.bunny.net';
$send['ttl']=30;



#id和query一般情况下直接返回输出即可
$send['id']=$data->id;
$send['query']=$data->query;



$send=json_encode($send);
$connection->send($send);


};
Worker::runAll();