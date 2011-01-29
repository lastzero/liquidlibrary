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

require_once 'Liquid/Service/StockQuotes.php';

class LiquidServiceStockQuotesTest extends PHPUnit_Framework_TestCase {
    public function testGetQuote () {
        $obj = new Liquid_Service_StockQuotes();    
        
        $obj->useFixtures(dirname(__FILE__) . '/_fixtures/');
        
        $result1 = $obj->getQuote('DB');
        $result2 = $obj->getQuote('SAP.DE,IBM.DE');
        
        $this->assertNotEquals($result1, $result2);
        $this->assertEquals(1, count($result1));        
        $this->assertEquals(2, count($result2));
        $this->assertArrayHasKey('symbol', current($result1));
        $this->assertArrayHasKey('symbol', current($result2));
        $this->assertArrayHasKey('symbol', next($result2));
        $this->assertArrayHasKey('symbol', $result1['DB']);
        $this->assertArrayHasKey('current', $result1['DB']);
        $this->assertArrayHasKey('open', $result1['DB']);
        $this->assertArrayHasKey('close', $result1['DB']);
        $this->assertArrayHasKey('change', $result1['DB']);
        $this->assertArrayHasKey('volume', $result1['DB']);
        $this->assertArrayHasKey('name', $result1['DB']);
        $this->assertArrayHasKey('exchange', $result1['DB']);
        $this->assertArrayHasKey('high', $result1['DB']);
        $this->assertArrayHasKey('low', $result1['DB']);
        $this->assertArrayHasKey('eps', $result1['DB']);
        $this->assertArrayHasKey('52w_low', $result1['DB']);
        $this->assertArrayHasKey('52w_high', $result1['DB']);
        $this->assertArrayHasKey('date', $result1['DB']);
    }
}
