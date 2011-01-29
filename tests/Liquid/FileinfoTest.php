<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Fileinfo
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
require_once 'tests/_config.php';

require_once 'Liquid/Fileinfo.php';

class LiquidFileinfoTest extends PHPUnit_Framework_TestCase {
    public function testGetExtension () {
        $fileinfo = new Liquid_Fileinfo('<html></html>');
        $this->assertEquals('html', $fileinfo->getExtension());

        $fileinfo = new Liquid_Fileinfo(file_get_contents(dirname(__FILE__) . '/Fileinfo/_files/example.gif'));
        $this->assertEquals('gif', $fileinfo->getExtension());

        $fileinfo = new Liquid_Fileinfo(file_get_contents(dirname(__FILE__) . '/Fileinfo/_files/example.jpg'));
        $this->assertEquals('jpg', $fileinfo->getExtension());

        $fileinfo = new Liquid_Fileinfo(file_get_contents(dirname(__FILE__) . '/Fileinfo/_files/example.png'));
        $this->assertEquals('png', $fileinfo->getExtension());
    }
    
    public function testGetCharset () {
        $fileinfo = new Liquid_Fileinfo('<html></html>');
        
        $this->assertEquals('us-ascii', $fileinfo->getCharset());

        $fileinfo = new Liquid_Fileinfo('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'.
        '</head><body>Mögliche Werte sind fifo, char, dir, block, link, file und unknown.</body></html>');
        
        $this->assertEquals('utf-8', $fileinfo->getCharset());
    }
    
    public function testGetMime () {
        $fileinfo = new Liquid_Fileinfo('<html></html>');
        $this->assertEquals('text/html', $fileinfo->getMime());
        
        $fileinfo = new Liquid_Fileinfo(file_get_contents(dirname(__FILE__) . '/Fileinfo/_files/example.gif'));
        $this->assertEquals('image/gif', $fileinfo->getMime());

        $fileinfo = new Liquid_Fileinfo(file_get_contents(dirname(__FILE__) . '/Fileinfo/_files/example.jpg'));
        $this->assertEquals('image/jpeg', $fileinfo->getMime());

        $fileinfo = new Liquid_Fileinfo(file_get_contents(dirname(__FILE__) . '/Fileinfo/_files/example.png'));
        $this->assertEquals('image/png', $fileinfo->getMime());
    }
}
