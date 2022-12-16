
<?php
/* PHP-IPv6 V1.0.
 * Copyright (c) 2010 Mr.Bin <bin_jly@163.com>
 */
class ipv6 {
    function addr($addr=null) {
        // 常规获取IPv6地址或格式化IP地址为IPv6格式
        !$addr && ($addr = $_SERVER['REMOTE_ADDR']);
        $type = self::type($addr);
        if ( $type === 6 && self::ipv6_check($addr) ) return $addr;
        elseif ( $type === 4 ) return self::ip426($addr);
        else return 'Unknown';
    }
 
    function realip() {
        /* 穿过代理获取真实IP地址
         * 返回值为数组，array[0]为真实IP，array[1]为代理IP(可能为空)
         * 若array[0]和array[1]相等，则实际真实IP可能无法获取(高度匿名?)
         */
        $is_proxy = false;
        if ( $_SERVER['HTTP_X_FORWARDED_FOR'] ) {
            $is_proxy = true;
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach($ips as $ip){
                if ( !self::wan_ip($ip) ) $ip = false;
                else break;
            }
        }
        if ( !$ip && $_SERVER['HTTP_CLIENT_IP'] ) {
            $is_proxy = true;
            $ip = $_SERVER['HTTP_CLIENT_IP'];
            if ( !self::wan_ip($ip) ) $ip = false;
        }
        if ( $is_proxy ) {
            $proxy = $_SERVER['REMOTE_ADDR'];
            if (!$ip) $ip = $proxy;
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return explode(',', $ip.','.$proxy);
    }
 
    function cut($addr) {
        // 压缩IPv6地址
        if (!self::ipv6_check($addr)) return $addr;
        $addr = self::fill($addr);
        $arr = explode(':',$addr);
        foreach ($arr as $a) {
            $arr2[] = preg_replace('/^0{1,3}(\w+)/','\1',$a);
        }
        $addr = join(':',$arr2);
        $olen = strlen($addr);
        for($i=6;$i>0;$i--){
            // 初步压缩
            $addr = preg_replace('/:(0\:){'.$i.'}/','::',$addr,1);
            if (strlen($addr) < $olen ) break;
        }
        $addr = preg_replace('/^0\:\:/','::',$addr);
        $addr = preg_replace('/\:\:0$/','::',$addr);
        return $addr;
    }
 
    function fill($addr) {
        // 标准IPv6格式
        if (!self::ipv6_check($addr)) return $addr;
        $addr = self::_fix_v4($addr);
        $arr = explode(':',$addr);
        foreach ($arr as $a) {
            $l = strlen($a);
            if ( $l > 0 && $l < 4 )
                $arr2[] = str_repeat('0', 4-$l).$a;
            else $arr2[] = $a;
        }
        $addr = join(':',$arr2);
        $fil = ':'.str_repeat('0000:', 9-count($arr));
        $addr = str_replace('::',$fil,$addr);
        $addr = preg_replace('/^\:/','0000:',$addr);
        $addr = preg_replace('/\:$/',':0000',$addr);
        return $addr;
    }
 
    function ip2bin($addr) {
        $type = self::type($addr);
        if ( $type === 0 ) return false;
        elseif ( $type === 4 ) $addr = self::ip426($addr);
        else $addr = self::fill($addr);
        $hexstr = str_replace(':','',$addr);
        return pack('H*', $hexstr);
    }
 
    function bin2ip($bin) {
        if ( strlen($bin) !== 16 ) return false;
        $arr = str_split(join('',unpack('H*', $bin)), 4);
        $addr = join(':',$arr);
        return $addr;
    }
 
    function ip426($addr) {
        // IPv4 to IPv6
        if (!self::ipv4_check($addr)) return $addr;
        $hex = dechex(self::ip2long($addr));
        $hex = str_repeat('0', 8-strlen($hex)).$hex;
        $ipv6 = '0000:0000:0000:0000:0000:0000:';
        $ipv6 .= substr($hex,0,4) . ':' . substr($hex,4,4);
        return $ipv6;
    }
 
    function type($addr) {
        if ( self::ipv6_check($addr) ) return 6;
        elseif ( self::ipv4_check($addr) ) return 4;
        else return 0;
    }
 
    function ipv4_check($addr) {
        $arr = explode('.', $addr);
        $l = count($arr);
        for ( $i=0;$i<$l;$i++ ) {
            if ( strlen($arr[$i]) > 3 ) return false;
            if ( !is_numeric($arr[$i]) ) return false;
            $a = intval($arr[$i], 10);
            if ($a > 255 || $a <0) return false;
        }
        return true;
    }
 
    function ipv6_check($addr) {
        $addr = self::_fix_v4($addr);
        if ( strpos($addr, '.') ) return false;
        $l1 = count(explode('::',$addr));
        if ( $l1 > 2 ) return false;
        $l2 = count(explode(':',$addr));
        if ( $l2 < 3 || $l2 > 8 ) return false;
        if ( $l2 < 8 && $l1 !== 2 ) return false;
        preg_match('/^([0-9a-f]{0,4}\:)+[0-9a-f]{0,4}$/i',$addr,$arr);
        if ( !$arr[0] ) return false;
        return true;
    }
 
    function ip2long($addr) {
        $arr = explode('.', $addr);
        $l = count($arr);
        $long = 0;
        for ( $i=0;$i<$l;$i++ ) {
            if ( strlen($arr[$i]) > 3 ) return false;
            if ( !is_numeric($arr[$i]) ) return false;
            $a = intval($arr[$i], 10);
            if ($a > 255 || $a <0) return false;
            $long += $a * pow(2, 24-$i*8);
        }
        return $long;
    }
 
    function wan_ip($addr) {
        // 检查外网可用地址
        if ( self::ipv6_check($addr) ) {
            $addr = self::fill($addr);
            // IPv4类地址处理
            $v4p = substr($addr,0,29);
            if ( $v4p == '0000:0000:0000:0000:0000:0000'
                || strtolower($v4p) == 'ffff:0000:0000:0000:0000:0000' ) {
                $t = str_replace($v4p,'',$addr);
                $t = str_replace(':','',$t);
                $ipv4 = long2ip(hexdec($t));
                return self::_wan_ipv4($ipv4);
            }
            // 取前16位进行比较
            $v6p = substr($addr,0,4);
            $bin = decbin(hexdec($v6p));
            $p = str_repeat(0, 16-strlen($bin)).$bin;
            if ( (($p&'1110000000000000')=='0010000000000000') //2000::/3
                || (($p&'1111111000000000')=='1111110000000000') //FC00::/7
                || (($p&'1111111111000000')=='1111111010000000') //FE80::/10
                || (($p&'1111111100000000')=='1111111100000000') //FF00::/8
                ) return false;
            return true;
        } else {
            return self::_wan_ipv4($addr);
        }
    }
 
    private function _wan_ipv4($addr){
        if ( !self::ipv4_check($addr) ) return false;
        $arr = explode('.',$addr);
        $bin = decbin($arr[0]*256+$arr[1]);
        $p = str_repeat(0, 16-strlen($bin)).$bin;
        $p8 = $p & '1111111100000000';
        $p16 = &$p;
        if ( ($p8 == '0000000000000000') // 0/8
            || ($p8 == '0000010100000000') // 5/8
            || ($p8 == '0000101000000000') // 10/8
            || ($p8 == '0001011100000000') // 23/8
            || ($p8 == '0010010000000000') // 36/8
            || ($p8 == '0010010100000000') // 37/8
            || ($p8 == '0010011100000000') // 39/8
            || ($p8 == '0010101000000000') // 42/8
            || ($p8 == '0110010000000000') // 100/8
            || ($p8 == '0110011000000000') // 102/8
            || ($p8 == '0110011100000000') // 103/8
            || ($p8 == '0110100000000000') // 104/8
            || ($p8 == '0110100100000000') // 105/8
            || ($p8 == '0110101000000000') // 106/8
            || ($p8 == '0111111100000000') // 127/8
            || ($p16 == '1010100111111110') // 169.254/16
            || (($p&'1111111111110000')=='1010110000010000') // 172.16/12
            || ($p8 == '1011001100000000') // 179/8
            || ($p8 == '1011100100000000') // 185/8
            || ($p16 == '1100000010101000') // 192.168/16
            || (($p&'1110000000000000')=='1110000000000000') // 224/8-255/8
            ) return false;
        return true;
    }
 
    private function _fix_v4($addr) {
        // 修正IPv4位址类IPv6格式为标准IPv6格式，不验证合法性
        if ( !strpos($addr, '.') ) return $addr;
        preg_match('/(\d+\.){3}\d+$/',$addr,$arr);
        if ( !self::ipv4_check($arr[0]) ) return $addr;
        $hex = dechex(self::ip2long($arr[0]));
        $hex = str_repeat('0', 8-strlen($hex)).$hex;
        $v4p = substr($hex,0,4) . ':' . substr($hex,4,4);
        $p1 = str_replace($arr[0],'',$addr);
        strtolower($p1) === 'ffff:' && $p1 = '::'.$p1;
        $addr = $p1 . $v4p;
        return $addr;
    }
}
?>