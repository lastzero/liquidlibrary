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

class Liquid_Log {
    private static $logger = array();
    private static $disabled = false;    
    private static $logLevel = self::DEBUG;
    private static $callerLogging = false;
    
    const DEFAULT_CHANNEL = 'main';
    
    const EMERG   = 0;  // Emergency: system is unusable
    const ALERT   = 1;  // Alert: action must be taken immediately
    const CRIT    = 2;  // Critical: critical conditions
    const ERR     = 3;  // Error: error conditions
    const WARN    = 4;  // Warning: warning conditions
    const NOTICE  = 5;  // Notice: normal but significant condition
    const INFO    = 6;  // Informational: informational messages
    const DEBUG   = 7;  // Debug: debug messages
    
    static protected $priorityLut = array ('EMERG','ALERT','CRIT','ERR','WARN','NOTICE','INFO','DEBUG');

    public static function reset () {
        self::clearLoggers();
        self::$logLevel = self::DEBUG;
        self::$disabled = false;
    }

    public static function addLogger (Liquid_Log_Abstract $logger, $name = false) {
        if(!$name) {
            $name = get_class($logger);
        }
        
        self::$logger[$name] = $logger;
    }

    public static function getLogger ($name) {
        if(!isset(self::$logger[$name])) {
            throw new Liquid_Log_Exception('Logger ' . $name . ' not found');
        }
        
        return self::$logger[$name];
    }
    
    public static function removeLogger ($name) {
        if(!isset(self::$logger[$name])) {
            throw new Liquid_Log_Exception('Logger ' . $name . ' not found');
        }

        unset(self::$logger[$name]);
    }
    
    public static function clearLoggers () {
        foreach(self::$logger as $name => $logger) {
            self::removeLogger($name);
        }
        
        self::$logger = array();
    }
    
    public static function isEnabled () {
        return (self::$disabled == false);
    }
    
    public static function isDisabled () {
        return (self::$disabled == true);
    }

    public static function enable () {
        self::$disabled = false;
        
        foreach(self::$logger as $logger) {
            $logger->enable();
        }
    }
    
    public static function disable () {
        self::$disabled = true;   
        
        foreach(self::$logger as $logger) {
            $logger->disable();
        } 
    }
    
    public static function setLogLevel ($level) {
        if(!is_int($level)) {
            throw new Liquid_Log_Exception('Log level must be an integer, ' . gettype($level) . ' given');
        }
        
        self::$logLevel = $level;
        
        foreach(self::$logger as $logger) {
            $logger->setLogLevel($level);
        }
    }
    
    public static function getLogLevel () {
        return self::$logLevel;
    }

    public static function enableCallerLogging () {
        self::$callerLogging = true;
    }

    public static function disableCallerLogging () {
        self::$callerLogging = false;
    }
    
    protected static function getCallerIdentity () {
        $result = 'Unknown';
        
        $trace = @debug_backtrace(false);

        if(is_array($trace)) {
            foreach($trace as $caller) {
                if(isset($caller['function']) && (!isset($caller['class']) || $caller['class'] != 'Liquid_Log')) {
                    $result = $caller['function'];
                    
                    if(isset($caller['class']) && $caller['class'] != '') {
                        $result = $caller['class'] . '::' . $result;
                    }
                    
                    return $result;
                }
            }
        }
        
        return $result;
    } 

    public static function log ($message, $priority = self::DEBUG, $channel = self::DEFAULT_CHANNEL) {
        if(self::$disabled || !is_int($priority) || self::$logLevel < $priority) {
            return false;
        }
        
        if(self::$callerLogging) {            
            $caller = self::getCallerIdentity();

            $message = "[$caller] " . $message;
        }
        
        foreach(self::$logger as $logger) {
            $logger->log($message, $priority, $channel);
        }
    }    

    public static function emergency ($message, $channel = self::DEFAULT_CHANNEL) {
        self::log($message, self::EMERG, $channel);
    }
    
    public static function alert ($message, $channel = self::DEFAULT_CHANNEL) {
        self::log($message, self::ALERT, $channel);
    }

    public static function critical ($message, $channel = self::DEFAULT_CHANNEL) {
        self::log($message, self::CRIT, $channel);
    }

    public static function error ($message, $channel = self::DEFAULT_CHANNEL) {
        self::log($message, self::ERR, $channel);
    }

    public static function warning ($message, $channel = self::DEFAULT_CHANNEL) {
        self::log($message, self::WARN, $channel);
    }
    
    public static function notice ($message, $channel = self::DEFAULT_CHANNEL) {
        self::log($message, self::NOTICE, $channel);
    }
    
    public static function info ($message, $channel = self::DEFAULT_CHANNEL) {
        self::log($message, self::INFO, $channel);
    }
    
    public static function debug ($message, $channel = self::DEFAULT_CHANNEL) {
        self::log($message, self::DEBUG, $channel);
    }
}
