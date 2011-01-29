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

require_once 'tests/Liquid/Storage/Adapter/InterfaceTest.php';
require_once 'Liquid/Storage/Adapter/Files.php';

class LiquidStorageAdapterFilesTest extends LiquidStorageAdapterInterfaceTest {
    public function setUp () {
        $this->adapter = new Liquid_Storage_Adapter_Files(dirname(__FILE__) . '/_temp/');
        
        $this->createFixtures();
    }
    
    public function testCreateEntryPNG () {
        parent::testCreateEntryPNG();
        
        $entry = $this->adapter->findLast('images', 'png');
        
        $meta = $entry->getMeta();
        
        $this->assertArrayHasKey('charset', $meta);
        $this->assertEquals('binary', $meta['charset']);
        $this->assertArrayHasKey('mime', $meta);
        $this->assertEquals('image/png', $meta['mime']);
        $this->assertArrayHasKey('created', $meta);
        $this->assertGreaterThan(time() - 1, $meta['created']);
        
        $entry->addMeta('comment', 'example');
        
        $this->adapter->updateEntry($entry);

        $this->adapter->refreshEntry($entry);

        $meta = $entry->getMeta();
        
        $this->assertArrayHasKey('comment', $meta);
        $this->assertEquals('example', $meta['comment']);
        $this->assertArrayHasKey('charset', $meta);
        $this->assertEquals('binary', $meta['charset']);
        $this->assertArrayHasKey('mime', $meta);
        $this->assertEquals('image/png', $meta['mime']);
        $this->assertArrayHasKey('created', $meta);
        $this->assertGreaterThan(time() - 1, $meta['created']);
        $this->assertArrayHasKey('updated', $meta);
        $this->assertGreaterThan(time() - 1, $meta['updated']);
    }
    
    public function testCreateEntryJPG () {
        parent::testCreateEntryJPG();
        
        $entry = $this->adapter->findLast('images', 'jpg');
        
        $meta = $entry->getMeta();
        
        $this->assertArrayHasKey('charset', $meta);
        $this->assertEquals('binary', $meta['charset']);
        $this->assertArrayHasKey('mime', $meta);
        $this->assertEquals('image/jpeg', $meta['mime']);
        $this->assertArrayHasKey('created', $meta);
        $this->assertGreaterThan(time() - 1, $meta['created']);
        
        $entry->addMeta('comment', 'example');
        
        $this->adapter->updateEntry($entry);

        $this->adapter->refreshEntry($entry);

        $meta = $entry->getMeta();
        
        $this->assertArrayHasKey('comment', $meta);
        $this->assertEquals('example', $meta['comment']);
        $this->assertArrayHasKey('charset', $meta);
        $this->assertEquals('binary', $meta['charset']);
        $this->assertArrayHasKey('mime', $meta);
        $this->assertEquals('image/jpeg', $meta['mime']);
        $this->assertArrayHasKey('created', $meta);
        $this->assertGreaterThan(time() - 1, $meta['created']);
        $this->assertArrayHasKey('updated', $meta);
        $this->assertGreaterThan(time() - 1, $meta['updated']);
    }
    
    public function testCreateEntryGIF () {
        parent::testCreateEntryGIF();
        
        $entry = $this->adapter->findLast('images', 'gif');
        
        $meta = $entry->getMeta();
        
        $this->assertArrayHasKey('charset', $meta);
        $this->assertEquals('binary', $meta['charset']);
        $this->assertArrayHasKey('mime', $meta);
        $this->assertEquals('image/gif', $meta['mime']);
        $this->assertArrayHasKey('created', $meta);
        $this->assertGreaterThan(time() - 1, $meta['created']);
        
        $entry->addMeta('comment', 'Now, we change the type');
        $entry->setData(file_get_contents(dirname(__FILE__) . '/_files/example.png'));
        
        $this->adapter->updateEntry($entry);

        $this->adapter->refreshEntry($entry);

        $meta = $entry->getMeta();
        
        $this->assertArrayHasKey('comment', $meta);
        $this->assertEquals('Now, we change the type', $meta['comment']);
        $this->assertArrayHasKey('charset', $meta);
        $this->assertEquals('binary', $meta['charset']);
        $this->assertArrayHasKey('mime', $meta);
        $this->assertEquals('image/png', $meta['mime']);
        $this->assertArrayHasKey('created', $meta);
        $this->assertGreaterThan(time() - 1, $meta['created']);
        $this->assertArrayHasKey('updated', $meta);
        $this->assertGreaterThan(time() - 1, $meta['updated']);
    }
}
