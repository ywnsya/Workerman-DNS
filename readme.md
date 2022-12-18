# Workerman-DNS

[Workerman](https://www.workerman.net/)的DNS协议，实现了简单的DNS协议解析和响应，通过本协议支持，您可以利用[Workerman](https://www.workerman.net/)实现基于PHP的Dns服务器

目前支持以下DNS类型：

* A
* AAAA
* CNAME
* SOA
* PTR
* MX
* TXT

> 本仓库内vendor文件夹为[Workerman](https://www.workerman.net/)您可以删除.
>
> 直接将本仓库根目录下的 Dns.php 放置到您的Workerman项目中的 /vendor/workerman/workerman/Protocols 目录下即可使用

---

## 使用方式：

详见start.php 文件

> 注意：使用53端口需要root权限

#### 1.监听端口

```php
<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
require_once __DIR__ . '/php-ipv6.php'; #IPv6支持
require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker('Dns://0.0.0.0:53');
$worker->transport = 'udp';


```

#### 2.获取查询内容

```php
$worker->onMessage = function($connection, $data){
$data=json_decode($data);
$type=$data->type; #查询类型
$name=$data->name; #查询内容(一般是域名，PTR时为倒序IP)
$rip=$connection->getRemoteIp(); #客户端IP

#输出信息
echo "\n Type:$type \n Domain: $name\n Client IP: $rip \n";

}
```

#### 3.响应A记录

```php
$worker->onMessage = function($connection, $data){

$send['type']='A';
$send['detail'][1]='119.29.29.29';  #第一条记录
$send['detail'][2]='8.8.8.8';	    #第二条记录
$send['ttl']=30;



#id和query一般情况下直接返回输出即可
$send['id']=$data->id;
$send['query']=$data->query;



$send=json_encode($send);
$connection->send($send);


};
Worker::runAll();
```

#### 4.响应其他记录

见start.php 内有所有记录类型的响应方式

#### 5.说明

您应当通过获取query的 `$name`通过查询数据库等方式返回数据，对于不存在的记录应当返回SOA记录

您需要的时候可以通过 `dns_get_record()`向上级DNS递归查找并缓存

这一系列操作，本协议不提供，您可以自行通过Redis等并利用workerman实现

不建议作为根域名的NS服务器使用。

## 已知问题

本协议最早写于鄙人刚学习php的阶段，现在翻出来无疑是屎山一坨，代码写的和xxs一样，性能不敢测试，还请各位大佬包容

目前已知问题是：

域名不存在时可能出现BUG
