<?php
// Class Object
// Name: entity_api
// Description: api account table, which stores all api user related information

class entity_api_key extends entity
{
    function generate_api_key($account_id)
    {
        $random_hash = substr(sha1(openssl_random_pseudo_bytes(20)),-32);
        $crc32b = hash('crc32b',2000-$account_id);

        $api_key_part = [];
        for($i=0;$i<8;$i++)
        {
            $sub_hash = substr($random_hash,$i*4,4);
            $seq = ord(substr($sub_hash,0,1)) % 3 + 1;
            $sub_hash = substr_replace($sub_hash,substr($crc32b,$i,1),$seq,1);
            $api_key_part[] = $sub_hash;
        }
        $api_key = implode('-',$api_key_part);
        $parameter = [
            'row'=>[['account_id'=>$account_id,'name'=>$api_key]],
            'table_fields'=>['account_id','name']
        ];
print_r($parameter);
        $this->set($parameter);
    }

    function validate_api_key($api_key)
    {
        $key_part = explode('-',$api_key);
        $crc32b_dec = '';
        foreach($key_part as $index=>$sub_hash)
        {
            $crc32b_dec .= substr($sub_hash,ord(substr($sub_hash,0,1)) % 3 + 1,1);
        }
        $parameter = array(
            'bind_param' => array(':name'=>$api_key),
            'where' => array('`name` = :name')
        );
        $row = $this->get($parameter);
        if (empty($row))
        {
            // TODO: Error, type invalid api key
        }
        foreach($row as $row_id=>$record)
        {
            if (hash('crc32b',2000-$record['account_id']) == $crc32b_dec)
            {
                $ip_restriction = explode(',',$record['ip_restriction']);
                if (in_array($_SERVER['remote_ip'], $ip_restriction))
                {
                    return $record['account_id'];
                }
                else
                {
                    // TODO: Error, requested ip not accepted
                }
            }
            else
            {
                // TODO: Error, type invalid api key, key is not generated through genuine method
            }
        }
        return false;
    }
}

?>