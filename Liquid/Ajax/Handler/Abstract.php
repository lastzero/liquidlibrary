<?php

/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Ajax
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
require_once 'Liquid/Ajax/Handler/Exception.php';
 
abstract class Liquid_Ajax_Handler_Abstract {
    private $secret = '';
    protected $errorCode;
    protected $errorString;
    protected $connected = false;
    
    const RESPONSE_ERROR        = 1;
    const RESPONSE_SUCCESS      = 2;
    const RESPONSE_ARRAY        = 3;
    
    abstract public function send($channel, $body, $retry = true);
    
    abstract public function connect();
    abstract public function disconnect();
    abstract public function sendHttpHeader();
    abstract public function sendAggregatedResponse($response);
    
    public function getName () {
        return strtolower(substr(strrchr(get_class($this), '_'), 1));
    }
    
    public function reconnect () {
        $this->disconnect();
        $this->connect();
        return;
    }
         
    public function isConnected () {
        return $this->connected;
    }
    
    public function getErrorCode() {
        return $this->errorCode;
    }
    
    
    public function getErrorString() {
        return $this->errorString;
    }        
    
    public function setSecret($secret) {
        $this->secret = $secret;
    }
    
    public function getSecret() {
        if(empty($this->secret)) {
            throw new Liquid_Ajax_Handler_Exception('No Ajax secret set');
        }
        
        return $this->secret;
    }

    public function getRequest($method = null, Array $params = null, $id = null) {
        if($method !== null) {
            return Zend_Json::encode(
                array(
                    'method' => $method, 
                    'params' => $params, 
                    'id' => $id
                )
            );
        } else {
            return Zend_Json::decode(file_get_contents('php://input'));
        }
    }
    
    public function sendError ($message = null, $code = 0, $data = null, $id = null) {
        $channel = $this->getErrorChannel($id);
        
        if($channel) {
            return $this->send($channel, $this->getErrorResponse($message, $code, $data, $id));
        }
    }        

    protected function getErrorChannel ($id = false) {
        $parts = explode(':', $id);
        
        if(count($parts) != 3 || empty($parts[1])) {
            return false;
        } elseif(is_numeric($parts[1])) {
            return md5($this->getSecret() . (int) $parts[2]);
        } else {
            return $this->getSecret();
        }
    }
     
    protected function getErrorResponse($message = null, $code = 0, $data = null, $id = null) {
        $response = array(
            'result' => null,
            'error' => array(
                'code'    => $code,
                'message' => $message,
                'data'    => $data,
            ),
            'id' => $id,
        );
        
        return $response;
    }
    
    public function sendSuccess ($result = null, $id = null) {
        $channel = $this->getSuccessChannel($id);
        
        if($channel) {
            return $this->send($channel, $this->getSuccessResponse($result, $id));
        }
    }    
    
    protected function getSuccessChannel ($id = false) {
        $parts = explode(':', $id);
        
        if(count($parts) != 3 || empty($parts[0])) {
            return false;
        } elseif(is_numeric($parts[0])) {
            return md5($this->getSecret() . (int) $parts[2]);
        } else {
            return $this->getSecret();
        }
    }   
    
    protected function getSuccessResponse($result = null, $id = null) {    
        $response = array(
            'result' => $result,
            'id'     => $id,
            'error'  => null
        );

        return $response;
    }
}
