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

require_once 'Liquid/Log.php';

class LiquidLogTest extends PHPUnit_Framework_TestCase {
    public function setUp () {
        Liquid_Log::reset();
    }
    
    public function tearDown () {
        Liquid_Log::reset();
    }
    
    public function testAddLogger () {
        $logger1 = new Liquid_Log_Files();
        
        Liquid_Log::addLogger($logger1);
        
        $this->assertEquals($logger1, Liquid_Log::getLogger('Liquid_Log_Files'));
        
        $logger2 = new Liquid_Log_Syslog();
        
        Liquid_Log::addLogger($logger2, 'foo 23');

        $this->assertEquals($logger2, Liquid_Log::getLogger('foo 23'));
    }
    
    public function testRemoveLogger () {
        $logger1 = new Liquid_Log_Files();
        
        Liquid_Log::addLogger($logger1);
        
        $this->assertEquals($logger1, Liquid_Log::getLogger('Liquid_Log_Files'));
        
        Liquid_Log::removeLogger('Liquid_Log_Files');
        
        $this->setExpectedException('Liquid_Log_Exception');
        
        Liquid_Log::getLogger('Liquid_Log_Files');
    }
    
    public function testClearLoggers () {
        $logger1 = new Liquid_Log_Files();
        
        Liquid_Log::addLogger($logger1);
        
        $this->assertEquals($logger1, Liquid_Log::getLogger('Liquid_Log_Files'));
        
        Liquid_Log::clearLoggers();
        
        $this->setExpectedException('Liquid_Log_Exception');
        
        Liquid_Log::getLogger('Liquid_Log_Files');
    }
    
    public function testSetLogLevel () {
        $this->assertEquals(Liquid_Log::DEBUG, Liquid_Log::getLogLevel());

        Liquid_Log::setLogLevel(Liquid_Log::EMERG);
        $this->assertEquals(Liquid_Log::EMERG, Liquid_Log::getLogLevel());
        
        Liquid_Log::setLogLevel(Liquid_Log::ALERT);
        $this->assertEquals(Liquid_Log::ALERT, Liquid_Log::getLogLevel());
        
        Liquid_Log::setLogLevel(Liquid_Log::CRIT);
        $this->assertEquals(Liquid_Log::CRIT, Liquid_Log::getLogLevel());
        
        Liquid_Log::setLogLevel(Liquid_Log::ERR);
        $this->assertEquals(Liquid_Log::ERR, Liquid_Log::getLogLevel());
        
        Liquid_Log::setLogLevel(Liquid_Log::WARN);
        $this->assertEquals(Liquid_Log::WARN, Liquid_Log::getLogLevel());

        Liquid_Log::setLogLevel(Liquid_Log::NOTICE);
        $this->assertEquals(Liquid_Log::NOTICE, Liquid_Log::getLogLevel());

        Liquid_Log::setLogLevel(Liquid_Log::INFO);
        $this->assertEquals(Liquid_Log::INFO, Liquid_Log::getLogLevel());

        Liquid_Log::setLogLevel(Liquid_Log::DEBUG);
        $this->assertEquals(Liquid_Log::DEBUG, Liquid_Log::getLogLevel());
    }
    
    public function testSetLogLevelException () {
        $this->setExpectedException('Liquid_Log_Exception');
        Liquid_Log::setLogLevel('foo');
    }
    
    public function testLog () {
        $path = dirname(__FILE__) . DIRECTORY_SEPARATOR. 'Log/_temp' . DIRECTORY_SEPARATOR;
        
        $logger = new Liquid_Log_Files();
        $logger->setDirectory($path);
        $logger->setChannel('wow', 'php');
        $logger->setLogLevel(Liquid_Log::ERR);
        
        Liquid_Log::addLogger($logger);
        
        Liquid_Log::setLogLevel(Liquid_Log::INFO);
        
        $testString = 'Hello World 1234567890-=[]{}\"\';/.,<>?!@#$%^&*()_+';        
        
        Liquid_Log::log($testString, Liquid_Log::NOTICE, 'wow');
        
        $filename = $path . 'php' . Liquid_Log_Files::FILE_EXTENSION;
        
        $this->assertFileExists($filename);
        
        $contents = file_get_contents($filename);
        $this->assertContains($testString, $contents);
        $this->assertContains('NOTICE', $contents);        
        
        unlink($filename);
    }
    
    public function testCallerLogging () {
        $path = dirname(__FILE__) . DIRECTORY_SEPARATOR. 'Log/_temp' . DIRECTORY_SEPARATOR;
        
        $logger = new Liquid_Log_Files();
        $logger->setDirectory($path);
        $logger->setChannel('wow', 'php');
        
        Liquid_Log::addLogger($logger);
        
        Liquid_Log::enableCallerLogging();
        
        $testString = 'Hello World 1234567890-=[]{}\"\';/.,<>?!@#$%^&*()_+';        
        
        Liquid_Log::log($testString, Liquid_Log::EMERG, 'wow');
        
        $filename = $path . 'php' . Liquid_Log_Files::FILE_EXTENSION;
        
        $this->assertFileExists($filename);
        
        $contents = file_get_contents($filename);
        $this->assertContains($testString, $contents);
        $this->assertContains('EMERG', $contents);        
        $this->assertContains('LiquidLogTest', $contents);     
        $this->assertContains('testCallerLogging', $contents);     

        unlink($filename);
    }   
}
