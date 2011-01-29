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

require_once 'Liquid/Service/Wiki.php';

class LiquidServiceWikiTest extends PHPUnit_Framework_TestCase {
    public function setUp () {
        $this->storage = new Liquid_Storage_Adapter_Files(dirname(__FILE__) . '/_temp/');       
        $this->storage->deleteAll();   
        $this->wiki = new Liquid_Service_Wiki('/wiki/', dirname(__FILE__) . '/_temp/', $this->storage);
    }
    
    public function tearDown () {
        $this->storage->deleteAll();
    }
    
    public function testCreate () {
        $this->wiki->create('test', 'content');
        $content = $this->wiki->html('test');
        
        $this->assertEquals('<p>content</p>', trim($content));
        
        $this->wiki->create('latex', '[tex]a_i = a_0 + i \cdot d[/tex]');
        $content = $this->wiki->html('latex');
        
        $this->assertEquals('<p><img src="/img/latex/f/7/7/a/f77af2fb31c948968b30e393f6561a64.png" class="latex" /></p>', trim($content)); 
        
        $this->assertFileExists(dirname(__FILE__) . '/_temp/img/latex/f/7/7/a/f77af2fb31c948968b30e393f6561a64.png');
        
        $fileinfo = new Liquid_Fileinfo(file_get_contents(dirname(__FILE__) . '/_temp/img/latex/f/7/7/a/f77af2fb31c948968b30e393f6561a64.png'));
        
        $this->assertEquals('image/png', $fileinfo->getMime());
        
        unlink(dirname(__FILE__) . '/_temp/img/latex/f/7/7/a/f77af2fb31c948968b30e393f6561a64.png');
    }
    
    public function testDelete () {
        $this->wiki->create('test', 'content');
        $content = $this->wiki->html('test');
        
        $this->assertEquals('<p>content</p>', trim($content));

        $this->wiki->delete('test');        
        
        $content = $this->wiki->find('test');
        
        $this->assertEquals('', trim($content));
    }        
    
    public function testUpdate () {
        $this->wiki->create('test', '1');
        $content = $this->wiki->html('test');
        
        $this->assertEquals('<p>1</p>', trim($content));

        $this->wiki->update('test', 'yeah');
         
        $content = $this->wiki->html('test');
        
        $this->assertEquals('<p>yeah</p>', trim($content));
    }
    
    public function testHtml () {
        $this->wiki->create('test', 'Your life is just beginning');
        $this->wiki->create('test', 'Keep your soul');
        $content = $this->wiki->html('test');
        
        $this->assertEquals('<p>Keep your soul</p>', trim($content));
    }

    public function testGetPages () {
        $this->wiki->create('test', 'Your life is just beginning');
        $this->wiki->create('test', 'Keep your soul');
        $this->wiki->create('Köln', 'Nevermind');
        $this->wiki->create('Bar <', '_baz_');
        
        $pages = $this->wiki->getPages();
        
        $this->assertEquals(3, count($pages));
        $this->assertEquals('test', $pages[0]['page']);
        $this->assertArrayHasKey('created', $pages[0]);
        $this->assertArrayHasKey('timestamp', $pages[0]);
        $this->assertEquals('Köln', $pages[1]['page']);
        $this->assertEquals('K%C3%B6ln', $pages[1]['urlencoded']);
        $this->assertEquals('Bar <', $pages[2]['page']);
        $this->assertEquals('Bar &lt;', $pages[2]['escaped']);
        $this->assertEquals('Bar%20%3C', $pages[2]['urlencoded']);
    }

    public function testGetPageRevisions () {
        $this->wiki->create('foo', 'Your life is just beginning');
        $this->wiki->create('foo', 'Keep your soul');
        $this->wiki->create('foo', 'Nevermind');

        $revs = $this->wiki->getPageRevisions('foo');
        
        $this->assertEquals(3, count($revs));
    }
    
    public function testReplace () {
        $this->wiki->replace('foo', 'Your life is just beginning');
        $this->wiki->replace('foo', 'Keep your soul');
        $this->wiki->replace('foo', 'Nevermind');

        $revs = $this->wiki->getPageRevisions('foo');
        
        $this->assertEquals(1, count($revs));
        
        $content = $this->wiki->html('foo');
        
        $this->assertEquals('<p>Nevermind</p>', trim($content));
    }
    
    public function testRenderAsHtml () {
        $result = $this->wiki->renderAsHtml("foo\n\nbar");
        $this->assertEquals("<p>foo</p>\n\n<p>bar</p>", trim($result));
    }
}
