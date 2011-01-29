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
 
require_once 'tests/_config.php';

require_once 'Liquid/Form.php';

require_once 'Liquid/Form/Example.php';

require_once 'Liquid/Form/Documentation.php';

class LiquidFormDocumentationTest extends PHPUnit_Framework_TestCase {
    public function testGetAsHtml () {
        $form = new Liquid_Form_Example();
        $docs = new Liquid_Form_Documentation();
        $docs->setForm($form);
        $result = $docs->getAsHtml();
        $fileinfo = new Liquid_Fileinfo ($result);
        $this->assertEquals('text/html', $fileinfo->getMime());
    }
}
                
