<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
require_once __DIR__ . '/vendor/autoload.php';
$worker = new Worker('Dns://172.26.141.97:53');
$worker->transport = 'udp';  
$worker->onMessage = function($connection, $data){
$data=json_decode($data);
$type=$data->type; #查询类型
$name=$data->name; #查询内容(一般是域名，PTR时为倒序IP)
$rip=$connection->getRemoteIp(); #客户端IP

#输出信息
echo "\n Type:$type \n Domain: $name\n Client IP: $rip \n";


if($type=='A'){
    $send['type']='A';
    $send['detail'][1]='119.29.29.29';
    $send['detail'][2]='8.8.8.8';
    $send['ttl']=30;
};


if($type=='PTR'){
    /**
     * 请注意：Nslookup和一部分dns程序会在任何请求发出前先对DNS服务器的IP发送PTR请求
     * 如果不设置PTR请求，收到PTR请求后会报错
     * 此外，请注意PTR请求所请求的域名(其实是IP)格式为倒序IP.in.addr.arpa
     * 例如，PTR： 192.168.0.1 则 $name 收到的是： 1.0.168.192.in.addr.arpa
     */
    $send['type']='PTR';
    $send['detail']='dns.laysense.com';
    $send['ttl']=30;
};

if($type=='NS'){
    $send['type']='NS';
    $send['detail'][1]='coco.bunny.net';
    $send['detail'][2]='kiki.bunny.net';
    $send['ttl']=600;
};

if($type=='CNAME'){
    $send['type']='CNAME';
    $send['detail'][1]='baidu.cn';
    $send['detail'][2]='baidu.com';
    $send['ttl']=600;
}

if($type=='CNAME'){
    $send['type']='CNAME';
    $send['detail'][1]='baidu.cn';
    $send['detail'][2]='baidu.com';
    $send['ttl']=600;
}

#无记录情况下返回SOA记录或域名不存在记录，防止报错
if(unset($set['type'])||unset($set['detail'])||unset($set['ttl'])||$type=='SOA'){
    $send['type']='SOA';
    $send['detail']['type']='null';
    $send['detail']['name']=$name;
    /**
     * SOA类型，如遇域名存在但无该查询类型的记录时返回
     * 请勿随意填写，本workermanDNS协议将自动向服务器配置的DNS获取SOA
     * 
     * 自行返回SOA记录(不建议)[除非你真的准备直接把域名NS到这里]
     * $send['detail']['type']='self';
     * $send['detail']['mname']='dns31.hichina.com'; #主DNS服务器名
     * $send['detail']['rname']='hostmaster.hichina.com' #DNS管理员邮箱
     * $send['detail']['serial']='2022052002' #序列号 序列号必须递增 类似于dns记录的版本号 序列号变大时递归dns将更新记录
     * $send['detail']['refresh']='3600' #区域应当被刷新前的时间间隔
     * $send['detail']['retry']='1200' #刷新失败重试的时间间隔
     * $send['detail']['expire']='86400' #规定在区域不再是权威的之前可以等待的时间间隔的上限
     * $send['detail']['minimum-ttl']='600' #最小TTL 
     * 
     **/
     
};

#id和query一般情况下直接返回输出即可
$send['id']=$data->id;
$send['query']=$data->query;



$send=json_encode($send);
$connection->send($send);


};
Worker::runAll();