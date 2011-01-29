<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Service
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
class Liquid_Service_Debug {
    public function echoRequest ($request) {
        return $request;
    }
    
    public function memoryUsage () {
        return round(memory_get_usage() / 1024) . ' KB';
    }
    
    public function getBadMethodCallException ($message) {
        throw new BadMethodCallException($message);
    }
}
