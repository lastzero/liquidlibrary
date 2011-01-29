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

class Liquid_Ajax_Handler_Ape extends Liquid_Ajax_Handler_Abstract {   
    // PRIVATE VARIABLES
    private $address;
    private $port;
    private $password;

    // CONSTRUCTOR
    function __construct($address, $port, $password)
    {
        $this->address      = $address;
        $this->port         = $port;
        $this->password     = $password;
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
        if(strlen($channel) > 40) {
            throw new Liquid_Ajax_Handler_Exception('Ape supports only up to 40 chars for channel names');
        }
    
        $server = 'http://' . $this->address . ':' . $this->port . '/?';        

        $cmd =  array(
                    array(
                        'cmd' => 'inlinepush',
                        'params' => array(
                            'password' => $this->password,
                            'raw' => $channel,
                            'channel' => $channel,
                            'data' => array( //Note: data can't be a string
                                'message' => $body
                            )
                        )
                    )
                );

        // Sending a lot of data might cause problems because of the GET request (depending on HTTP lib)
        $data = file_get_contents($server . rawurlencode(json_encode($cmd)));

        $data = Zend_Json::decode($data);

        if ($data[0]['data']['value'] == 'ok') {
            return $data;
        } else {
            if($retry) {
                return $this->send($channel, $body, false);
            }

            $this->errorCode = $data[0]['data']['code'];
            $this->errorString = $data[0]['data']['value'];
            
            throw new Liquid_Ajax_Handler_Exception('Send Failed: ' . $this->getErrorString(), $this->getErrorCode());
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
