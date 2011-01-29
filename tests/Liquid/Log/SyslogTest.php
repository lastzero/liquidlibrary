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
 
require_once 'tests/_config.php';

require_once 'Liquid/Log/Syslog.php';

class LiquidLogSyslogTest extends PHPUnit_Framework_TestCase {
    public function setUp () {
        $this->logger = new Liquid_Log_Syslog();
    }
    
    public function testSetLogName () {
        $this->logger->setLogName('random');
        $this->assertEquals('random', $this->logger->getLogName());
    }
    
    public function testGetLogName () {
        $this->setExpectedException('Liquid_Log_Exception');
        $this->logger->getLogName();
    }
    
    public function testSetLogLevel () {
        $this->assertEquals(Liquid_Log::DEBUG, $this->logger->getLogLevel());

        $this->logger->setLogLevel(Liquid_Log::EMERG);
        $this->assertEquals(Liquid_Log::EMERG, $this->logger->getLogLevel());
        
        $this->logger->setLogLevel(Liquid_Log::ALERT);
        $this->assertEquals(Liquid_Log::ALERT, $this->logger->getLogLevel());
        
        $this->logger->setLogLevel(Liquid_Log::CRIT);
        $this->assertEquals(Liquid_Log::CRIT, $this->logger->getLogLevel());
        
        $this->logger->setLogLevel(Liquid_Log::ERR);
        $this->assertEquals(Liquid_Log::ERR, $this->logger->getLogLevel());
        
        $this->logger->setLogLevel(Liquid_Log::WARN);
        $this->assertEquals(Liquid_Log::WARN, $this->logger->getLogLevel());

        $this->logger->setLogLevel(Liquid_Log::NOTICE);
        $this->assertEquals(Liquid_Log::NOTICE, $this->logger->getLogLevel());

        $this->logger->setLogLevel(Liquid_Log::INFO);
        $this->assertEquals(Liquid_Log::INFO, $this->logger->getLogLevel());

        $this->logger->setLogLevel(Liquid_Log::DEBUG);
        $this->assertEquals(Liquid_Log::DEBUG, $this->logger->getLogLevel());
    }
    
    public function testEnable () {
        $this->setExpectedException('Liquid_Log_Exception');
        $this->logger->enable();
    }

    public function testDisable () {
        $this->logger->disable();
    }
    
    public function testLogException () {
        $this->setExpectedException('Liquid_Log_Exception');
        $this->logger->enable();
        $this->logger->setLogName('test');    
        $this->logger->log('test');
    }

    public function testLog () {
        $this->logger->setLogName('test');    
        $this->logger->enable();
        $this->logger->log('test');
    }
}
