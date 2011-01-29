<?php 
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Log
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */

abstract class Liquid_Log_Abstract {
    protected $logLevel = Liquid_Log::DEBUG;
    
    abstract public function enable ();
    
    abstract public function disable ();
    
    abstract public function log ($message, $priority = Liquid_Log::DEBUG, $channel = Liquid_Log::DEFAULT_CHANNEL);

    public function setLogLevel ($level) {
        if(!is_int($level)) {
            throw new Liquid_Log_Exception('Log level must be an integer, ' . gettype($level) . ' given');
        }
        
        $this->logLevel = $level;
    }
    
    public function getLogLevel () {
        return $this->logLevel;
    }
}
