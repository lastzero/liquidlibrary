<?php
/**
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Db
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */

require_once 'Zend/Db/Adapter/Abstract.php';

class Liquid_Db {
    private static $db = false;

    // Static functions are there to handle MANY rows

    public static function setDefaultAdapter (Zend_Db_Adapter_Abstract $db) {
        self::$db = $db;
    }

    /**
     * @return Zend_Db_Adapter_Abstract
     */
    protected static function getAdapter () {
        if(self::$db) {
            return self::$db;
        }

        if(Zend_Registry::isRegistered('db')) {
            return Zend_Registry::get('db');
        }

        if(Zend_Registry::isRegistered('Zend_Db')) {
            return Zend_Registry::get('Zend_Db');
        }

        throw new Liquid_Db_Exception('Could not find database adapter');
    }

    public static function beginTransaction () {
        return self::getAdapter()->beginTransaction();
    }

    public static function commit () {
        return self::getAdapter()->commit();
    }

    public static function rollBack () {
        return self::getAdapter()->rollBack();
    }
}
