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
 
require_once 'tests/_config.php';

require_once 'Liquid/Ajax/Client.php';

class LiquidAjaxClientTest extends PHPUnit_Framework_TestCase {
    public function testGetUrl  () {
        $url = 'http://chaos.local/ajax/debug';
        $client = new Liquid_Ajax_Client ($url);
        $this->assertEquals($url, $client->getUrl());
    }
    
    public function testEchoRequest  () {
        $url = 'http://chaos.local/ajax/debug';
        $client = new Liquid_Ajax_Client ($url);

        $client->useFixtures(dirname(__FILE__) . '/_fixtures');
        
        $result = $client->echoRequest('foo/bar/baz');

        $this->assertEquals('foo/bar/baz', $result);
    }
    
    public function testStockquotes  () {
        $url = 'http://chaos.local/ajax/stockquotes';
        $client = new Liquid_Ajax_Client ($url);

        $client->useFixtures(dirname(__FILE__) . '/_fixtures');
        
        $result = $client->getQuote('IBM');
                
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $result);
        $this->assertArrayHasKey('IBM', $result);
    }
    
    public function testExceptions  () {        
        $url = 'http://chaos.local/ajax/debug';
        $client = new Liquid_Ajax_Client ($url);

        $client->useFixtures(dirname(__FILE__) . '/_fixtures');
        
        $this->setExpectedException('BadMethodCallException');
        $result = $client->getBadMethodCallException('Test');
    }
}
