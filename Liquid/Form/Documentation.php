<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Form
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
class Liquid_Form_Documentation {
    protected $form;
    
    public function setForm (Liquid_Form $form) {
        $this->form = $form;
    }
    
    public function getAsHtml () {
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . '/Documentation/View');
        
        $view->title = get_class($this->form);
        $view->form = $this->form->getDefinition();

        return $view->render('Html.phtml');
    }   
}
