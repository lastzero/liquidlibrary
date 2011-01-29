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
require_once 'Liquid/Storage/Adapter/Interface.php';

class LiquidStorageAdapterInterfaceTest extends PHPUnit_Framework_TestCase {
    protected $adapter = null;
    
    public function tearDown () {
        $this->adapter->deleteAll();
    }
    
    public function createFixtures () { 
        $this->adapter->deleteAll();
        
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'page',
            'namespace' => 'wiki',
            'data' => '[http://www.ibm.com/]', 
            'meta' => array(
                'time' => time()
            )
        ));
        
        $this->adapter->createEntry($entry);
        
        $entry = new Liquid_Storage_Entry(array(
            'key' => '/this/is/a/test',
            'namespace' => 'wiki',
            'data' => '[http://www.ibm.com/]', 
            'meta' => array(
                'time' => time()
            )
        ));
        
        $this->adapter->createEntry($entry);
        
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'bar',
            'namespace' => 'test',
            'data' => 'barcode', 
            'meta' => array('gh' => 'ij', 'hey' => 'ho'))
        );
        
        $this->adapter->createEntry($entry);
        
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'array',
            'namespace' => 'test',
            'data' => array('moin' => 'moin'), 
            'meta' => array('gh' => 'ij', 'cisco' => 'ibm', 'time' => time()))
        );
        
        $this->adapter->createEntry($entry);
        
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'foo',
            'namespace' => 'test',
            'data' => 'abcdef', 
            'meta' => array('gh' => 'ij', 'cisco' => 'ibm', 'time' => time()))
        );
        
        $this->adapter->createEntry($entry);
    }
       
    public function testCreateEntryString () {
        $value = 'bar';
        
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'foo',
            'namespace' => 'test', 
            'data' => $value)
        );
        
        $this->adapter->createEntry($entry);
        
        $result = $this->adapter->findLast('test', 'foo');
        
        $this->assertEquals($value, $result->getData());
    }
    
    public function testCreateEntryArray () {
        $value = array('srsdgsheg' => '(*&TFGVJI_=)');
        
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'foo',
            'namespace' => 'test', 
            'data' => $value)
        );
        
        $this->adapter->createEntry($entry);
        
        $result = $this->adapter->findLast('test', 'foo');
        
        $this->assertEquals($value, $result->getData());
    }
    
    public function testCreateEntryPNG () {
        $value = file_get_contents(dirname(__FILE__) . '/_files/example.png');
        
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'png',
            'namespace' => 'images', 
            'data' => $value)
        );
        
        $this->adapter->createEntry($entry);
        
        $result = $this->adapter->findLast('images', 'png');
        
        $this->assertEquals($value, $result->getData());
    }
    
    public function testCreateEntryJPG () {
        $value = file_get_contents(dirname(__FILE__) . '/_files/example.jpg');
        
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'jpg',
            'namespace' => 'images', 
            'data' => $value)
        );
        
        $this->adapter->createEntry($entry);
        
        $result = $this->adapter->findLast('images', 'jpg');
        
        $this->assertEquals($value, $result->getData());
    }
    
    public function testCreateEntryGIF () {
        $value = file_get_contents(dirname(__FILE__) . '/_files/example.gif');
        
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'gif',
            'namespace' => 'images', 
            'data' => $value)
        );
        
        $this->adapter->createEntry($entry);
        
        $result = $this->adapter->findLast('images', 'gif');
        
        $this->assertEquals($value, $result->getData());
    }
    
    public function testUpdateEntry () {
        $value = 'xyz';
        
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'foo',
            'namespace' => 'test', 
            'data' => 'lalala')
        );
        
        $this->adapter->createEntry($entry);        
        
        $entry = $this->adapter->findLast('test', 'foo');

        $entry->setData($value);
        
        $this->adapter->updateEntry($entry);
        
        $result = $this->adapter->findOne('test', 'foo', $entry->getId());
        
        $this->assertEquals($value, $result->getData());
    }
    
    public function testDeleteEntry () {
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'foo',
            'namespace' => 'test', 
            'data' => 'lalala')
        );
        
        $id = $this->adapter->createEntry($entry);                
        
        $entry = $this->adapter->findOne('test', 'foo', $id);

        $this->adapter->deleteEntry($entry);
        
        $this->setExpectedException('Liquid_Storage_Adapter_Exception_EntryNotFound');
        
        $this->adapter->findOne('test', 'foo', $id);
    }
    
    public function testNamespaceExists () {
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'foo',
            'namespace' => 'test', 
            'data' => 'lalala')
        );
        
        $this->adapter->createEntry($entry);
        
        $this->assertTrue($this->adapter->namespaceExists('test'));
        $this->assertTrue($this->adapter->namespaceExists('wiki'));

        $this->assertFalse($this->adapter->namespaceExists('q'));
    }
    
    public function testKeyExists () {
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'foo',
            'namespace' => 'test', 
            'data' => 'lalala')
        );
        
        $this->adapter->createEntry($entry);
        
        $this->assertTrue($this->adapter->keyExists('test', 'bar'));
        $this->assertTrue($this->adapter->keyExists('test', 'foo'));
        $this->assertTrue($this->adapter->keyExists('wiki', 'page'));
        
        $this->assertFalse($this->adapter->keyExists('test', 'q'));
    }
    
    public function testEntryExists () {
        $firstEntry  = $this->adapter->findFirst('test', 'foo');
        
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'foo',
            'namespace' => 'test', 
            'data' => 'lalala')
        );
        
        $id = $this->adapter->createEntry($entry);
        
        $secondEntry  = $this->adapter->findOne('test', 'foo', $id);
        
        $this->assertTrue($this->adapter->entryExists($firstEntry));
        $this->assertTrue($this->adapter->entryExists($secondEntry));
        
        $entry = new Liquid_Storage_Entry(array(
            'id' => -1,
            'key' => 'foo',
            'namespace' => 'test', 
            'data' => 'lalala')
        );
        
        $this->assertFalse($this->adapter->entryExists($entry));
    }
    
    public function testSetNamespaceMeta () {
        $this->adapter->setNamespaceMeta('wiki', array('foo' => 'bar'));    
        $meta = $this->adapter->getNamespaceMeta('wiki');    
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $meta);
        $this->assertArrayNotHasKey('created', $meta);
        $this->assertArrayHasKey('foo', $meta);
        $this->assertEquals('bar', $meta['foo']);
    }
    
    public function testGetNamespaceMeta () {
        $meta = $this->adapter->getNamespaceMeta('wiki');    
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $meta);
        $this->assertArrayHasKey('created', $meta);
    }
    
    public function testAddNamespaceMeta () {
        $this->adapter->setNamespaceMeta('wiki', array('foo' => 'bar'));    
        $this->setExpectedException('Liquid_Storage_Exception');
        $this->adapter->addNamespaceMeta('wiki', 'foo', 'too');
    }
    
    public function testReplaceNamespaceMeta () {
        $this->adapter->setNamespaceMeta('wiki', array('foo' => 'bar'));    
        $this->adapter->replaceNamespaceMeta('wiki', 'foo', 'too');
        
        $meta = $this->adapter->getNamespaceMeta('wiki');   
        $this->assertArrayHasKey('foo', $meta);
        
        $this->assertEquals('too', $meta['foo']);
        
        $this->adapter->replaceNamespaceMeta('wiki', 'bar', 'baz');
        $meta = $this->adapter->getNamespaceMeta('wiki');   
        $this->assertArrayHasKey('bar', $meta);
        $this->assertEquals('baz', $meta['bar']);
    }
    
    public function testDeleteNamespaceMeta () {
        $this->adapter->setNamespaceMeta('wiki', array('foo' => 'bar'));  
        $meta = $this->adapter->getNamespaceMeta('wiki');     
        $this->assertArrayHasKey('foo', $meta);
        $this->adapter->deleteNamespaceMeta('wiki', 'foo');
        $meta = $this->adapter->getNamespaceMeta('wiki');
        $this->assertArrayNotHasKey('foo', $meta);
    }
    
    public function testSetKeyMeta () {
        $this->adapter->setKeyMeta('wiki', 'page', array('bla' => 'blub'));    
        $meta = $this->adapter->getKeyMeta('wiki', 'page');    
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $meta);
        $this->assertArrayNotHasKey('created', $meta);
        $this->assertArrayHasKey('bla', $meta);
        $this->assertEquals('blub', $meta['bla']);
    }
    
    public function testGetKeyMeta () {
        $meta = $this->adapter->getKeyMeta('wiki', 'page');    
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $meta);
        $this->assertArrayHasKey('created', $meta);
    }

    public function testSetEntryMeta () {
        $entry = $this->adapter->findFirst('test', 'foo');
        
        $oldMeta = $entry->getMeta();
        
        $this->adapter->setEntryMeta($entry, array('a' => 'b'));
        
        $entry = $this->adapter->findFirst('test', 'foo');
        
        $newMeta = $entry->getMeta($entry);
        
        $this->assertNotEquals($oldMeta, $newMeta);
        
        $this->assertArrayHasKey('a', $newMeta);
        
        $this->assertContains('b', $newMeta);
    }
    
    public function testGetEntryMeta () {
        $entry = $this->adapter->findFirst('test', 'bar');
        
        $meta = $entry->getMeta();
        
        $this->assertArrayHasKey('hey', $meta);
        $this->assertEquals('ho', $meta['hey']);
        
        $entry = $this->adapter->findLast('test', 'foo');
        
        $this->assertArrayHasKey('time', $entry->getMeta());
    }
    
    public function testAddEntryMeta () {
        $random = mt_rand(0, 10000);
         
        $entry = $this->adapter->findLast('test', 'foo');
        
        $this->adapter->addEntryMeta($entry, 'random', $random);
        
        $entry = $this->adapter->findLast('test', 'foo');
        
        $meta = $entry->getMeta();
        
        $this->assertArrayHasKey('random', $meta);
        $this->assertEquals($random, $meta['random']);
        
        $this->setExpectedException('Liquid_Storage_Entry_Exception');
        
        $this->adapter->addEntryMeta($entry, 'random', $random);
    }
    
    public function testReplaceEntryMeta () {
        $random = mt_rand(0, 10000);
         
        $entry = $this->adapter->findLast('test', 'foo');
        
        $oldMeta = $entry->getMeta();

        $this->adapter->replaceEntryMeta($entry, 'time', $random);        
        
        $entry = $this->adapter->findLast('test', 'foo');
        
        $newMeta = $entry->getMeta();
        
        $this->assertArrayHasKey('time', $newMeta);
        $this->assertArrayHasKey('time', $oldMeta);
        $this->assertNotEquals($random, $oldMeta['time']);
        $this->assertEquals($random, $newMeta['time']);
    }
    
    public function testDeleteEntryMeta () {
        $random = mt_rand(0, 10000);
         
        $entry = $this->adapter->findLast('test', 'foo');
        
        $this->assertArrayHasKey('time', $entry->getMeta());
        
        $this->adapter->deleteEntryMeta($entry, 'time');
        
        $entry = $this->adapter->findLast('test', 'foo');
        
        $meta = $entry->getMeta();
        
        $this->assertArrayHasKey('gh', $meta);
        $this->assertEquals('ij', $meta['gh']);
        
        $this->setExpectedException('Liquid_Storage_Entry_Exception');
        
        $this->adapter->deleteEntryMeta($entry, 'time');
    }
    
    public function testFindKeys () {
        $result = $this->adapter->findKeys('test');
        $this->assertContains('bar', $result);
        $this->assertContains('foo', $result);
        $this->assertContains('array', $result);
        
        $this->setExpectedException('Liquid_Storage_Adapter_Exception_NamespaceNotFound');
        
        $result = $this->adapter->findKeys('sfsefwlej');
    }
    
    public function testFindIndex () {
        $entry = new Liquid_Storage_Entry(array(
            'namespace' => 'test',            
            'key' => 'foo',
            'data' => 'Apple', 
            'meta' => array('fsv' => 'zdf'))
        );

        $this->adapter->createEntry($entry);
        
        $result = $this->adapter->findIndex('test', 'foo');
        
        $this->assertEquals(2, count($result));
        
        $entry = new Liquid_Storage_Entry(array(
            'namespace' => 'test',            
            'key' => 'foo',
            'data' => 'PC', 
            'meta' => array('fsv' => 'zrd'))
        );
        
        $this->adapter->createEntry($entry);
        
        $result = $this->adapter->findIndex('test', 'foo');
        
        $this->assertEquals(3, count($result));
        
        foreach($result as $id => $meta) {
            $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $meta);
            $this->assertNotNull($id);
        }

        $this->setExpectedException('Liquid_Storage_Adapter_Exception_KeyNotFound');
        
        $result = $this->adapter->findIndex('test', 'sfsefwlej');
    }
    
    public function testFindById () {
        $expected = $this->adapter->findLast('test', 'foo');
        $entries = $this->adapter->findById($expected->getId());
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $entries);
        
        $this->assertGreaterThan(1, count($entries));

        foreach($entries as $entry) {
            $this->assertEquals($expected->getId(), $entry->getId());
        }        
    }
    
    public function testFindByMeta () {
        $entries = $this->adapter->findByMeta('rkgp354tl5gv', 'ho');
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $entries);
        $this->assertEquals(0, count($entries));
    
        $entries = $this->adapter->findByMeta('hey', 'rkgp354tl5gv');
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $entries);
        $this->assertEquals(0, count($entries));

        $entries = $this->adapter->findByMeta('hey', 'ho');
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $entries);
        $this->assertEquals(1, count($entries));

        $entry = current($entries);
        $this->assertEquals('barcode', $entry->getData());
        
        $entries = $this->adapter->findByMeta('gh', 'ij');
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $entries);
        $this->assertEquals(3, count($entries));

        foreach($entries as $entry) {
            $this->assertTrue($entry instanceof Liquid_Storage_Entry);
        }        
    }
    
    public function testFindByMetaArray () {
        $entries = $this->adapter->findByMeta(array('rkgp354tl5gv' => 'ho'), null);
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $entries);
        $this->assertEquals(0, count($entries));
    
        $entries = $this->adapter->findByMeta(array('hey' => 'rkgp354tl5gv'), null);
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $entries);
        $this->assertEquals(0, count($entries));

        $entries = $this->adapter->findByMeta(array('hey' => 'ho'), null);
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $entries);
        $this->assertEquals(1, count($entries));

        $entry = current($entries);
        $this->assertEquals('barcode', $entry->getData());
        
        $entries = $this->adapter->findByMeta(array('gh' => 'ij', 'cisco' => 'ibm'), null);
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $entries);
        $this->assertEquals(2, count($entries));

        foreach($entries as $entry) {
            $this->assertTrue($entry instanceof Liquid_Storage_Entry);
        }    
        
        $entries = $this->adapter->findByMeta(array('gh' => 'ij', 'cisco' => 'ibm'), 1);
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $entries);
        $this->assertEquals(3, count($entries));

        foreach($entries as $entry) {
            $this->assertTrue($entry instanceof Liquid_Storage_Entry);
        }        
    }
    
    public function testFindOne () {
        $entry1 = $this->adapter->findLast('test', 'foo');
        $entry2 = $this->adapter->findOne('test', 'foo', $entry1->getId());

        $entryData1 = $entry1->getAsArray();
        $entryData2 = $entry1->getAsArray();

        $this->assertEquals($entryData1, $entryData2);
    }
    
    public function testFindFirst () {
        $entry = new Liquid_Storage_Entry(array(
            'key' => 'foo',
            'namespace' => 'test', 
            'data' => 'lalala')
        );
        
        $this->adapter->createEntry($entry);

        $entry = $this->adapter->findFirst('test', 'foo');
        
        $this->assertEquals('abcdef', $entry->getData());
    }
    
    public function testFindLast () {
        $expected = new Liquid_Storage_Entry(array(
            'key' => 'foo',
            'namespace' => 'test', 
            'data' => 'lalala')
        );
        
        $this->adapter->createEntry($expected);

        $entry = $this->adapter->findLast('test', 'foo');
        
        $this->assertEquals($expected->getData(), $entry->getData());
        $this->assertEquals($expected->getKey(), $entry->getKey());
        $this->assertEquals($expected->getNamespace(), $entry->getNamespace());
    }
    
    public function testGetNamespaces () {
        $list = $this->adapter->getNamespaces();
        
        $this->assertContains('wiki', $list);
        $this->assertContains('test', $list);
    }
    
    public function testRenameNamespace () {
        $this->assertTrue($this->adapter->namespaceExists('test'));
        $this->assertFalse($this->adapter->namespaceExists('usa'));

        $this->adapter->renameNamespace('test', 'usa');

        $this->assertFalse($this->adapter->namespaceExists('test'));
        $this->assertTrue($this->adapter->namespaceExists('usa'));
    }

    public function testRenameKey () {
        $this->assertTrue($this->adapter->keyExists('test', 'foo'));
        $this->assertFalse($this->adapter->keyExists('test', 'france'));

        $this->adapter->renameKey('test', 'foo', 'france');

        $this->assertFalse($this->adapter->keyExists('test', 'foo'));
        $this->assertTrue($this->adapter->keyExists('test', 'france'));        
    }
}
