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

class LiquidFormExampleTest extends PHPUnit_Framework_TestCase {
    public function testValidateSuccess () {
        $this->form = new Liquid_Form_Example();
                
        $values = array(
            'firstname' => 'Michael', 
            'temperature' => 31, 
            'email' => 'xyz@ibm.com',
            'cars' => array(
                'bmw' => 1,
                'hond' => 2
            ),
            'computers' => array(
                'apple' => 1
            ),
            'sports' => 'dance',
            'spacetravel' => 'hello',
            'drink' => true,
            'vehicle' => '1,1234'
        );
        
        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(0, count($errors));
    }
    
    public function testValidateError () {
        $this->form = new Liquid_Form_Example();
                
        $values = array(
            'firstname' => 'Michael', 
            'temperature' => 31, 
            'email' => 'xyz@ibm.com',
            'computers' => array(
                'apple' => 1
            ),
            'sports' => 'dance',
            'spacetravel' => 'hello',
            'vehicle' => '1,1234'
        );
        
        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(1, count($errors));
    }
}
