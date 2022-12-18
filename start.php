<?php
/**
 * Workerman DNS Protocol
 * @author   Enoch EchoNoch Enoch@laysense.com
 * @Repo     http://git.laysense.com/enoch/workerman-dns
 * @Github   http://github.com/ywnsya/workerman-dns
 * @license  http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
require_once __DIR__ . '/php-ipv6.php'; #IPv6支持
require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker('Dns://0.0.0.0:53');
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

#此处进行一个CNAME+A记录返回的实例
if($type=='A' && $name='cn.bing.com'){
    $send['type']='CNAME+A';
    #CNAME+A和CNAME+AAAA的情况下，均只会返回一条CNAME，如多条CNAME的均衡负载请通过您的代码在此处服务端实现
    $send['detail']='china.bing123.com';
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

#实际情况下很少直接返回CNAME，真实情况一般是CNAME+A同时还有CNAME+AAAA
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

if($type=='AAAA'){
    $ipv6=new IPv6;
    $send['type']='AAAA';
    $send['detail'][1]=bin2hex($ipv6->ip2bin("fe80::2c5f")); #此操作可以还原被简化的IPv6地址 协议内不再对IPv6地址进行处理，请按照本方式传递16进制无":"的完整16位IPv6
    $send['detail'][2]=bin2hex($ipv6->ip2bin("2001:0:2851:b9d0:2c5f:f0d9:21be:4b96"));
    $send['ttl']=600;
}

if($type=='TEXT'){
    $send['type']='TEXT';
    $send['detail'][1]='text1-test';
    $send['detail'][2]='text2-test';
    $send['ttl']=600;
}

if($type=='MX'){
    $send['type']='MX';
    $send['detail'][1]['name']='mx.zoho.com';
    $send['detail'][1]['pre']='20'; #权重
    $send['detail'][2]['name']='mx2.zoho.com';
    $send['detail'][2]['pre']='30'; #权重
    $send['detail'][3]['name']='mx3.zoho.com';
    $send['detail'][3]['pre']='50'; #权重
    $send['ttl']=600;
}

#无记录情况下返回SOA记录或域名不存在记录，防止报错
if( (!isset($send['type'])) || (!isset($send['detail'])) || (!isset($send['ttl'])) || $type=='SOA'){

    $send['type']='SOA';
    $send['detail']= array();

    $send['detail']['type']='none';
    $send['detail']['name']=$name;
    
    /**
     * SOA类型，如遇域名存在但无该查询类型的记录时返回
     * 请勿随意填写，本workermanDNS协议将自动向服务器配置的DNS获取SOA
     * 
     * 自行返回SOA记录(不建议)[除非你真的准备直接把域名NS到这里]
     * $send['detail']['type']='self';
     * $send['detail']['mname']='dns31.hichina.com'; #主DNS服务器名
     * $send['detail']['rname']='hostmaster.hichina.com'; #DNS管理员邮箱
     * $send['detail']['serial']='2022052002'; #序列号 序列号必须递增 类似于dns记录的版本号 序列号变大时递归dns将更新记录
     * $send['detail']['refresh']='3600'; #区域应当被刷新前的时间间隔
     * $send['detail']['retry']='1200'; #刷新失败重试的时间间隔
     * $send['detail']['expire']='86400'; #规定在区域不再是权威的之前可以等待的时间间隔的上限
     * $send['detail']['minimum-ttl']='600'; #最小TTL 
     * 
     * $send['ttl']='180'; #当前TTL 
     **/

     
};

#id和query一般情况下直接返回输出即可
$send['id']=$data->id;
$send['query']=$data->query;



$send=json_encode($send);
$connection->send($send);


};
Worker::runAll();