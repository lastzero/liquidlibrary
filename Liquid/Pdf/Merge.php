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
 
require_once 'Fpdf/fpdf.php';
require_once 'Fpdf/fpdi.php';
	 
class Liquid_Pdf_Merge extends FPDI { 
    protected $files = array();
    protected $deleteAfterConcat = false; 
	 
    public function setFiles($files) { 
        $this->files = $files;
        $this->deleteAfterConcat = false;
    }
    
    public function setDocuments($docs) {
        $this->files = array();
        
        foreach($docs as $doc) {
            $filename = tempnam(sys_get_temp_dir(), 'pdfmerge');
            file_put_contents($filename, $doc);
            $this->files[] = $filename; 
        }
        
       $this->deleteAfterConcat = true;
    } 
	 
    public function concat() { 
        foreach($this->files as $file) { 
            $pagecount = $this->setSourceFile($file); 
            for ($i = 1; $i <= $pagecount; $i++) { 
                 $tplidx = $this->ImportPage($i); 
                 $s = $this->getTemplatesize($tplidx); 
                 $this->AddPage('P', array($s['w'], $s['h'])); 
                 $this->useTemplate($tplidx); 
            }
            
            if($this->deleteAfterConcat) {
                unlink($file);
            }
        } 
    }      
} 

