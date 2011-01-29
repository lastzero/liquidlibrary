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

require_once 'Liquid/Log/Files.php';

class LiquidLogFilesTest extends PHPUnit_Framework_TestCase {
    public function setUp () {
        $this->logger = new Liquid_Log_Files();
        $this->path = dirname(__FILE__) . DIRECTORY_SEPARATOR. '_temp';
    }
    
    public function testSetDirectoryWithoutDirectorySeparator () {
        $this->logger->setDirectory($this->path);
        $this->assertEquals($this->path . DIRECTORY_SEPARATOR, $this->logger->getDirectory());
    }
    
    public function testSetDirectoryWithDirectorySeparator () {
        $this->logger->setDirectory($this->path . DIRECTORY_SEPARATOR);
        $this->assertEquals($this->path . DIRECTORY_SEPARATOR, $this->logger->getDirectory());
    }
    
    public function testSetChannel () {
        $this->logger->setChannel('foo', 'bar');
        $this->logger->setChannel('x', 'y');
        $channels = $this->logger->getChannels();
        
        $expected = array(Liquid_Log::DEFAULT_CHANNEL => Liquid_Log::DEFAULT_CHANNEL, 'foo' => 'bar', 'x' => 'y');
        
        $this->assertEquals($expected, $channels);
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
    
    public function testSetLogLevelException () {
        $this->setExpectedException('Liquid_Log_Exception');
        $this->logger->setLogLevel('foo');
    }
    
    public function testLog () {
        $this->logger->setDirectory($this->path);
        $this->logger->setChannel('foo', 'bar');
        
        $testString = 'Hello World 1234567890-=[]{}\"\';/.,<>?!@#$%^&*()_+';
        
        $this->logger->log($testString, Liquid_Log::EMERG, 'foo');
        
        $filename = $this->path . DIRECTORY_SEPARATOR . 'bar' . Liquid_Log_Files::FILE_EXTENSION;
        
        $this->assertFileExists($filename);
        
        $contents = file_get_contents($filename);
        $this->assertContains($testString, $contents);
        $this->assertContains('EMERG', $contents);        
        
        unlink($filename);
        
        $this->logger->setLogLevel(Liquid_Log::ERR);
        
        $this->logger->log($testString, Liquid_Log::WARN, 'foo');
        
        $this->assertFileNotExists($filename);
        
        $this->logger->log($testString, Liquid_Log::CRIT, 'something_else');
        
        $filename = $this->path . DIRECTORY_SEPARATOR . Liquid_Log::DEFAULT_CHANNEL . Liquid_Log_Files::FILE_EXTENSION;
        
        $this->assertFileExists($filename);
        
        $contents = file_get_contents($filename);
        $this->assertContains($testString, $contents);
        $this->assertContains('CRIT', $contents);        
        
        unlink($filename);
    }
}
