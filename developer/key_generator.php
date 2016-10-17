<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 14/10/2016
 * Time: 4:38 PM
 */

$hash_algos = hash_algos();
$execution_time = array();
$hash_result = array();
foreach($hash_algos as $index=>$hash_algo)
{
    $account_id = $_GET['id'];
    $start_time = microtime(1);
    $hash_result[$hash_algo] = hash($hash_algo,2000-$account_id);
    $execution_time[$hash_algo] = microtime(1) - $start_time;
}
echo '<pre>';
print_r($hash_result);
print_r($execution_time);

$random_hash = substr(sha1(openssl_random_pseudo_bytes(20)),-32);
$crc32b = hash('crc32b',2000-$account_id);
echo ('<br>'.$random_hash.'<br>'.$crc32b.'<br>');

$key_part = [];
for($i=0;$i<8;$i++)
{
    $sub_hash = substr($random_hash,$i*4,4);
    $seq = ord(substr($sub_hash,0,1)) % 3 + 1;
    $sub_hash = substr_replace($sub_hash,substr($crc32b,$i,1),$seq,1);
    $key_part[] = $sub_hash;
}
$key = implode('-',$key_part);
echo '<br><strong>Final Key</strong>:<br>'.$key;

// Key decoder
$key_part = explode('-',$key);
$crc32b_dec = '';
foreach($key_part as $index=>$sub_hash)
{
    $crc32b_dec .= substr($sub_hash,ord(substr($sub_hash,0,1)) % 3 + 1,1);
}
echo '<br><strong>Decoder CRC</strong>:<br>'.$crc32b_dec;