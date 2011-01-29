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
 
require_once 'tests/_config.php';

require_once 'Liquid/Service/Debug.php';

class LiquidServiceDebugTest extends PHPUnit_Framework_TestCase {
    public function testEchoRequest () {
        $obj = new Liquid_Service_Debug();      
        $value = 'foo,bar -- baz';
        $this->assertEquals($value, $obj->echoRequest($value));
    }
}
