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

class LiquidFormTest extends PHPUnit_Framework_TestCase {
    public function setUp () {
        $this->form = new Liquid_Form();
    }

    public function testSetLocale () {
        Liquid_Form::setDefaultLocale();
        $localeStatic = Liquid_Form::getDefaultLocale();
        $localeInstance = $this->form->getLocale();
        $this->assertEquals($localeStatic, $localeInstance);

        $locale = new Zend_Locale('en_US');

        $this->form->setLocale($locale);
        $this->assertEquals($locale, $this->form->getLocale());

        $locale = new Zend_Locale('de_DE');

        $this->form->setLocale($locale);
        $this->assertEquals($locale, $this->form->getLocale());

        Liquid_Form::setDefaultLocale();
        $this->form->setLocale();

        $localeStatic = Liquid_Form::getDefaultLocale();
        $localeInstance = $this->form->getLocale();
        $this->assertEquals($localeStatic, $localeInstance);
    }

    public function testGetForm () {
        $result = $this->form->getForm();
        $this->assertEquals(array(), $result);
    }

    public function testSetAllValues () {
        $this->setExpectedException('Liquid_Form_Exception');
        $values = array('foo' => 'bar', 'x' => 'y');
        $this->form->setAllValues($values);
        $result = $this->form->getValues();
    }

    public function testSetWritableValues () {
        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'readonly' => true
                 ),
                 'lastname' => array(
                    'readonly' => false
                 ),
                 'company' => array(
                    'type' => 'string'
                 ),
                 'foo.bar' => array(
                    'default' => 'foo',
                    'type' => 'string'
                 )
             )
        );

        $values = array('firstname' => 'foo', 'lastname' => 'bar', 'company' => 'xyz');

        $this->form->setWritableValues($values);

        $result = $this->form->getValues();

        $this->assertEquals(null, $result['firstname']);
        $this->assertEquals($values['lastname'], $result['lastname']);
        $this->assertEquals($values['company'], $result['company']);
        $this->assertEquals('foo', $result['foo.bar']);
    }

    public function testSetWritableValuesOnPageSuccess () {
        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'readonly' => true,
                    'page' => 1
                 ),
                 'lastname' => array(
                    'readonly' => false,
                    'page' => 2
                 ),
                 'company' => array(
                    'type' => 'string',
                    'page' => 2
                 ),
                 'mustsee' => array(
                    'type' => 'bool',
                    'checkbox' => true,
                    'page' => 2
                 ),
                 'bar' => array(
                    'default' => 'foo',
                    'type' => 'string',
                    'page' => 3
                 )
             )
        );

        $values = array('lastname' => 'foo', 'company' => 'bar');

        $this->form->setWritableValuesOnPage($values, 2);

        $result = $this->form->getValues();

        $this->assertEquals(null, $result['firstname']);
        $this->assertEquals($values['lastname'], $result['lastname']);
        $this->assertEquals($values['company'], $result['company']);
        $this->assertEquals('foo', $result['bar']);
        $this->assertEquals(false, $result['mustsee']);
    }

    public function testGetErrorsOnPage () {
        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'readonly' => true,
                    'page' => 1
                 ),
                 'lastname' => array(
                    'readonly' => false,
                    'page' => 2
                 ),
                 'company' => array(
                    'type' => 'string',
                    'page' => 2
                 ),
                 'mustsee' => array(
                    'type' => 'bool',
                    'checkbox' => true,
                    'page' => 2,
                    'required' => true
                 ),
                 'bar' => array(
                    'type' => 'string',
                    'page' => 3,
                    'depends' => 'company'
                 )
             )
        );

        $values = array('lastname' => 'foo', 'company' => 'bar');

        $this->form->setWritableValuesOnPage($values, 2);

        $errors = $this->form->validate()->getErrorsByPage();

        $this->assertArrayNotHasKey(1, $errors);
        $this->assertArrayNotHasKey(2, $errors);
        $this->assertArrayHasKey(3, $errors);
        $this->assertEquals(1, count($errors));
        $this->assertEquals(1, count($errors[3]['bar']));
    }

    public function testSetWritableValuesOnPageError () {
        $this->setExpectedException('Liquid_Form_Exception');

        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'readonly' => true,
                    'page' => 1
                 ),
                 'lastname' => array(
                    'readonly' => false,
                    'page' => 2
                 ),
                 'company' => array(
                    'type' => 'string',
                    'page' => 2
                 ),
                 'bar' => array(
                    'default' => 'foo',
                    'type' => 'string',
                    'page' => 3
                 )
             )
        );

        $values = array('lastname' => 'foo');

        $this->form->setWritableValuesOnPage($values, 2);
    }

    public function testValidationError () {
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
                    'type' => 'list',
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
                    'type' => 'list',
                    'options' => array(
                        'len' => 'Lenovo',
                        'hp' => 'HP',
                        'apple' => 'Apple'
                    )
                 ),
                 'sports' => array(
                    'caption' => 'Sport',
                    'type' => 'list',
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

        $this->form->validate();

        $errors = $this->form->getErrors();

        $this->assertEquals(8, count($errors));
    }

    public function testValidationSuccess () {
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
                    'type' => 'list',
                    'options' => array(
                        'bmw' => 'BMW',
                        'hond' => 'Honda',
                        'gmc' => 'General Motors'
                    ),
                    'min' => 1,
                    'max' => 2
                 ),
                 'computers' => array(
                    'type' => 'list',
                    'options' => array(
                        'len' => 'Lenovo',
                        'hp' => 'HP',
                        'apple' => 'Apple'
                    )
                 ),
                 'nothing' => array(
                    'type' => 'list',
                    'checkbox' => true,
                    'options' => array(
                        'len' => 'Lenovo',
                        'hp' => 'HP',
                        'apple' => 'Apple'
                    )
                 ),
                 'sports' => array(
                    'caption' => 'Sport',
                    'options' => array(
                        'soccer' => 'Fussball',
                        'chess' => 'Schach',
                        'dance' => 'Tanzen'
                    )
                 ),
                 'fun' => array(
                    'default' => 'for_me'
                 ),
                 'spacetravel' => array(
                    'depends' => 'sports',
                    'depends_value' => 'chess'
                 ),
                 'drink' => array(
                    'caption' => 'Trinken',
                    'depends' => 'sports',
                    'depends_last_option' => true
                 ),
                 'vehicle' => array(
                    'caption' => 'Fahrzeug',
                    'type' => 'float'
                 ),
                 'drive' => array(
                    'depends' => 'vehicle',
                    'depends_value_empty' => true
                 ),
                 'between' => array(
                    'type' => 'Between',
                    'type_params' => array('min' => 1, 'max' => 10)
                 )
             )
        );

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
            'vehicle' => '1,1234',
            'between' => 2
        );

        $this->form->setWritableValues($values);

        $this->form->validate();

        $this->assertEquals('for_me', $this->form->fun);

        $errors = $this->form->getErrors();

        $this->assertEquals(0, count($errors));
    }

    public function testDefinedWritableValuesSuccess () {
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
                    'type' => 'list',
                    'options' => array(
                        'bmw' => 'BMW',
                        'hond' => 'Honda',
                        'gmc' => 'General Motors'
                    ),
                    'min' => 1,
                    'max' => 2
                 ),
                 'computers' => array(
                    'type' => 'list',
                    'checkbox' => true,
                    'options' => array(
                        'len' => 'Lenovo',
                        'hp' => 'HP',
                        'apple' => 'Apple'
                    )
                 ),
                 'more_computers' => array(
                    'type' => 'list',
                    'checkbox' => true,
                    'options' => array(
                        0 => 'Lenovo',
                        1 => 'HP',
                        2 => 'Apple'
                    ),
                    'min' => 2
                 ),
                 'nothing' => array(
                    'type' => 'list',
                    'checkbox' => true,
                    'options' => array(
                        'len' => 'Lenovo',
                        'hp' => 'HP',
                        'apple' => 'Apple'
                    )
                 ),
                 'sports' => array(
                    'caption' => 'Sport',
                    'options' => array(
                        'soccer' => 'Fussball',
                        'chess' => 'Schach',
                        'dance' => 'Tanzen'
                    )
                 ),
                 'spacetravel' => array(
                    'depends' => 'sports',
                    'depends_value' => 'chess'
                 ),
                 'drink' => array(
                    'caption' => 'Trinken',
                    'depends' => 'sports',
                    'depends_last_option' => true
                 ),
                 'vehicle' => array(
                    'caption' => 'Fahrzeug',
                    'type' => 'float'
                 ),
                 'between' => array(
                    'type' => 'Between',
                    'type_params' => array('min' => 1, 'max' => 10)
                 )
             )
        );

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
            'more_computers' => array(
                '1' => 1,
                '2' => 2
            ),
            'sports' => 'dance',
            'spacetravel' => 'hello',
            'drink' => true,
            'vehicle' => '1,1234',
            'between' => 2
        );

        $this->form->setDefinedWritableValues($values);

        $this->assertEquals(array(), $this->form->nothing);

        $this->form->validate();

        $errors = $this->form->getErrors();

        $this->assertEquals(0, count($errors));

        $values = array(
            'firstname' => 'Michael',
            'temperature' => 31,
            'email' => 'xyz@ibm.com',
            'cars' => array(
                'bmw',
                'hond'
            ),
            'computers' => array(
                'apple' => 1
            ),
            'more_computers' => array(
                1, 2
            ),
            'sports' => 'dance',
            'spacetravel' => 'hello',
            'drink' => true,
            'vehicle' => '1,1234',
            'between' => 2
        );

        $this->form->setDefinedWritableValues($values);

        $this->assertEquals(array(), $this->form->nothing);

        $this->form->validate();

        $errors = $this->form->getErrors();

        $this->assertEquals(0, count($errors));
    }

    public function testChangeDefinitionException () {
        $this->setExpectedException('Liquid_Form_Exception');
        $this->form->changeDefinition('foo', array('min' => 3));
    }

    public function testChangeDefinition () {

        $this->form->addDefinition('foo', array('min' => 3));
        $this->form->foo = 'abc';
        $this->assertEquals(0, count($this->form->validate()->getErrors()));
        $this->form->clearErrors();

        $this->form->foo = 'ab';
        $this->assertEquals(1, count($this->form->validate()->getErrors()));
        $this->form->clearErrors();

        $this->form->changeDefinition('foo', array('min' => 5));

        $this->form->foo = 'abcd';
        $this->assertEquals(1, count($this->form->validate()->getErrors()));
        $this->form->clearErrors();

        $this->form->foo = 'abcde';
        $this->assertEquals(0, count($this->form->validate()->getErrors()));
        $this->form->clearErrors();
    }

    public function testValidateMax () {
        $this->form->setDefinition(
            array(
                'birthday' => array(
                    'type' => 'date',
                    'max' => 0
                 ),
                 'otherday' => array(
                    'type' => 'date',
                    'max' => '1981-22-01'
                 ),
                 'number' => array(
                    'type' => 'numeric',
                    'max' => 299
                 ),
                 'string' => array(
                    'type' => 'string',
                    'max' => 10
                 )
             )
        );

        $values = array('birthday' => date('d.m.Y', time() + 60*60*24), 'otherday' => '22.01.1990', 'number' => 300, 'string' => 'abcdefghijk');

        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(4, count($errors));

        $this->form->clearErrors();

        $values = array('birthday' => date('d.m.Y', time() - 60*60*24), 'otherday' => '22.01.1960', 'number' => 299, 'string' => 'abc');

        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(0, count($errors));
    }

    public function testValidateMin () {
        $this->form->setDefinition(
            array(
                'birthday' => array(
                    'type' => 'date',
                    'min' => 0
                 ),
                 'otherday' => array(
                    'type' => 'date',
                    'min' => '1981-22-01'
                 ),
                 'number' => array(
                    'type' => 'numeric',
                    'min' => 299
                 ),
                 'string' => array(
                    'type' => 'string',
                    'min' => 10
                 )
             )
        );

        $values = array('birthday' => date('d.m.Y', time() - 60*60*24), 'otherday' => '22.01.1960', 'number' => 298, 'string' => 'abc');

        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(4, count($errors));

        $this->form->clearErrors();

        $values = array('birthday' => date('d.m.Y', time() + 60*60*24), 'otherday' => '22.01.1990', 'number' => 299, 'string' => 'abcdefghijk');

        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(0, count($errors));
    }
}
