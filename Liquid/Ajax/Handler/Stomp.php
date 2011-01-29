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

class Liquid_Ajax_Handler_Stomp extends Liquid_Ajax_Handler_Abstract {   
    // PRIVATE VARIABLES
    private $stomp;
    private $address;
    private $port;
    private $user;
    private $password;    
    
    // CONSTRUCTOR
    function __construct($address, $port, $user = '', $password = '')
    {   
        $this->address      = $address;
        $this->port         = $port;
        $this->user         = $user;
        $this->password     = $password;
    }    
    
    // PUBLIC FUNCTIONS
    public function connect()
    {        
        $this->stomp = new Stomp('tcp://'.$this->address.':'.$this->port);
        $this->connected = true;
    }
    
    public function disconnect()
    {
        unset($this->stomp);
        $this->connected = false;
    }        
    
    public function send($channel, $body, $retry = true)
    {        
        try { 
            $this->stomp->send($channel, Zend_Json::encode($body));
        } catch (Exception $e) {
            if($retry) {
                return $this->event($channel, $body, false);
            }
            
            $this->error_code = $e->getCode();
            $this->error_string = $e->getMessage();
            
            throw new Liquid_Ajax_Handler_Exception('Send Failed: ' . $this->get_error_string(), $this->get_error_code());
        }
    }
    
    public function sendHttpHeader () {
        ignore_user_abort(true);
        header("Connection: close");
        header("Content-Encoding: none");
        header("Content-Length: 0");
        flush();
    }      

    public function sendAggregatedResponse ($response) {
        // Nothing to do
    }
}
