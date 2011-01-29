<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Storage
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
require_once 'tests/_config.php';

require_once 'Liquid/Storage/Entry.php';

class LiquidStorageEntryTest extends PHPUnit_Framework_TestCase {
    public function setUp () {
        $this->expectedValues = array(
            'data' => 'ABC',
            'meta' => array('foo' => 'bar', 'x' => 'y'),
            'id' => md5(123),
            'key' => '/there/we/go',
            'namespace' => 'thisIsATest'
        );
        
        $this->entry = new Liquid_Storage_Entry ($this->expectedValues);
    }
    
    public function testGetData () {
        $this->assertEquals($this->expectedValues['data'], $this->entry->getData());
    }
    
    public function testGetMeta () {
        $this->assertEquals($this->expectedValues['meta'], $this->entry->getMeta());
    }
    
    public function testGetId () {
        $this->assertEquals($this->expectedValues['id'], $this->entry->getId());
    }
    
    public function testGetKey () {
        $this->assertEquals($this->expectedValues['key'], $this->entry->getKey());
    }
    
    public function testGetNamespace () {
        $this->assertEquals($this->expectedValues['namespace'], $this->entry->getNamespace());
    }
    
    public function testSetData () {
        $rand = mt_rand();
        $this->entry->setData($rand);
        $this->assertEquals($rand, $this->entry->getData());
    }
    
    public function testSetMeta () {
        $rand = mt_rand();
        $this->entry->setMeta(array('foo' => $rand));
        $this->assertEquals(array('foo' => $rand), $this->entry->getMeta());
    }
    
    public function testSetIdException () {
        $this->setExpectedException('Liquid_Storage_Entry_Exception');
        $this->entry->setId(1);
    }
    
    public function testSetKeyException () {
        $this->setExpectedException('Liquid_Storage_Entry_Exception');
        $this->entry->setKey(2);
    }
    
    public function testSetNamespaceException () {
        $this->setExpectedException('Liquid_Storage_Entry_Exception');
        $this->entry->setNamespace(3);
    }
    
    public function testGetAsArray () {
        $values = array(
            'data' => 'ABC',
            'meta' => array('foo' => 'bar', 'x' => 'y'),
            'id' => md5(123),
            'key' => '/there/we/go',
            'namespace' => 'thisIsATest'
        );
        
        $entry = new Liquid_Storage_Entry ();
        $entry->setId($values['id']);
        $entry->setKey($values['key']);
        $entry->setNamespace($values['namespace']);
        $entry->setMeta($values['meta']);
        $entry->setData($values['data']);
        
        $this->assertEquals($this->entry->getAsArray(), $entry->getAsArray());
        
        $entry->setData(array());
        
        $this->assertNotEquals($this->entry->getAsArray(), $entry->getAsArray());
    }
    
    public function testGetAsJson () {        
        $entry = new Liquid_Storage_Entry ($this->entry->getAsArray());
        
        $this->assertEquals($this->entry->getAsJson(), $entry->getAsJson());
        
        $this->assertEquals($this->entry->getAsArray(), Zend_Json::decode($entry->getAsJson()));

        $this->assertEquals($entry->getAsArray(), Zend_Json::decode($entry->getAsJson()));
    }
    
    public function testIsScalar () {
        $this->assertTrue($this->entry->isScalar());

        $this->entry->setData(array('abc'));
        
        $this->assertFalse($this->entry->isScalar());
    }
    
    public function testAddMeta () {
        $this->entry->addMeta('see', 'you');
        
        $meta = $this->entry->getMeta();
        
        $this->assertArrayHasKey('see', $meta);
        $this->assertEquals('you', $meta['see']);
        
        $this->setExpectedException('Liquid_Storage_Entry_Exception');

        $this->entry->addMeta('foo', 'you');
    }
    
    public function testReplaceMeta () {        
        $meta = $this->entry->getMeta();
        
        $this->assertArrayHasKey('foo', $meta);
        $this->assertEquals('bar', $meta['foo']);
        
        $this->entry->replaceMeta('foo', 'baz');
        $this->entry->replaceMeta('xxx', 'yyy');
        
        $meta = $this->entry->getMeta();
        
        $this->assertArrayHasKey('foo', $meta);
        $this->assertEquals('baz', $meta['foo']);
    }
    
    public function testDeleteMeta () {
        $meta = $this->entry->getMeta();
        
        $this->assertArrayHasKey('foo', $meta);

        $this->entry->deleteMeta('foo');
        
        $meta = $this->entry->getMeta();
        
        $this->assertArrayNotHasKey('foo', $meta);
        
        $this->setExpectedException('Liquid_Storage_Entry_Exception');

        $this->entry->deleteMeta('foo', 'you');
    }
}
