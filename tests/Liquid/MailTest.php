<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Mail
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
require_once 'tests/_config.php';

require_once 'Liquid/Mail.php';

class LiquidMailTest extends PHPUnit_Framework_TestCase {
    public function setUp () {
        $this->mail = new Liquid_Mail();
    }
    
    public function testSend () {
        $values = array('foo' => 'bar');
        
        $mail = new Liquid_Mail();
        $mail->setPath(dirname(__FILE__).'/_templates/');
        $mail->setFrom('info@ke-club.de');
        $mail->setSubject('Testmail');
        $mail->setTemplate('testmail.phtml');
        $mail->setRecipient('michael@liquidbytes.net');
        $mail->setValues($values);
        $mail->send();
    }
}
