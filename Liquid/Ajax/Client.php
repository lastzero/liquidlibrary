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
 
require_once 'Liquid/Ajax/Client/Exception.php';
 
class Liquid_Ajax_Client {
    private $url;    
    protected $fixturePath = false;

    public function __construct ($url) {
        $this->url = $url;
    }
    
    public function getUrl () {
        if(empty($this->url)) {
            throw new Liquid_Ajax_Client_Exception('URL is empty');
        }
        
        return $this->url;
    }
    
    public function useFixtures ($fixturePath) {
        $this->fixturePath = Liquid_Fixture::normalizePath($fixturePath);
    }
    
    public function __call ($method, $args) {
        $result = $this->_callServer($method, $args);
        
        if(!empty($result['error'])) {        
            $this->_handleError($result['error']);
        }

        return $result['result'];
    }
    
    protected function _handleError ($error) {
        if(isset($error['data']) && isset($error['data']['class'])) {
            $class = $error['data']['class'];
            throw new $class ($error['message'], (int) $error['code']);
        } else {
            throw new Exception($error['message'], (int) $error['code']);
        }
    }
    
    protected function _callServer ($method, $args) {
        $recordResult = false;
        
        if($this->fixturePath) {
            $fixture = new Liquid_Fixture($this->fixturePath.Liquid_Fixture::getFilename($method, $this->url . serialize($args)));
            
            try {                
                $result = $fixture->getData();
                return $result;
            } catch (Liquid_Fixture_Exception $e) {
                $recordResult = true;
            }
        }
        
        $data = Zend_Json::encode(
            array(
                'method' => $method,
                'params' => (array) $args,
                'id' => '1:1:' . time()
            )
        );
        
        if(function_exists('http_post_data')) {
            // Use HTTP ext (PECL)
            $message = http_parse_message(http_post_data($this->getUrl(), $data));
            
            if($message->responseCode >= 400) {
                throw new Liquid_Ajax_Client_Exception(
                    'The JSON-RPC server rejected our request (URL: "' . $this->getUrl() 
                    . '", Response Code: "' . $message->responseCode .'")');
            }
            
            $response = $message->body;
        } else {
            // Use Curl
            $handle = curl_init();

            curl_setopt($handle, CURLOPT_URL, $this->getUrl());
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

            $response = curl_exec($handle);

            curl_close($handle);
        }

        if(empty($response)) {
            throw new Liquid_Ajax_Client_Exception('JSON-RPC server response was empty (URL: "' . $this->getUrl() . '")');
        }
        
        $result = Zend_Json::decode($response);
        
        if(empty($result)) {
            throw new Liquid_Ajax_Client_Exception('JSON-RPC server response contained no valid JSON (URL: "' . $this->getUrl() . '")');
        }

        if($recordResult) { 
            $fixture->setData($result);
        }
        
        return $result;
    }  
}
