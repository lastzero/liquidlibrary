<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Rest
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
class Liquid_Rest_Controller extends Zend_Controller_Action {
    private $server;

    public static $defaultResponseFormat = 'xml';
    private $responseFormat;
    
    public function init() {
        $this->server = new Zend_Rest_Server();
        
        switch(self::$defaultResponseFormat) {
            case 'xml':
                $this->setResponseFormatXml();
                break;
            case 'json': 
                $this->setResponseFormatJson();
                break;
        }
    }
    
    public function setResponseFormatXml() {
        $this->server->returnResponse(false);
        $this->responseFormat = 'xml';
    }
    
    public function setResponseFormatJson() {
        $this->server->returnResponse(true);
        $this->responseFormat = 'json';
    }
    
    public function setupRestServer($modelName) {
        $this->server->setClass($modelName);
        
        switch($this->responseFormat) {
            case 'xml':
                $this->server->handle();
                break;
            case 'json': 
                header('Content-Type: application/json');
                echo Zend_Json::fromXml($this->server->handle());
                break;
            default: 
                throw new Exception('Unknown response format' . $this->responseFormat);
        }
        
    }
    
    public function preDispatch()
    {
        $this->_helper->viewRenderer->setNoRender();
    }
}
