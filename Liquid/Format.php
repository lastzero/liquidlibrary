<?php 
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Format
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
require_once 'Zend/Date.php';
require_once 'Zend/Locale.php';
require_once 'Zend/Locale/Format.php';

class Liquid_Format {
    const NONE = 1;
    const INT = 2;
    const FLOAT = 3;
    const STRING = 4;
    const ALPHANUMERIC = 5;
    const SERIALIZE = 6;
    const JSON = 7;
    
    const DATE = 'YYYY-MM-dd';        
    const DATETIME = 'YYYY-MM-dd HH:mm:ss';
    
    protected static $locale = null;
    
    public static function setLocale (Zend_Locale $locale = null) {
        self::$locale = $locale;
    }
    
    public static function getLocale () {
        if(self::$locale) {
            return self::$locale;
        } elseif (Zend_Registry::isRegistered('Zend_Locale')) {
            return Zend_Registry::get('Zend_Locale');
        }
        
        return new Zend_Locale();
    }
    
    public static function convert ($format, $data = null) {
        if($data === null) {
            return null;
        }
        
        switch ($format) {
            case self::NONE:
                return $data;
            case self::DATE:
                $date = new Zend_Date($data, self::DATE);
                return $date->toString(Zend_Date::DATE_MEDIUM);
            case self::DATETIME: 
                $date = new Zend_Date($data, self::DATETIME);
                return $date->toString(Zend_Date::DATETIME_MEDIUM);               
            case self::INT:  
                return (integer) $data;          
            case self::FLOAT:
                return (double) $data;
            case self::STRING:                
                return (string) $data;                
            case self::ALPHANUMERIC:
                return preg_replace('/[^a-zA-Z0-9_ ]/', '', $data);
            case self::SERIALIZE:
                return unserialize($data);
            case self::JSON:
                return Zend_Json::decode($data);
            default:
                try {
                    $result = Zend_Locale_Format::toNumber(
                        $data, 
                        array(
                            'number_format' => $format,
                            'locale' => self::getLocale()
                            )
                    );
                } catch (Exception $e) {
                    $result = null;
                }
                
                return $result;
        }
    }
    
    public static function sql ($format, $data = null) {
        if($data === null) {
            return null;
        }
                
        switch ($format) {
            case self::NONE:  
                return $data;      
            case self::DATE:
                if(empty($data)) {
                    $date = null;                    
                } elseif(!is_object($data)) {
                    $zend = new Zend_Date($data);
                    $date = $zend->get(self::DATE);
                } elseif($data instanceof Zend_Date) {
                    $date = $data->get(self::DATE);
                } elseif($data instanceof DateTime) {
                    $date = $data->format('Y-m-d');
                } else {
                    throw new Liquid_Format_Exception('Unknown date object: ' . get_class($data));
                }
                
                return $date;      
            case self::DATETIME:
                if(empty($data)) {
                    $datetime = null;                    
                } elseif(!is_object($data)) {
                    $zend = new Zend_Date($data);
                    $datetime = $zend->get(self::DATETIME);
                } elseif($data instanceof Zend_Date) {
                    $datetime = $data->get(self::DATETIME);
                } elseif($data instanceof DateTime) {
                    $datetime = $data->format('Y-m-d H:i:s');
                } else {
                    throw new Liquid_Format_Exception('Unknown date/time object: ' . get_class($data));
                }
                
                return $datetime;    
            case self::INT:
                return (integer) $data;          
            case self::STRING:                
                return (string) $data;                
            case self::ALPHANUMERIC:
                return preg_replace('/[^a-zA-Z0-9_ ]/', '', $data);
            case self::SERIALIZE:
                return serialize($data);
            case self::JSON:
                return Zend_Json::encode($data);
            default:
                if(!is_numeric($data)) {                    
                    $params = array('locale' => self::getLocale());
                    
                    try {
                        $data = Zend_Locale_Format::getNumber($data, $params);
                    } catch (Exception $e) {
                        $data = 0;
                    }
                }
                
                return (double) $data;
       }
    }
}
