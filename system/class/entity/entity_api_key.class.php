<?php
// Class Object
// Name: entity_api
// Description: api account table, which stores all api user related information

class entity_api_key extends entity
{
    function get($parameter = array())
    {
        $row = parent::get($parameter);
        $result_row = array();
        if (is_array($row))
        {
            foreach($row as $record_index=>&$record)
            {
                $result_record = array();
                $result_record['name'] = $record['name'];
                $result_record['alternate_name'] = $record['alternate_name'];
                $result_record['ip_restriction'] = array();
                if (!empty($record['ip_restriction'])) $result_record['ip_restriction'] = explode(',',$record['ip_restriction']);
                $result_row[] = $result_record;
            }
        }
        return $result_row;
    }

    function get_api_key($account_id)
    {
        $get_parameter = array(
            'bind_param' => array(':account_id'=>$account_id),
            'where' => array('`account_id` = :account_id')
        );
        return $this->get($get_parameter);
    }

    function generate_api_key($account_id)
    {
        $crc32b = hash('crc32b',2000-$account_id);
        $set_count = strlen($crc32b);
        $random_hash = substr(sha1(openssl_random_pseudo_bytes(20)),-$set_count*4);

        $api_key_part = [];
        for($i=0;$i<$set_count;$i++)
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
//print_r($parameter);
        if ($this->set($parameter) === false) return false;

        return $api_key;
    }

    function update_api_key($api_key)
    {

    }

    function validate_api_key(&$parameter = array())
    {
        $key_part = explode('-',$parameter['api_key']);
        $crc32b_dec = '';
        foreach($key_part as $index=>$sub_hash)
        {
            $crc32b_dec .= substr($sub_hash,ord(substr($sub_hash,0,1)) % 3 + 1,1);
        }
        $get_parameter = array(
            'bind_param' => array(':name'=>$parameter['api_key']),
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
//$parameter['status'] = 'OK';
//return $record['account_id'];

                $ip_restriction = explode(',',$record['ip_restriction']);
                foreach ($ip_restriction as $ip_index=>$ip_pattern)
                {
                    $ip_pattern = str_replace('.','\.',$ip_pattern);
                    $ip_pattern = str_replace('*','(\d*)',$ip_pattern);
                    $ip_pattern = '/'.$ip_pattern.'/';
print_r([$ip_pattern,$parameter['remote_ip']]);
                    if (preg_match($ip_pattern,$parameter['remote_ip']))
                    {
print_r('<br>pattern matched<br>');
                        $parameter['status'] = 'OK';
                        $parameter['message'] = NULL;
                        return $record['account_id'];
                    }
                }
                // TODO: Error, requested ip not accepted
                $parameter['status'] = 'REQUEST_DENIED';
                $parameter['message'] = 'Invalid ip address ['.$parameter['remote_ip'].']';
                return false;
            }
            else
            {
                // TODO: Error, type invalid api key, key is not generated through genuine method
                $parameter['status'] = 'INVALID_REQUEST';
                $parameter['message'] = 'Invalid api key, key is not genuine';
                return false;
            }
        }
    }
}

?>