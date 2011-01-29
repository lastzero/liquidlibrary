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

require_once 'Liquid/Ajax/Handler/Abstract.php';

class Liquid_Ajax_Handler_Json extends Liquid_Ajax_Handler_Abstract {    
    protected function getMessages () {
        if(!isset($_SESSION[__CLASS__]) || !isset($_SESSION[__CLASS__]['messages'])) {    
            return array();
        }
        
        return (array) $_SESSION[__CLASS__]['messages'];
    }
    
    protected function clearMessages () {
        $_SESSION[__CLASS__]['messages'] = array();
    }
    
    protected function addMessage ($channel, $body) {
        if(!isset($_SESSION[__CLASS__]) || !isset($_SESSION[__CLASS__]['messages'])) {    
            $_SESSION[__CLASS__]['messages'] = array();
        }
        
        $_SESSION[__CLASS__]['messages'][] = array(
            'channel' => $channel, 
            'body'    => $body
        );
    }
    
    // PUBLIC FUNCTIONS
    public function connect()
    {        
        $this->connected = true;
    }
    
    public function disconnect()
    {
        $this->connected = false;
    }        
    
    public function send($channel, $body, $retry = true)
    {
        $this->addMessage($channel, $body);
    }
    
    public function sendHttpHeader () {
        header("Content-Type: application/json");
    }
    
    public function sendAggregatedResponse ($jsonrpc) {
        $response = array(
            'messages' => $this->getMessages(),
            'aggregated'  => $jsonrpc
        );
        
        $this->clearMessages();
        
        echo Zend_Json::encode($response);
    }
    
    public function sendError ($message = null, $code = 0, $data = null, $id = null) {
        $parts = explode(':', $id);
        
        if(count($parts) == 3 && !empty($parts[1])) {
            echo Zend_Json::encode($this->getErrorResponse($message, $code, $data, $id));
        }
    }

    public function sendSuccess ($result = null, $id = null) {
        $parts = explode(':', $id);
        
        if(count($parts) == 3 && !empty($parts[0])) {
            echo Zend_Json::encode($this->getSuccessResponse($result, $id));
        }
    }    
}
