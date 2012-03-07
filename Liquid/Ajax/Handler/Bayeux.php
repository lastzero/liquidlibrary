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
 * @copyright  Copyright (c) 2012 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
require_once 'Liquid/Ajax/Handler/Abstract.php';

class Liquid_Ajax_Handler_Bayeux extends Liquid_Ajax_Handler_Abstract {   
    // PRIVATE VARIABLES
    private $bayeux;
    private $url;
    private $user;
    private $password;    
    
    // CONSTRUCTOR
    function __construct($url, $user = '', $password = '')
    {   
        $this->url          = $url;
        $this->user         = $user;
        $this->password     = $password;
    }    
    
    // PUBLIC FUNCTIONS
    public function convertChannelName ($channel) {
        return '/' . strtr($channel, '.', '/');        
    }
    
    public function connect()
    {        
        $this->bayeux = new Liquid_Ajax_Adapter_Bayeux($this->url);
        $this->connected = true;
    }
    
    public function disconnect()
    {
        unset($this->bayeux);
        $this->connected = false;
    }        
    
    public function send($channel, $body, $retry = true)
    {        
        try { 
            $this->bayeux->publish($this->convertChannelName($channel), $body);
        } catch (Exception $e) {
            if($retry) {
                return $this->send($channel, $body, false);
            }
            
            $this->error_code = $e->getCode();
            $this->error_string = $e->getMessage();
            
            throw new Liquid_Ajax_Handler_Exception('Send Failed: ' . $this->get_error_string(), $this->get_error_code());
        }
    }
    
    public function sendHttpHeader () {
        /* ignore_user_abort(true);
        header("Connection: close");
        header("Content-Encoding: none");
        header("Content-Length: 0");
        flush(); */
    }      

    public function sendAggregatedResponse ($response) {
        // Nothing to do
    }
}
