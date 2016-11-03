<?php
// Class Object
// Name: entity_api
// Description: api account table, which stores all api user related information

class entity_api_session extends entity
{
    function generate_api_session_id($account_id)
    {
        $crc32b = hash('crc32b',2000-$account_id);
        $set_count = strlen($crc32b);
        $random_hash = substr(sha1(openssl_random_pseudo_bytes(20)),-$set_count*4);

        $api_session_id_part = [];
        for($i=0;$i<$set_count;$i++)
        {
            $sub_hash = substr($random_hash,$i*4,4);
            $seq = ord(substr($sub_hash,0,1)) % 3 + 1;
            $sub_hash = substr_replace($sub_hash,substr($crc32b,$i,1),$seq,1);
            $api_session_id_part[] = $sub_hash;
        }
        $api_session_id = implode('-',$api_session_id_part);
        $parameter = [
            'row'=>[['account_id'=>$account_id,'name'=>$api_session_id]],
            'table_fields'=>['account_id','name']
        ];
//print_r($parameter);
        $this->set($parameter);
    }

    function validate_api_session_id(&$parameter = array())
    {
        $key_part = explode('-',$parameter['api_session_id']);
        $crc32b_dec = '';
        foreach($key_part as $index=>$sub_hash)
        {
            $crc32b_dec .= substr($sub_hash,ord(substr($sub_hash,0,1)) % 3 + 1,1);
        }
        $get_parameter = array(
            'bind_param' => array(':name'=>$parameter['api_session_id']),
            'where' => array('`name` = :name')
        );
        $row = $this->get($get_parameter);
        if (empty($row))
        {
            // TODO: Error, type invalid api key
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Invalid api key';
            return false;
        }
        foreach($row as $row_id=>$record)
        {
            if (hash('crc32b',2000-$record['account_id']) == $crc32b_dec)
            {
                $ip_restriction = explode(',',$record['ip_restriction']);
                if (in_array($parameter['remote_ip'], $ip_restriction))
                {
                    $parameter['status'] = 'OK';
                    $parameter['message'] = NULL;
                    return $record['account_id'];
                }
                else
                {
                    // TODO: Error, requested ip not accepted
                    $parameter['status'] = 'REQUEST_DENIED';
                    $parameter['message'] = 'Invalid ip address ['.$parameter['remote_ip'].']';
                }
            }
            else
            {
                // TODO: Error, type invalid api key, key is not generated through genuine method
                $parameter['status'] = 'INVALID_REQUEST';
                $parameter['message'] = 'Invalid api key, key is not genuine';
            }
        }
        return false;
    }
}

?>