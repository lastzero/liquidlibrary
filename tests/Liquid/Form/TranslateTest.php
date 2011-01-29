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

require_once 'Liquid/Form/Translate.php';

class LiquidFormTranslateTest extends PHPUnit_Framework_TestCase {
    public function testSetLocale () {
        $this->form = new Liquid_Form();
        
        Liquid_Form_Translate::setLocale('de_DE');

        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'type' => 'string',
                    'min' => 2,
                    'max' => 10,
                    'caption' => 'Vorname'
                 ), 
                 'temperature' => array(
                    'type' => 'numeric',
                    'min' => 29.9,
                    'max' => 50.1,
                    'caption' => 'Temperatur'
                 ), 
                 'email' => array(
                    'type' => 'EmailAddress'
                 ),
                 'cars' => array(
                    'caption' => 'Autos',
                    'type' => 'array',
                    'options' => array(
                        'bmw' => 'BMW',
                        'hond' => 'Honda',
                        'gmc' => 'General Motors'
                    ),
                    'min' => 1,
                    'max' => 2
                 ),
                 'computers' => array(
                    'caption' => 'Computer',
                    'type' => 'array',
                    'options' => array(
                        'len' => 'Lenovo',
                        'hp' => 'HP',
                        'apple' => 'Apple'
                    )
                 ),
                 'sports' => array(
                    'caption' => 'Sport',
                    'type' => 'array',
                    'options' => array(
                        'soccer' => 'Fussball',
                        'chess' => 'Schach',
                        'dance' => 'Tanzen'
                    )
                 ),
                 'bar' => array(
                    'default' => 'foo',
                    'type' => 'string',
                    'required' => true
                 ),
                 'spacetravel' => array(
                    'depends' => 'sports',
                    'depends_value' => 'running'
                 )  
             )
        );
        
        $values = array(
            'firstname' => 'x', 
            'temperature' => 'bar', 
            'email' => 'xyz',
            'cars' => array(
                'bmw' => 1,
                'hond' => 2,
                'gmc' => 3
            ),
            'computers' => array(
                'belinea' => 1
            ),
            'sports' => 'running',
            'bar' => ''
        );
        
        $this->form->setWritableValues($values);
               
        $errorsDE = $this->form->validate()->getErrors();
                       
        $this->assertEquals(8, count($errorsDE));
        
        Liquid_Form_Translate::reset();
        
        $errorsEN = $this->form->clearErrors()->validate()->getErrors();
        
        $this->assertNotEquals($errorsEN, $errorsDE);
    }
}
