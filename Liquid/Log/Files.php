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
 
class Liquid_Log_Files extends Liquid_Log_Abstract {
    const FILE_EXTENSION = '.log';    

    private $logger;
    
    private $disabled = false;    
    private $directory = false;
    
    private $channels = array(Liquid_Log::DEFAULT_CHANNEL => Liquid_Log::DEFAULT_CHANNEL);
    private $files = array();
            
    protected $priorityLut = array ('EMERG','ALERT','CRIT','ERR','WARN','NOTICE','INFO','DEBUG');
    
    public function enable () {
        $this->disabled = false;
    }
    
    public function disable () {
        $this->disabled = true;    
    }
    
    public function setDirectory ($directory) {
        if(strrpos($directory, DIRECTORY_SEPARATOR) !== (strlen($directory) - 1)) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        $this->directory = $directory;
    }
    
    public function getDirectory () {
        return $this->directory;
    }   

    public function setChannel ($name, $filename) {
        $this->channels[$name] = $filename;
    }
    
    public function getChannels () {
    	return $this->channels;
    }
    
    public function log ($message, $priority = Liquid_Log::DEBUG, $channel = Liquid_Log::DEFAULT_CHANNEL) {
        if($this->disabled || !$this->directory || !is_int($priority) || $this->logLevel < $priority) {
            return false;
        }
        
        if(!isset($this->channels[$channel])) {
            $channel = Liquid_Log::DEFAULT_CHANNEL;
        }
        
        if($this->channels[$channel] === false) {
            return false;
        }
        
        if(!isset($this->files[$this->channels[$channel]])) {
            $this->files[$this->channels[$channel]] = fopen(
                $this->directory . $this->channels[$channel] . self::FILE_EXTENSION, 
                'a'
            );
        }
        
        $priorityName = $this->priorityLut[$priority];

        fwrite($this->files[$this->channels[$channel]], date('c') . " $priorityName ($priority): " . $message . PHP_EOL);
        
        return true;
    }
}
