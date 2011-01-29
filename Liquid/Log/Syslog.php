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
 
require_once 'Liquid/Log/Abstract.php';

class Liquid_Log_Syslog extends Liquid_Log_Abstract {
    private $disabled = true;   
    private $logName = null;
    
    public function setLogName ($logName) {
        $this->logName = $logName;
    } 
    
    public function getLogName () {
        if($this->logName === null) {
            throw new Liquid_Log_Exception ('Log name is null');
        }
        
        return $this->logName;
    } 

    public function enable () {
        $this->disabled = false;
        openlog($this->getLogName(), LOG_ODELAY | LOG_PID, LOG_USER);
    }
    
    public function disable () {
        $this->disabled = true;
        closelog();
    }
    
    public function log ($message, $priority = Liquid_Log::DEBUG, $channel = Liquid_Log::DEFAULT_CHANNEL) {
        if($this->disabled) {
            return;
        }
        
        syslog($priority, $message);
    }
}
