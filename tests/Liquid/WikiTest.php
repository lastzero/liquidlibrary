<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Wiki
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
require_once 'tests/_config.php';

require_once 'Liquid/Wiki.php';

class LiquidWikiTest extends PHPUnit_Framework_TestCase {
    public function testRenderAsHtml () {   
        $wiki = new Liquid_Wiki('http://www.example.com/', dirname(__FILE__) . '/_temp/latex/');      
        $value = file_get_contents(dirname(__FILE__) . '/_fixtures/wiki.txt');
        $result = $wiki->renderAsHtml($value);
        $files = scandir(dirname(__FILE__) . '/_temp/img/latex');

        foreach($files as $file) {
            $filename = dirname(__FILE__) . '/_temp/img/latex/' . $file;
            if(is_file($filename)) {
                $fileinfo = new Liquid_Fileinfo(file_get_contents($filename));
        
                $this->assertEquals('image/gif', $fileinfo->getMime());
                
                unlink($filename);
            }
        }
    }
    
    public function testRenderAsLatex () {   
        $wiki = new Liquid_Wiki('http://www.example.com/', dirname(__FILE__) . '/_temp/');      
        $value = file_get_contents(dirname(__FILE__) . '/_fixtures/wiki.txt');
        $result = $wiki->renderAsLatex($value);
        
        $fileinfo = new Liquid_Fileinfo($result);
        $this->assertEquals('text/x-tex', $fileinfo->getMime());
        $this->assertEquals('us-ascii', $fileinfo->getCharset());     
    }
    
    public function testRenderAsPdf () {   
        $wiki = new Liquid_Wiki('http://www.example.com/', dirname(__FILE__) . '/_temp/');      
        $value = file_get_contents(dirname(__FILE__) . '/_fixtures/wiki.txt');
        $result = $wiki->renderAsPdf($value);
        
        $fileinfo = new Liquid_Fileinfo($result);
        $this->assertEquals('application/pdf', $fileinfo->getMime());
    }
    
    public function testRenderAsPdfBook () {   
        $wiki = new Liquid_Wiki('http://www.example.com/', dirname(__FILE__) . '/_temp/');      
        $value = file_get_contents(dirname(__FILE__) . '/_fixtures/wikibook.txt');
        $result = $wiki->renderAsPdfBook($value);
        file_put_contents('book.pdf', $result);
        $fileinfo = new Liquid_Fileinfo($result);
        $this->assertEquals('application/pdf', $fileinfo->getMime());
    }
    
    public function testRenderAsText () {   
        $wiki = new Liquid_Wiki('http://www.example.com/', dirname(__FILE__) . '/_temp/');      
        $value = file_get_contents(dirname(__FILE__) . '/_fixtures/wiki.txt');
        $result = $wiki->renderAsText($value);
        
        $fileinfo = new Liquid_Fileinfo($result);
        $this->assertEquals('text/plain', $fileinfo->getMime());
        $this->assertEquals('us-ascii', $fileinfo->getCharset());
    }
}
