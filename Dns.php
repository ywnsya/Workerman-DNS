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
    public static function getDomain($data,$startbyte){
        $dlen=substr($data,$startbyte,2);
        $startbyte=$startbyte+2;
        $domain[0]='';
        $i=0;
        while($dlen!='00'){
            $domain[$i]=hex2bin(substr($data,$startbyte,hexdec($dlen)*2));
            $startbyte=$startbyte+(hexdec($dlen)*2);
            $dlen=substr($data,$startbyte,2);
            $startbyte=$startbyte+2;
            $i++;
        }
        $realname=join(".",$domain);
        $return=['name'=>$realname,'startbyte'=>$startbyte];
        return $return;
    }
    public static function send($response,$query,$info){
       $response=hex2bin($response);
       $traffic=strlen($response);
       $info=json_decode($info);
       var_dump($info);
       #出流量统计
       #您也可以在此处保存$response,下一次通过raw类型实现快速缓存相应.
       return $response;
    }
    /**
     * 检查包的完整性
     * 如果能够得到包长，则返回包的在buffer中的长度，否则返回0继续等待数据
     * 如果协议有问题，则可以返回false，当前客户端连接会因此断开
     * @param string $buffer
     * @return int
     */
    public static function input($buffer)
    {
        return 512;
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
            case 'raw':
                return Dns::send($buffer->detail,$buffer->query,$buffer->info);
            break;
            case 'flag':
                if($buffer->flag=='NXDOMAIN'){
                    $status='8183';
                    $questions='0001';
                    $AnswerRRs='0000';
                    $AuthorityRRs='0001';
                    /**
                     * NXDOMAIN应当返回SOA记录，主要内容是TTL，LDNS会在SOA的TTL到期前缓存该FLAG，否则会被LDNS递归时拒绝
                     */
                    $AdditionalRRs='0000';
                    $response=$buffer->id.$status.$questions.$AnswerRRs.$AuthorityRRs.$AdditionalRRs.$buffer->query;
                    return Dns::send($response,$buffer->query,$buffer->info);
                }elseif($buffer->flag=='SERVFAIL'){
                    $status='8182';
                    $questions='0001';
                    $AnswerRRs='0000';
                    $AuthorityRRs='0000';
                    $AdditionalRRs='0000';
                    $response=$buffer->id.$status.$questions.$AnswerRRs.$AuthorityRRs.$AdditionalRRs.$buffer->query;
                    return Dns::send($response,$buffer->query,$buffer->info);
                }elseif($buffer->flag=='REFUSE'){
                    $status='8185';
                    $questions='0001';
                    $AnswerRRs='0000';
                    $AuthorityRRs='0000';
                    $AdditionalRRs='0000';
                    $response=$buffer->id.$status.$questions.$AnswerRRs.$AuthorityRRs.$AdditionalRRs.$buffer->query;
                    return Dns::send($response,$buffer->query,$buffer->info);
                }
            break;
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
                return Dns::send($response,$buffer->query,$buffer->info);

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
                return Dns::send($response,$buffer->query,$buffer->info);

            break;
            case 'SOA':
                $type='0006';
                $ns=$buffer->detail;
                $ns=json_decode( json_encode( $ns),true);
                if($ns['type']=='auto'){
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
                $ns=json_decode( json_encode( $ns),true);
                var_dump($ns);
                
                    if($ns['type']=='auto'){
                        $ns=$ns['name'];
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
                    }else{
                        $qname=$ns['qname'];
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
                    return Dns::send($response,$buffer->query,$buffer->info);
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
        $traffic=strlen(hex2bin($response));
        return Dns::send($response,$buffer->query,$buffer->info);
    }

    /**
     * 解包，当接收到的数据字节数等于input返回的值（大于0的值）自动调用
     * 并传递给onMessage回调函数的$data参数
     * @param string $buffer
     * @return string
     */
    public static function decode($buffer)
    {
        $traffic=strlen($buffer);#接收流量
    $data=bin2hex($buffer);
    $id=substr($data,0,4);
    $flag=substr($data,4,4);
    $questions=substr($data,8,4);
    $answerRRs=substr($data,12,4);
    $authorityRRs=substr($data,16,4);
    $additionalRRs=substr($data,20,4);
    $gdomain=Dns::getDomain($data,24);
    $realname=$gdomain['name'];
    $startbyte=$gdomain['startbyte'];
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
    #additionalRRs
    if($authorityRRs=='0000'&&$additionalRRs=='0001'){
        $addR=new \StdClass();
        $startbyte=$startbyte+8;
        $addR_name=Dns::getDomain($data,$startbyte);
        $addR_rname=$addR_name['name'];
        $startbyte=$addR_name['startbyte'];
        if($addR_rname==''){
            $addR_rname=$realname;
        }
        $addR_type=substr($data,$startbyte,4);
        $startbyte=$startbyte+4;
        $addR->realname=$addR_rname;
        $addR->type=$addR_type;
        #OPT
        if($addR_type=='0029'){
            #dns.rr.udp_playload_size,请求定义该值后响应将可突破512byte默认限制
            $addR->playloadSize=hexdec(substr($data,$startbyte,4));
            $addR->rcode=substr($data,$startbyte+4,2);#dns.resp.ext_rcode
            $addR->edns0v=substr($data,$startbyte+6,2);#Edns0 拓展协议版本
            $addR->Z=substr($data,$startbyte+8,4);
            $addR->optLen=hexdec(substr($data,$startbyte+12,4))*2;
            if($addR->optLen!=0){
                $startbyte=$startbyte+16;
                $opt=substr($data,$startbyte,$addR->optLen);
                $opt_type=substr($opt,0,4);
                if($opt_type=='0008'){
                    $addR->opt_type='CSUBNET';
                    $csubnet_len=hexdec(substr($opt,4,4))*2;
                    $csubnet_data=substr($opt,8,$csubnet_len);
                    $csubnet_family=substr($csubnet_data,0,4);
                    #IPv4
                    if($csubnet_family=='0001'){
                        $csubnet_source=substr($csubnet_data,4,2);
                        $csubnet_scope=substr($csubnet_data,6,2);
                        $csubnet_ip=long2ip(hexdec(substr(substr($csubnet_data,8,$csubnet_len-8).'00000000',0,8)));
                        $addR->csubnet=['family'=>$csubnet_family,'source'=>$csubnet_source,'scope'=>$csubnet_scope,'ip'=>$csubnet_ip];
                    }
                }

            }
        }
    }else{
        $addR=null;
    }


    $returndata= json_encode(array('type' => $type, 'name' => "$realname", 'id'=>"$id", 'query'=>"$query",'traffic'=>$traffic,'addR'=>$addR));

        return $returndata;
    }
}