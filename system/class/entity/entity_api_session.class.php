<?php
// Class Object
// Name: entity_api
// Description: api account table, which stores all api user related information

class entity_api_session extends entity
{
    function generate_api_session_id($parameter = array())
    {
        $crc32b = hash('crc32b',2000-$parameter['account_id']);
        $set_count = strlen($crc32b);
        $random_hash = substr(sha1(openssl_random_pseudo_bytes(20)),-$set_count*4);

        $api_session_id_part = [];
        for($i=0;$i<$set_count;$i++)
        {
            $sub_hash = substr($random_hash,$i*4,4);
            $seq = (ord(substr($sub_hash,0,1)) + 1) % 3 + 1;
            $sub_hash = substr_replace($sub_hash,substr($crc32b,$i,1),$seq,1);
            $api_session_id_part[] = $sub_hash;
        }
        $api_session_id = implode('-',$api_session_id_part);
        $set_parameter = [
            'row'=>[['account_id'=>$parameter['account_id'],'name'=>$api_session_id,'expire_time'=>$parameter['expire_time'],'remote_addr'=>$parameter['remote_addr'],'http_user_agent'=>$parameter['http_user_agent']]],
            'table_fields'=>['account_id','name','expire_time','remote_addr','http_user_agent']
        ];
//print_r($parameter);
        $this->set($set_parameter);
        if (empty($this->row))
        {
            // TODO: Error Handling, Failed to generate session
            $this->message->error = 'Failed to generate session';
            return false;
        }
        return end($this->row);
    }

    function validate_api_session_id(&$parameter = array())
    {
        $key_part = explode('-',$parameter['api_session_id']);
        $crc32b_dec = '';
        foreach($key_part as $index=>$sub_hash)
        {
            $crc32b_dec .= substr($sub_hash,(ord(substr($sub_hash,0,1)) + 1) % 3 + 1,1);
        }
        $get_parameter = array(
            'bind_param' => array(':name'=>$parameter['api_session_id']),
            'where' => array('`name` = :name AND `expire_time` > NOW()')
        );
        $row = $this->get($get_parameter);
        if (empty($row))
        {
            // TODO: Error Handling, invalid api key
            $parameter['status'] = 'REQUEST_DENIED';
            $parameter['message'] = 'Please login again';
            $this->message->notice = 'Invalid session id '.$parameter['api_session_id'].'.';
            return false;
        }
        else return end($row);
    }
}

?>