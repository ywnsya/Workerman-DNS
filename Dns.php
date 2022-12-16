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
        }
        $ttl=str_pad(dechex($buffer->ttl),8,"0",STR_PAD_LEFT);
        $status='8180';
        $questions='0001';
        $AnswerRRs=str_pad(count((array)$buffer->detail),4,"0",STR_PAD_LEFT);
        #$AnswerRRs='0001';
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
        $data=bin2hex($buffer);
        $id=substr($data,0,4);
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

    #$returndata="$type".'|||'."$realname";
    $returndata= json_encode(array('type' => $type, 'name' => "$realname", 'id'=>"$id", 'query'=>"$query"));

        return $returndata;
    }
}