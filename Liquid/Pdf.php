<?php 
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Pdf
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
require_once 'Zend/Pdf.php';
require_once 'Liquid/Pdf/Merge.php';

class Liquid_Pdf {
	protected $positions = array();
	protected $config = array();
	
	protected $document;
	
	public function __construct(Zend_Pdf $document, Array $config) {
	   $this->config = $config;
	   $this->document = $document;    	
	}
	
	public static function createEmptyDocument ($config = array()) {
    	return new Liquid_Pdf(new Zend_Pdf(), $config);
	}
	
    public static function createFromFile  ($fileName, $config = array()) {
        return new Liquid_Pdf(Zend_Pdf::load($fileName), $config);
    }
    
    protected function preRender () {
        // Optional    	
	}
    
    public function getAsString () {
        $this->preRender();
        return $this->document->render();
    }
    
    public function saveToFile ($fileName) {
        $this->preRender();
        $this->document->save($fileName);
    }

    public function __set ($name, $value) {
    	if(!isset($this->config[$name])) return;
    	
    	$config = $this->config[$name];
    	
    	if(isset($config['page'])) {
    		$page = $config['page'];
    	} else {
    	    $page = 0;	
    	}
    	
    	$this->document->pages[$page]->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
        $this->document->pages[$page]->drawText($value, $config['pos'][0], $config['pos'][1], 'UTF-8');
    }
    
    public static function mergeFiles (Array $files) {
        $pdf = new Liquid_Pdf_Merge(); 
        $pdf->setFiles($files); 
        $pdf->concat(); 
        return $pdf->Output('', 'S');
    }

    public static function mergeDocuments (Array $docs) {
        $pdf = new Liquid_Pdf_Merge(); 
        $pdf->setDocuments($docs); 
        $pdf->concat(); 
        return $pdf->Output('', 'S');
    }
}
