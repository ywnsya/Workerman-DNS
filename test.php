<?php
use Workerman\Worker;
use Workerman\Protocols\Dns;
require_once __DIR__ . '/php-ipv6.php'; #IPv6支持
require_once __DIR__ . '/vendor/autoload.php';

echo long2ip(hexdec(substr('7541b3'.'00000000',0,8)));