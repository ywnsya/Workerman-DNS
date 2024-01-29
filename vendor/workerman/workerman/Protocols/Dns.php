<?php
/**
 * Workerman DNS Protocol
 * @author   Enoch EchoNoch Enoch@laysense.com
 * @Repo     http://git.laysense.com/enoch/workerman-dns
 * @Github   http://github.com/ywnsya/workerman-dns
 * @license  http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Workerman\Protocols;
class Dns
{
    /**
     * 检查包的完整性
     * 如果能够得到包长，则返回包的在buffer中的长度，否则返回0继续等待数据
     * 如果协议有问题，则可以返回false，当前客户端连接会因此断开
     * @param string $buffer
     * @return int
     */
    public static function input($buffer)
    {
        
        return 200;
    }

    /**
     * 打包，当向客户端发送数据的时候会自动调用
     * @param string $buffer
     * @return string
     */
    public static function encode($buffer)
    {
        
        $buffer=json_decode($buffer);
        $type=$buffer->type;
        switch($type){
            case 'A':
                $type='0001';
                #$lenth='0004';
                $ip=$buffer->detail;
                $n=0;
                foreach($ip as $i){
                    $nss=explode('.',$i);
                    $detail[$n]='';
                    foreach($nss as $part){
                        $tpart=str_pad(dechex($part),2,"0",STR_PAD_LEFT);
                        $detail[$n]=$detail[$n].$tpart;
                    };
                    $lenth[$n]=str_pad(dechex((strlen($detail[$n])/2)),4,"0",STR_PAD_LEFT);
                    $n=$n+1;
                };
            break;
            case 'NS':
                $type='0002';
                #$lenth='0004';
                $ns=$buffer->detail;
                $n=0;
                foreach($ns as $i){
                    $nss=explode('.',$i);
                    $detail[$n]='';
                    foreach($nss as $part){
                        #$len=strlen($part); 
                        $len=str_pad(dechex(strlen($part)),2,"0",STR_PAD_LEFT);
                        $tpart=bin2hex($part);
                        $detail[$n]=$detail[$n].$len.$tpart;
                    };
                    $detail[$n]=$detail[$n].'00';
                    $lenth[$n]=str_pad(dechex((strlen($detail[$n])/2)),4,"0",STR_PAD_LEFT);
                    $n=$n+1;
                };
            break;
            case 'PTR':
                    $type='000C';
                    $ns=$buffer->detail;
                    $nss=explode('.',$ns);
                    $detail[0]='';
                    foreach($nss as $part){
                        $len=str_pad(dechex(strlen($part)),2,"0",STR_PAD_LEFT);
                        $tpart=bin2hex($part);
                        $detail[0]=$detail[0].$len.$tpart;
                    };
                    $detail[0]=$detail[0].'00';
                    $lenth[0]=str_pad(dechex((strlen($detail[0])/2)),4,"0",STR_PAD_LEFT);
            break;
            case 'CNAME':
                $type='0005';
                $ns=$buffer->detail;
                $n=0;
                foreach($ns as $i){
                    $nss=explode('.',$i);
                    $detail[$n]='';
                    foreach($nss as $part){
                        #$len=strlen($part); 
                        $len=str_pad(dechex(strlen($part)),2,"0",STR_PAD_LEFT);
                        $tpart=bin2hex($part);
                        $detail[$n]=$detail[$n].$len.$tpart;
                    };
                    $detail[$n]=$detail[$n].'00';
                    $lenth[$n]=str_pad(dechex((strlen($detail[$n])/2)),4,"0",STR_PAD_LEFT);
                    $n=$n+1;
                };
            break;
            case 'CNAME+A':
                $type='0005';
                $ns=$buffer->detail;
                $nss=explode('.',$ns);
                $detail[0]='';
                foreach($nss as $part){
                    $len=str_pad(dechex(strlen($part)),2,"0",STR_PAD_LEFT);
                    $tpart=bin2hex($part);
                    $detail[0]=$detail[0].$len.$tpart;
                };
                $detail[0]=$detail[0].'00';
                $lenth[0]=str_pad(dechex((strlen($detail[0])/2)),4,"0",STR_PAD_LEFT);

                $ttl=str_pad(dechex($buffer->ttl),8,"0",STR_PAD_LEFT);
                
                $answer='';
                $answer=$answer.'C00C'.$type.'0001'.$ttl.$lenth[0].$detail[0];
                
                $ip=dns_get_record($ns,DNS_A);
                $type='0001';
                $n=0;
                foreach($ip as $i){
                    $ttl=str_pad(dechex($i['ttl']),8,"0",STR_PAD_LEFT);
                    $i=$i['ip'];
                    $nss=explode('.',$i);
                    $detail[$n]='';
                    foreach($nss as $part){
                        $tpart=str_pad(dechex($part),2,"0",STR_PAD_LEFT);
                        $detail[$n]=$detail[$n].$tpart;
                    };
                    $lenth[$n]=str_pad(dechex((strlen($detail[$n])/2)),4,"0",STR_PAD_LEFT);
                    $n=$n+1;
                    
                };
                $n=0;
                foreach($detail as $c){
                    $rlenth='';
                    $rlenth=$lenth[$n];
                    $n=$n+1;
                    $answer=$answer.'C02B'.$type.'0001'.$ttl.$rlenth.$c;
                }

                $status='8180';
                $questions='0001';
                $AuthorityRRs='0000';
                $AdditionalRRs='0000';

                $AnswerRRs=str_pad((count((array)$ip)+1),4,"0",STR_PAD_LEFT);

                $response=$buffer->id.$status.$questions.$AnswerRRs.$AuthorityRRs.$AdditionalRRs.$buffer->query.$answer;
                return hex2bin($response);

            break;
            case 'CNAME+AAAA':
                $type='0005';
                $ns=$buffer->detail;
                $nss=explode('.',$ns);
                $detail[0]='';
                foreach($nss as $part){
                    $len=str_pad(dechex(strlen($part)),2,"0",STR_PAD_LEFT);
                    $tpart=bin2hex($part);
                    $detail[0]=$detail[0].$len.$tpart;
                };
                $detail[0]=$detail[0].'00';
                $lenth[0]=str_pad(dechex((strlen($detail[0])/2)),4,"0",STR_PAD_LEFT);

                $ttl=str_pad(dechex($buffer->ttl),8,"0",STR_PAD_LEFT);
                
                $answer='';
                $answer=$answer.'C00C'.$type.'0001'.$ttl.$lenth[0].$detail[0];
                
                $ip=dns_get_record($ns,DNS_AAAA);
                $type='001C';
                $n=0;
                foreach($ip as $i){
                    $ipv6=$i['ipv6'];
                    $hexstr = unpack("H*hex", inet_pton($ipv6));
                    $ipv6=substr(preg_replace("/([A-f0-9]{4})/", "$1:", $hexstr['hex']), 0, -1);
                    $ipv6=str_replace(':','',$ipv6);
                    #$ipv6= bin2hex($ipv6);
                    $detail[$n]="$ipv6";
                    $lenth[$n]="0010";
                    $n=$n+1;
                };

                $n=0;
                foreach($detail as $c){
                    $rlenth='';
                    $rlenth=$lenth[$n];
                    $n=$n+1;
                    $answer=$answer.'C02C'.$type.'0001'.$ttl.$rlenth.$c;
                }

                $status='8180';
                $questions='0001';
                $AuthorityRRs='0000';
                $AdditionalRRs='0000';

                $AnswerRRs=str_pad((count((array)$ip)+1),4,"0",STR_PAD_LEFT);

                $response=$buffer->id.$status.$questions.$AnswerRRs.$AuthorityRRs.$AdditionalRRs.$buffer->query.$answer;
                return hex2bin($response);

            break;
            case 'SOA':
                $type='0006';
                $ns=$buffer->detail;
                $ns=json_decode( json_encode( $ns),true);
                if($ns['type']=='none'){
                    $Rns=dns_get_record($ns['name'],DNS_SOA);
                    $Rns=$Rns[0];
                    $ns=$Rns;
                    $buffer->ttl=$Rns['ttl'];
                }

                    $nss=explode('.',$ns['mname']);
                    $detail[0]='';
                    foreach($nss as $part){
                        $len=str_pad(dechex(strlen($part)),2,"0",STR_PAD_LEFT);
                        $tpart=bin2hex($part);
                        $detail[0]=$detail[0].$len.$tpart;
                    };
                    $detail[0]=$detail[0].'00';
                    unset($nss,$len,$tpart);
                    $nss=explode('.',$ns['rname']);
                    foreach($nss as $part){
                        $len=str_pad(dechex(strlen($part)),2,"0",STR_PAD_LEFT);
                        $tpart=bin2hex($part);
                        $detail[0]=$detail[0].$len.$tpart;
                    };
                    $detail[0]=$detail[0].'00'.str_pad(dechex($ns['serial']),8,"0",STR_PAD_LEFT).str_pad(dechex($ns['refresh']),8,"0",STR_PAD_LEFT).str_pad(dechex($ns['retry']),8,"0",STR_PAD_LEFT).str_pad(dechex($ns['expire']),8,"0",STR_PAD_LEFT).str_pad(dechex($ns['minimum-ttl']),8,"0",STR_PAD_LEFT);


                    $lenth[0]=str_pad(dechex((strlen($detail[0])/2)),4,"0",STR_PAD_LEFT);
            break;
            case 'AAAA':
                $type='001C';
                $ip=$buffer->detail;
                $n=0;
                foreach($ip as $i){
                    $detail[$n]="$i";
                    $lenth[$n]="0010";
                    $n=$n+1;
                };
            break;
            case 'TEXT':
                $type='0010';
                $ns=$buffer->detail;
                $n=0;
                foreach($ns as $i){
                    $detail[$n]='';
                    $text=bin2hex($i);
                    $tlen=str_pad(dechex((strlen($text)/2)),2,"0",STR_PAD_LEFT);
                    $detail[$n]=$tlen.$text;
                    $lenth[$n]=str_pad(dechex((strlen($detail[$n])/2)),4,"0",STR_PAD_LEFT);
                    $n=$n+1;
                };
            break;
            case 'MX':
                $type='000F';
                $ns=$buffer->detail;
                $n=0;

                print_r($ns);

                foreach($ns as $i){
                    $nss=explode('.',$i->name);
                    $detail[$n]='';
                    foreach($nss as $part){
                        #$len=strlen($part); 
                        $len=str_pad(dechex(strlen($part)),2,"0",STR_PAD_LEFT);
                        $tpart=bin2hex($part);
                        $detail[$n]=$detail[$n].$len.$tpart;
                    };
                    $detail[$n]=$detail[$n].'00';
                    $lenth[$n]=str_pad(dechex((strlen($detail[$n])/2)+2),4,"0",STR_PAD_LEFT).str_pad(dechex($i->pre),4,"0",STR_PAD_LEFT);
                    $n=$n+1;
                };
            break;
            case 'none':
                $type='0006';
                $ns=$buffer->detail;
                $url=$ns;
                while(true){
                preg_match("#\.(.*)#i",$url,$match);//获取根域名
                $domin = $match[1];
                $soa=dns_get_record($domin,DNS_SOA);
                if(array_key_exists('0',$soa)){
                    if(array_key_exists('mname',$soa[0])){
                    $qname=$domin;
                    $ns=$soa[0];
                    break;
                    }else{
                        $url=$domin;
                    }
                }else{
                    $url=$domin;
                }
                }

                    $nss=explode('.',$ns['mname']);
                    $detail='';
                    foreach($nss as $part){
                        $len=str_pad(dechex(strlen($part)),2,"0",STR_PAD_LEFT);
                        $tpart=bin2hex($part);
                        $detail=$detail.$len.$tpart;
                    };
                    $detail=$detail.'00';
                    unset($nss,$len,$tpart);
                    $nss=explode('.',$ns['rname']);
                    foreach($nss as $part){
                        $len=str_pad(dechex(strlen($part)),2,"0",STR_PAD_LEFT);
                        $tpart=bin2hex($part);
                        $detail=$detail.$len.$tpart;
                    };
                    $detail=$detail.'00'.str_pad(dechex($ns['serial']),8,"0",STR_PAD_LEFT).str_pad(dechex($ns['refresh']),8,"0",STR_PAD_LEFT).str_pad(dechex($ns['retry']),8,"0",STR_PAD_LEFT).str_pad(dechex($ns['expire']),8,"0",STR_PAD_LEFT).str_pad(dechex($ns['minimum-ttl']),8,"0",STR_PAD_LEFT);


                    $lenth=str_pad(dechex((strlen($detail)/2)),4,"0",STR_PAD_LEFT);
                    $ttl=str_pad(dechex($buffer->ttl),8,"0",STR_PAD_LEFT);
                    $status='8183';
                    $questions='0001';
                    $AnswerRRs='0000';
                    $AuthorityRRs='0001';
                    $AdditionalRRs='0000';

                    #$qname
                    $nss=explode('.',$qname);
                    $qname='';
                    foreach($nss as $part){
                        #$len=strlen($part); 
                        $len=str_pad(dechex(strlen($part)),2,"0",STR_PAD_LEFT);
                        $tpart=bin2hex($part);
                        $qname=$qname.$len.$tpart;
                    };
                    $qname=$qname.'00';

                    $answer='';                    
                    $answer=$answer.$qname.$type.'0001'.$ttl.$lenth.$detail;
                    $response=$buffer->id.$status.$questions.$AnswerRRs.$AuthorityRRs.$AdditionalRRs.$buffer->query.$answer;
                    return hex2bin($response);
            break;
        }
        $ttl=str_pad(dechex($buffer->ttl),8,"0",STR_PAD_LEFT);
        $status='8180';
        $questions='0001';
        $AnswerRRs=str_pad(count((array)$buffer->detail),4,"0",STR_PAD_LEFT);
        $AuthorityRRs='0000';
        $AdditionalRRs='0000';
        $answer='';
        $n=0;
        foreach($detail as $c){
            $rlenth='';
            $rlenth=$lenth[$n];
            $n=$n+1;
            $answer=$answer.'C00C'.$type.'0001'.$ttl.$rlenth.$c;
        }
        $response=$buffer->id.$status.$questions.$AnswerRRs.$AuthorityRRs.$AdditionalRRs.$buffer->query.$answer;
        return hex2bin($response);
    }

    /**
     * 解包，当接收到的数据字节数等于input返回的值（大于0的值）自动调用
     * 并传递给onMessage回调函数的$data参数
     * @param string $buffer
     * @return string
     */
    public static function decode($buffer)
    {
    /** 
        $data=bin2hex($buffer);
        echo $data;
        $id=substr($data,0,4);
        $flag=substr($data,5,4);
    $type=substr($data,-8,4);
    switch($type){
        case '0001':
            $type='A';
            break;
        case '0002':
            $type='NS';
            break;
        case '000c':
            $type='PTR';
            break;
        case '0006':
            $type='SOA';
            break;
        case '001c':
            $type='AAAA';
            break;
        case '0005':
            $type='CNAME';
            break;
        case '0010':
            $type='TEXT';
            break;
        case '000f':
            $type='MX';
            break;                                
    }
    $name=substr($data,24,-8);
    $namede=str_split($name,2);
    $realname='';
    foreach($namede as $cha){
        $chat=hex2bin($cha);
        if(!ctype_alnum($chat)){
            $chat='.';
        }
        $realname=$realname.$chat;
    }
    $realname=substr($realname,1,-1);
    $query=substr($data,24);
    ***/

    #$returndata="$type".'|||'."$realname";
    $data=bin2hex($buffer);
    $id=substr($data,0,4);
$flag=substr($data,4,4);
$questions=substr($data,8,4);
$answerRRs=substr($data,12,4);
$authorityRRs=substr($data,16,4);
$additionalRRs=substr($data,20,4);
$startbyte=24;
$dlen=substr($data,$startbyte,2);
$startbyte=26;
$i=1;
while($dlen!='00'){
$domain[$i]=hex2bin(substr($data,$startbyte,hexdec($dlen)*2));
$startbyte=$startbyte+(hexdec($dlen)*2);
$dlen=substr($data,$startbyte,2);
$startbyte=$startbyte+2;
$i++;
}
$realname=join(".",$domain);
$type=substr($data,$startbyte,4);
switch($type){
    case '0001':
        $type='A';
        break;
    case '0002':
        $type='NS';
        break;
    case '000c':
        $type='PTR';
        break;
    case '0006':
        $type='SOA';
        break;
    case '001c':
        $type='AAAA';
        break;
    case '0005':
        $type='CNAME';
        break;
    case '0010':
        $type='TEXT';
        break;
    case '000f':
        $type='MX';
        break;                                
}
$query=substr($data,24,$startbyte-16);


    $returndata= json_encode(array('type' => $type, 'name' => "$realname", 'id'=>"$id", 'query'=>"$query"));

        return $returndata;
    }
}