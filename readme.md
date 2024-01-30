# WorkermanDNS DOC

# 有什么用

让您能够使用Workerman编写DNS服务器。

该库仅提供DNS协议的响应方式和DNS请求的解析。记录的查询、储存、缓存都需要您自行编写完成。

# 支持特性

- 支持的协议：
    
    A AAAA CNAME MX PTR NS TXT SOA
    
- 支持的Flag标签：
    - 8180：OK≈HTTP200 ：正常
    - 8182：SERVFAIL≈HTTP503 ：服务器错误
    - 8183：NXDOMAIN≈HTTP404 ：记录不存在
    - 8185：REFUSE≈HTTP403：拒绝请求

# 安装

### PHP环境

WorkermanDNS基于Workerman PHP框架。是纯PHP编写的DNS服务器。使用php-cli环境运行。

安装环境请参照Workerman要求：

[环境要求-workerman手册](https://www.workerman.net/doc/workerman/install/requirement.html)

### Workerman框架

通过Composer安装Workerman框架

`composer require workerman/workerman`

### 下载Dns.php

可通过

[https://git.laysense.com/enoch/Workerman-DNS/raw/branch/master/Dns.php](https://git.laysense.com/enoch/Workerman-DNS/raw/branch/master/Dns.php)

或

[raw.githubusercontent.com/ywnsya/Workerman-DNS/master/Dns.php](https://raw.githubusercontent.com/ywnsya/Workerman-DNS/master/Dns.php)

或从Releases中：

[Workerman-DNS](https://git.laysense.com/enoch/Workerman-DNS/releases)

下载Dns.php文件

### 下载IPv6支持库

如您需要使用到IPv6功能(您也可以用其他库代替，不影响DNS协议的使用)，请下载IPv6支持库

地址为：

[git.laysense.com/enoch/Workerman-DNS/raw/branch/master/php-ipv6.php](https://git.laysense.com/enoch/Workerman-DNS/raw/branch/master/php-ipv6.php)

或

[raw.githubusercontent.com/ywnsya/Workerman-DNS/master/php-ipv6.php](https://raw.githubusercontent.com/ywnsya/Workerman-DNS/master/php-ipv6.php)

### 放置Dns.php文件

Dns.php文件应当放置在./vendor/workerman/workerman/Protocols下

### 放置php-ipv6.php文件

该文件放置在项目目录./下

### 创建start.php文件

在项目目录./下新建空白的start.php文件即可

### 以上步骤完成后，您的文件夹结构应该如下：

```
.
├── composer.json
├── composer.lock
├── ***php-ipv6.php***
├── ***start.php***
└── vendor
    ├── autoload.php
    ├── composer
    │   ├── ……此处省略……
    └── workerman
        ├── workerman
        │   ├── ……此处省略……
        │   ├── Protocols
        │   │   ├── ***Dns.php***
        │   │   ├── Frame.php
        │   │   ├── Http
        │   │   │   ├── Chunk.php
        │   │   │   ├── Request.php
        │   │   │   ├── Response.php
        │   │   │   ├── ServerSentEvents.php
        │   │   │   ├── Session
        │   │   │   ├── Session.php
        │   │   │   └── mime.types
        │   │   ├── Http.php
        │   │   ├── ProtocolInterface.php
        │   │   ├── Text.php
        │   │   ├── Websocket.php
        │   │   └── Ws.php
        │   ├── ……此处省略……
```

# 基础框架：

以下内容均在start.php中编写

请在start.php中写入：

```php
<?php
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

# 请在下方编写您的DNS响应
#——————————————————————

#——————————————————————
#下方内容无需更改
$send['id']=$data->id;
$send['query']=$data->query;
if(!isset($send['ttl'])){
    $send['ttl']=0;
}
$send['info']=json_encode(['domain'=>$data->name,'querytype'=>$data->type,'answertype'=>$send['type'],'ip'=>$rip,'ttl'=>$send['ttl'],'detail'=>$send['detail']]);
$send=json_encode($send);
$connection->send($send);
};
Worker::runAll();
```

### 请求变量

| 变量名称 | 意义 | 示例 |
| --- | --- | --- |
| $type | 请求类型 | A |
| $name | 请求域名 | dnsservertest.com |
| $rip | 客户端IP | 192.168.1.1 |
| $data->id | 请求ID | 0001 |
| $data->traffic | 请求包大小(Byte) | 88 |

# 编写响应：

### 响应参数

您最终需要回复一个$send的数组。定义如下：

| 名称 | 意义 | 类型 | 示例 | 说明 |
| --- | --- | --- | --- | --- |
| $send['type'] | 响应类型 | 字符串 | $send['type']=A
$send['type']=CNAME | 指定响应的类型 |
| $send['detail'] | 响应内容 | 字符串
数组
多维数组 | 119.29.29.29
$send['detail'][1]='119.29.29.29'
$send['detail']= array(); | 根据不同的响应类型，返回不同的响应。具体请往下阅读各种记录的响应方式 |
| $send['ttl'] | TTL缓存时间 | 数字 | $send['ttl']=600 | 并非所有记录都需要TTL，未传入时默认0 |
| $send['flag'] | Flag类型 | 字符串 | $send['flag']=‘REFUSE’ | 使用flag类型时需指定的flag |
| $send['id'] | ID | 数字 | 0001 | 无需更改无需编写。 |
| $send['query'] | 请求体 | 16进制 |  | 无需更改无需编写。 |
| $send['info'] | 请求和响应信息 | json |  | 无需编写，基础框架内已经完成。 |

您应当根据$name变量，进行响应。

以下内容请在start.php结构中间的空行内编写：

### A记录：

响应一个或多个IPv4地址

$send['detail']应为一个数组。

```php
$send['type']='A';
$send['detail'][1]='119.29.29.29';
$send['detail'][2]='8.8.8.8';
$send['ttl']=30;
```

以上意味着响应IP为119.29.29.29和8.8.8.8，指定TTL为30秒。

- 示例

```php
<?php
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

if($type=='A' && $name=='dnsservertest.com'){
	$send['type']='A';
	$send['detail'][1]='192.168.2.1';
	$send['detail'][2]='192.168.2.2';
	$send['detail'][3]='192.168.2.3';
	$send['ttl']=60;
}

$send['id']=$data->id;
$send['query']=$data->query;
if(!isset($send['ttl'])){
    $send['ttl']=0;
}
$send['info']=json_encode(['domain'=>$data->name,'querytype'=>$data->type,'answertype'=>$send['type'],'ip'=>$rip,'ttl'=>$send['ttl'],'detail'=>$send['detail']]);
$send=json_encode($send);
$connection->send($send);
};
Worker::runAll();
```

以上代码意味着对于dnsservertest.com域名的A记录请求，响应192.168.2.1、2.2、2.3三个IP，指定TTL为60秒。

以下内容将不再给出完整代码示例。

### AAAA记录

AAAA记录响应一个或多个IPv6地址。

```php
$ipv6=new IPv6;
$send['type']='AAAA';
$send['detail'][1]=bin2hex($ipv6->ip2bin("fe80::2c5f")); #此操作可以还原被简化的IPv6地址 协议内不再对IPv6地址进行处理，请按照本方式传递16进制无":"的完整16位IPv6
$send['detail'][2]=bin2hex($ipv6->ip2bin("2001:0:2851:b9d0:2c5f:f0d9:21be:4b96"));
$send['ttl']=30;
```

$send['detail']应当为数组，且值为完整的、没有‘：’的，连续16位16进制IPv6地址。

IPv6库可以如上方示例所述，将IPv6还原完整并去除‘：’，再通过bin2hex的php函数转为16进制

### CNAME

CNAME即别名，将映射到另一个或多个DNS域名，客户端将向另一个域名发送请求。

```php
		$send['type']='CNAME'  
		$send['detail'][1]='hk.dnsservertest.com';
    $send['detail'][2]='jp.dnsservertest.com';
    $send['ttl']=600;
```

$send['detail']一样为数组。

一般情况下，为了提升DNS解析效率，DNS服务器将同时直接返回一个A或AAAA记录，要实现这个请使用CNAME+A或CNAME+AAAA类型

### CNAME+A/AAAA

```php
#CNAME+A
$send['type']='CNAME+A';
$send['detail']='ipv4.dnsservertest.com';
$send['ttl']=30;

#CNAME+AAAA
$send['type']='CNAME+AAAA';
$send['detail']='ipv6.dnsservertest.com';
$send['ttl']=30;
```

 CNAME+A和CNAME+AAAA的情况下，均只支持返回一条CNAME.

因此，此时的$send['detail']应当为字符串

如多条CNAME的均衡负载请通过您的代码在服务端实现。

WorkermanDNS将自动获取对应的A记录并返回。

### TEXT(TXT)

TEXT记录将返回一条文本记录。由于默认的最大包大小为512B，因此应当控制TEXT记录内容在256B之内

```php
$send['type']='TEXT';
$send['detail'][1]='text1-test';
$send['detail'][2]='text2-test';
$send['ttl']=600;
```

$send['detail']为数组。值为TEXT记录内容(字符串)

### MX

MX邮件交换记录，建设邮箱时常用。

```php
    $send['type']='MX';
    $send['detail'][1]['name']='mx.zoho.com';
    $send['detail'][1]['pre']='20'; #权重
    $send['detail'][2]['name']='mx2.zoho.com';
    $send['detail'][2]['pre']='30'; #权重
    $send['detail'][3]['name']='mx3.zoho.com';
    $send['detail'][3]['pre']='50'; #权重
    $send['ttl']=600;
```

$send['detail']为数组，其中的每个元素也是多维数组，分别有name和pre两个参数，分别为MX的记录值和对应权重。

### NS

将子域名转交给其他DNS服务器解析。

```php
$send['type']='NS';
$send['detail'][1]='coco.bunny.net';
$send['detail'][2]='kiki.bunny.net';
$send['ttl']=600;
```

$send['detail']为数组，值为DNS服务器名称。

### SOA

SOA有自动和手动两种

- 自动

```php
	  $send['type']='SOA';
    $send['detail']= array();
    $send['detail']['type']='auto';#自动获取上级的SOA
    $send['detail']['name']="$name";
```

自动则指定 $send['detail']['type']='auto' ，将自行从上级DNS服务器获取SOA信息。以上代码无需改动，直接使用即可。

- 手动

```php
$send['type']='SOA';
$send['detail']= array();
$send['detail']['type']='self';
$send['detail']['mname']='dns31.hichina.com'; #主DNS服务器名
$send['detail']['rname']='hostmaster.hichina.com'; #DNS管理员邮箱
$send['detail']['serial']='2022052002'; #序列号 序列号必须递增 类似于dns记录的版本号 序列号变大时递归dns将更新记录
$send['detail']['refresh']='3600'; #区域应当被刷新前的时间间隔
$send['detail']['retry']='1200'; #刷新失败重试的时间间隔
$send['detail']['expire']='86400'; #规定在区域不再是权威的之前可以等待的时间间隔的上限
$send['detail']['minimum-ttl']='60'; #最小TTL 
$send['ttl']='180'; #当前SOA记录的TTL
```

示例和说明如上。不推荐使用，因为SOA记录的错误可能对递归DNS造成极大影响，除非是您使用WorkermanDNS作为权威DNS时(请尽可能不要这么做)

### None

none是WorkermanDNS自定义的一种方式。将返回NXDOMAIN状态码以及SOA记录，一般用于域名不存在时。同样，SOA部分也可以使用自动和手动两种。

```php
$send['type']='none';
$send['detail']= array();
$send['detail']['type']='auto';
$send['detail']['name']="$name";
```

```php
		$send['type']='none';
    $send['detail']= array();
    $send['detail']['type']='self';
    $send['detail']['qname']='phpisthebestlanguage.com';#根域名
    $send['detail']['mname']='dns31.hichina.com'; #主DNS服务器名
    $send['detail']['rname']='hostmaster.hichina.com'; #DNS管理员邮箱
    $send['detail']['serial']='2022052002'; #序列号 序列号必须递增 类似于dns记录的版本号 序列号变大时递归dns将更新记录
    $send['detail']['refresh']='3600'; #区域应当被刷新前的时间间隔
    $send['detail']['retry']='1200'; #刷新失败重试的时间间隔
    $send['detail']['expire']='86400'; #规定在区域不再是权威的之前可以等待的时间间隔的上限
    $send['detail']['minimum-ttl']='600'; #最小TTL 
    $send['ttl']='180'; #当前TTL
```

### Flag

Flag类型将返回DNS状态码。

- NXDOMAIN
    
    ```php
    $send['type']='flag';
    $send['flag']='NXDOMAIN';
    ```
    
    NXDOMAIN表示域名不存在，使用flag类型返回NXDOMAIN时不会附带SOA,当作为权威DNS时可能造成LDNS发生错误,不建议使用,建议使用none类型替代
    
- SERVFAIL
    
    ```php
    $send['type']='flag';
    $send['flag']='SERVFAIL';
    ```
    
    SERVFAIL表示解析遇到错误,类似于HTTP的503
    
- REFUSE
    
    ```php
    $send['type']='flag';
    $send['flag']='REFUSE';
    ```
    
    REFUSE表示服务器拒绝此请求,可用于限定客户端的IP范围
    

Flag类型无需返回$send['detail']

### Raw

Raw是WorkermanDNS自定义的一种类型，将不再处理，直接以16进制形式响应，从而无需进行16进制拼合，以加快响应速度。一般是在第一次响应完毕后通过截取记录和请求，写入缓存(如Redis或共享变量等)，后续遇到相同请求且在TTL内直接返回。

```php
$send['type']='raw';
$status='8180';
$questions='0001';
$AnswerRRs='0001';
$AuthorityRRs='0000';
$AdditionalRRs='0000';
$answer='c00c000c00010000001e001203646e73086c617973656e736503636f6d00';#此处示例为返回记录值为dns.laysense.com的PTR记录的16进制
$response=$data->id.$status.$questions.$AnswerRRs.$AuthorityRRs.$AdditionalRRs.$data->query.$answer;
$send['detail']=$response;
```

# 流量统计与日志

### 响应(出站)

Dns.php文件中send函数下，将会返回提供$query和$info两个变量，以及$response。

`strlen($response)`将直接可以获取该次响应出站流量(为byte)

$query是请求包体的16进制响应内容

$info为一个json对象，var_dump结构类似如下

```php
object(stdClass)#18 (6) {
  ["domain"]=>
  string(26) "95.188.24.172.in-addr.arpa"
  ["querytype"]=>
  string(3) "PTR"
  ["answertype"]=>
  string(3) "PTR"
  ["ip"]=>
  string(12) "172.24.176.1"
  ["ttl"]=>
  int(30)
  ["detail"]=>
  string(16) "dns.laysense.com"
}
```

以上是一个对172.24.188.95的IP地址提供值为dns.laysense.com的PTR记录的$info示例

包含了请求域名、请求类型、响应类型、客户端IP、ttl等，可供您进行日志统计

### 请求(入站)

请参看上方的请求变量章节。

其中对于流量将传递$data->traffic变量，其为请求包体的大小(Byte)

# 运行

使用

`php start.php start` 运行

`php start.php start -d` 后台运行，自带守护进程

`php start.php stop` 停止运行

`php start.php status` 查看运行状况

请注意：

DNS服务使用53端口，为特权端口，绝大多数情况下必须使用root权限运行。

### 版本

0.1.0@2024/01/31

### About

作者 Enoch[enoch@laysense.com]

- Repo

[Workerman-DNS](https://git.laysense.com/enoch/Workerman-DNS)

- Github

[https://github.com/ywnsya/Workerman-DNS](https://github.com/ywnsya/Workerman-DNS)

### License

**Apache License 2.0**