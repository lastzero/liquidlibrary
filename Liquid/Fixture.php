<?php 
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Fixture
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */

class Liquid_Fixture {
    protected $filename;

    public function __construct ($filename) {
        if(empty($filename)) {
            throw new Liquid_Fixture_Exception('Empty filename');
        }
        
        $this->filename = $filename;
    }
    
    public function getData () {
        if(!file_exists($this->filename)) {
            throw new Liquid_Fixture_Exception('File not found: ' . $this->filename);            
        }
        
        return unserialize(file_get_contents($this->filename));
    }
    
    public function setData ($data) {
        file_put_contents($this->filename, serialize($data));     
    }
    
    public static function filterAlphanumeric ($string) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $string);
    }
    
    public static function normalizePath ($directory) {
        $result = realpath($directory) ;
        
        if(empty($result)) {
            throw new Liquid_Fixture_Exception('Invalid directory: ' . $directory);
        }
         
        return $result . DIRECTORY_SEPARATOR;
    }
    
    public static function getFilename ($name, $arguments = false) {
        if(!$arguments) {
            $filename = self::filterAlphanumeric($name);
        } else {
            $fingerprint = self::filterAlphanumeric(strtr(print_r($arguments, true), array('=' => '_', 'Array' => 'array_')));
            
            if(strlen($fingerprint) > 40 ) {
                $fingerprint = md5(print_r($arguments, true));
            }
            
            $filename = self::filterAlphanumeric($name) . '.' . $fingerprint;
        }               
        
        return $filename . '.fix';
    }
}
