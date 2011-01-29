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
 
require_once 'tests/_config.php';

require_once 'Liquid/Fixture.php';

class LiquidFixtureTest extends PHPUnit_Framework_TestCase {
    public function testFilterAlphanumeric () {
        $this->assertEquals('_abc123', Liquid_Fixture::filterAlphanumeric('_-abc@#$%^&*(123,./<>?:"{}|\]['));
        $this->assertEquals('', Liquid_Fixture::filterAlphanumeric(''));        
        $this->assertEquals(array('php_'), Liquid_Fixture::filterAlphanumeric(array('php!@#$%^%_*()')));
    }
    
    public function testGetFilename () {
        $this->assertEquals('foo_barbaz.fix', Liquid_Fixture::getFilename('foo_bar.baz&*()'));
        $this->assertEquals('foo_barbaz.array_a_b.fix', Liquid_Fixture::getFilename('foo_bar.baz&*()', array('a' => 'b')));
        $this->assertEquals('foo.bar.fix', Liquid_Fixture::getFilename('foo!', 'bar'));
        $this->assertEquals('foo.fix', Liquid_Fixture::getFilename('foo'));
        $this->assertEquals('GoOo.b7f3e6756b2ca19c4b06f5e95061e342.fix', Liquid_Fixture::getFilename('GoOo', 'e5v8snjpv0pjsev4fjp0ws4tfghsge;]-c3seecfjhisfhijjijkjmcs8jvn'));
    }
    
    public function testNormalizePath () {
        $this->assertEquals(getcwd() . DIRECTORY_SEPARATOR, Liquid_Fixture::normalizePath(''));
        $this->assertEquals(dirname(__FILE__) . DIRECTORY_SEPARATOR, Liquid_Fixture::normalizePath(dirname(__FILE__)));
        $this->assertEquals(dirname(__FILE__) . DIRECTORY_SEPARATOR, Liquid_Fixture::normalizePath(dirname(__FILE__) . DIRECTORY_SEPARATOR));
    }
    
    public function testNormalizePathException () {
        $this->setExpectedException('Liquid_Fixture_Exception');
        
        Liquid_Fixture::normalizePath('/a/b/c');
    }
    
    public function testConstructor () {
        $this->setExpectedException('Liquid_Fixture_Exception');

        $fixture = new Liquid_Fixture('');
    }

    public function testGetData () {
        $fixture = new Liquid_Fixture(dirname(__FILE__) . '/_fixtures/fixture_test_get_data.fix');        
        $this->assertEquals(array('a' => 'b', array('b' => 'c')), $fixture->getData());
    }

    public function testSetData () {
        $fixture = new Liquid_Fixture(dirname(__FILE__) . '/_fixtures/fixture_test_set_data.fix');
        $fixture->setData(array('a' => 'b', array('x' => 'y')));
        $this->assertEquals('a:2:{s:1:"a";s:1:"b";i:0;a:1:{s:1:"x";s:1:"y";}}', file_get_contents(dirname(__FILE__) . '/_fixtures/fixture_test_set_data.fix'));
    }
}
