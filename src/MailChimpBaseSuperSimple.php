<?php

/**
 * Super-simple, minimum abstraction MailChimp API v3
 *
 * Extended version of https://github.com/drewm/mailchimp-api
 *
 * @author Fermin Hernandez Gil <fermin.hdez@gmail.com>
 * @version 1.0.0
 */
class MailChimpBaseSuperSimple
{
    public $apikey = "YOUR-API-KEY";
    public $verifySsl = true;
    public $list_id;
    
    private $_apiEndpoint = 'https://<dc>.api.mailchimp.com/3.0';
    private $_errorCode = false;
    private $_errorMessage = '';

    /**
     * Create a new instance
     * @param string $api_key Your MailChimp API key
     */
    public function __construct()
    {
        $this->apikey = Yii::app()->mailchimp->apikey;

        list(, $datacentre) = explode('-', $this->apikey);
        $this->_apiEndpoint = str_replace('<dc>', $datacentre, $this->_apiEndpoint);
    }

    /**
     * Call an API method.
     * @param  string $method The API method to call, e.g. 'lists/list'
     * @param  array  $args   An array of arguments to pass to the method. Will be json-encoded for you.
     * @return array          Associative array of json decoded API response.
     */
    public function call($method, $args = array())
    {
        $this->_errorCode = false;
        $this->_errorMessage = '';
        
        $result = $this->makeRequest($method, $args);
        
        if (isset($result['status']) && $result['status'] == 'error') {
            $this->_errorCode = isset($result['code']) ? (int)$result['code'] : 0;
            $this->_errorMessage = isset($result['error']) ? (int)$result['error'] : '';
        }
        
        return $result;
    }

    private function makeRequest($method, $args = array())
    {
        $auth = base64_encode('user:'. $this->apikey);
        $json_data = json_encode($args);

        $ch = curl_init();
        $url = $url = $this->_apiEndpoint.'/'.$method;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
            'Authorization: Basic '. $auth));
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        $result = curl_exec($ch);

        return $result ? json_decode($result, true) : false;
    }

    public function getErrorCode()
    {
        return $this->_errorCode;
    }
    
    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }


    /**
     * Call to list members
     * http://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/
     */
    public function listMembers()
    {
        $result = $this->call("lists/{$this->list_id}/members");
        return $result;
    }

    /**
     * Call to list member and subresources methods
     * http://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/
     * http://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/goals/
     * http://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/activity/
     * http://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/notes/
     *
     * @param  [type] $email        Member email
     * @param  string $subresources Emtpy or name of subresource['', goals, activity, notes]
     */
    public function listMember($email, $subresources = '')
    {
        $subscriber_hash = md5($email);
        $url = "lists/{$this->list_id}/members/$subscriber_hash" . ((empty($subresources)) ? '' : "/$subresources");
        $result = $this->call($url);
        return $result;
    }
}
