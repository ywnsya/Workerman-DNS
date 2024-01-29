<?php
$data='a7df00100001000000000001013103646e730457434c4d0264650000010001000029057800008000000b00080007000118007541b3';
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
$name=join(".",$domain);
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
$query=substr($data,24,$startbyte-20);