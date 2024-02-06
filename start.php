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
if(isset($data->addR->csubnet->ip)){
    $ip=$data->addR->csubnet->ip;
}else{
    $ip=$rip;
}


if($type=='A'){
    $send['type']='A';
    $send['detail'][1]='119.29.29.29';
    $send['detail'][2]='8.8.8.8';
    $send['ttl']=30;
};

#此处进行一个CNAME+A记录返回的实例
if($type=='A' && $name=='cn.bing.com'){
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

if($type=='AAAA'){
    $ipv6=new IPv6;
    $send['type']='AAAA';
    $send['detail'][1]=bin2hex($ipv6->ip2bin("fe80::2c5f")); #此操作可以还原被简化的IPv6地址 协议内不再对IPv6地址进行处理，请按照本方式传递16进制无":"的完整16位IPv6
    $send['detail'][2]=bin2hex($ipv6->ip2bin("2001:0:2851:b9d0:2c5f:f0d9:21be:4b96"));
    $send['ttl']=600;
}
#此处进行一个CNAME+AAAA记录返回的实例
if($type=='AAAA' && $name=='cname6.xx.com'){
    $send['type']='CNAME+AAAA';
    #CNAME+A和CNAME+AAAA的情况下，均只会返回一条CNAME，如多条CNAME的均衡负载请通过您的代码在此处服务端实现
    $send['detail']='ipv6.xx.com';
    $send['ttl']=30;
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

if($type=='SOA'){

    $send['type']='SOA';
    $send['detail']= array();

    $send['detail']['type']='auto';#自动获取上级的SOA
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

     
}

#返回域名不存在(自动获取SOA)
if($name=='1.phpisthebestlanguage.com'){
    $send['type']='none';
    $send['detail']= array();
    $send['detail']['type']='auto';
    $send['detail']['name']="$name";
    /**
     * none类型将返回NXDOMAIN，同时通过自动获取SOA返回SOA记录。
     */
    /**
     * 
     * 手动自行返回SOA记录(如果为权威服务器时)
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
}
if($name=='2.phpisthebestlanguage.com'){
    $send['type']='none';
    $send['detail']= array();
    $send['detail']['type']='self';
    #手动自行返回SOA记录与NXDOMAIN时的示例
    $send['detail']['qname']='phpisthebestlanguage.com';#根域名
    $send['detail']['mname']='dns31.hichina.com'; #主DNS服务器名
    $send['detail']['rname']='hostmaster.hichina.com'; #DNS管理员邮箱
    $send['detail']['serial']='2022052002'; #序列号 序列号必须递增 类似于dns记录的版本号 序列号变大时递归dns将更新记录
    $send['detail']['refresh']='3600'; #区域应当被刷新前的时间间隔
    $send['detail']['retry']='1200'; #刷新失败重试的时间间隔
    $send['detail']['expire']='86400'; #规定在区域不再是权威的之前可以等待的时间间隔的上限
    $send['detail']['minimum-ttl']='600'; #最小TTL 
    $send['ttl']='180'; #当前TTL 
}
if($name=='404.testdnsserver.com'){
    $send['type']='flag';
    $send['flag']='NXDOMAIN';
    #使用flag类型返回NXDOMAIN时不会附带SOA,当作为权威DNS时可能造成LDNS发生错误,不建议使用,建议改为none类型
}
if($name=='503.testdnsserver.com'){
    $send['type']='flag';
    $send['flag']='SERVFAIL';
    #SERVFAIL表示解析遇到错误,类似于HTTP的503
}
if($name=='403.testdnsserver.com'){
    $send['type']='flag';
    $send['flag']='REFUSE';
    #REFUSE表示服务器拒绝此请求,可用于限定客户端的IP范围
}
if($name=='raw.testdnsserver.com'){
    $send['type']='raw';
    $status='8180';
    $questions='0001';
    $AnswerRRs='0001';
    $AuthorityRRs='0000';
    $AdditionalRRs='0000';
    $answer='c00c000c00010000001e001203646e73086c617973656e736503636f6d00';#此处示例为返回记录值为dns.laysense.com的PTR记录的16进制
    $response=$data->id.$status.$questions.$AnswerRRs.$AuthorityRRs.$AdditionalRRs.$data->query.$answer;
    $send['detail']=$response;
}

#id和query一般情况下直接返回输出即可
$send['id']=$data->id;
$send['query']=$data->query;
if(!isset($send['ttl'])){
    $send['ttl']=0;
}
$send['info']=json_encode(['domain'=>$data->name,'querytype'=>$data->type,'answertype'=>$send['type'],'ip'=>$ip,'rip'=>$rip,'ttl'=>$send['ttl'],'detail'=>$send['detail']]);


$send=json_encode($send);
$connection->send($send);


};
Worker::runAll();