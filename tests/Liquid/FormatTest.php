<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Format
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
require_once 'tests/_config.php';

require_once 'Liquid/Format.php';

class LiquidFormatTest extends PHPUnit_Framework_TestCase {
    public function setUp () {
        Liquid_Format::setLocale(new Zend_Locale('de_DE'));
    }
    
    public function tearDown() {
        Liquid_Format::setLocale();
    }
    
    public function testConvertDatetime () {
        $output = Liquid_Format::convert(Liquid_Format::DATETIME, '2010-10-11 17:08:21');
        $this->assertEquals('11.10.2010 17:08:21', $output);
    }
    
    public function testConvertDate () {
        $output = Liquid_Format::convert(Liquid_Format::DATE, '2010-10-11');
        $this->assertEquals('11.10.2010', $output);
    }

    public function testSqlDateException () {
        $this->setExpectedException('Liquid_Format_Exception');
        Liquid_Format::sql(Liquid_Format::DATE, new Zend_Locale());
    }
    
    public function testSqlDatetimeException () {
        $this->setExpectedException('Liquid_Format_Exception');
        Liquid_Format::sql(Liquid_Format::DATETIME, new Zend_Registry());
    }
    
    public function testConvertNumberException () {
        $this->assertEquals(null, Liquid_Format::convert('#.00', 1234));
    }

    public function testSqlDateFromEmptyValue () {
        $output = Liquid_Format::sql(Liquid_Format::DATE, '');
        $this->assertEquals(null, $output);

        $output = Liquid_Format::sql(Liquid_Format::DATE, null);
        $this->assertEquals(null, $output);

        $output = Liquid_Format::sql(Liquid_Format::DATE, 0);
        $this->assertEquals(null, $output);
    }

    public function testSqlDateFromLocaleFormat () {
        $output = Liquid_Format::sql(Liquid_Format::DATE, '11.10.2010');
        $this->assertEquals('2010-10-11', $output);
    }
    
    public function testSqlDateFromDbFormat () {
        $output = Liquid_Format::sql(Liquid_Format::DATE, '2010-10-11');
        $this->assertEquals('2010-10-11', $output);
    }

    public function testSqlDateFromDateTime () {
        $date = new DateTime('2010-10-11');
        $output = Liquid_Format::sql(Liquid_Format::DATE, $date);
        $this->assertEquals('2010-10-11', $output);

        $date = new DateTime('11.10.2010');
        $output = Liquid_Format::sql(Liquid_Format::DATE, $date);
        $this->assertEquals('2010-10-11', $output);
    }

    public function testSqlDateFromZendDate () {
        $zend = new Zend_Date('2010-10-11');
        $output = Liquid_Format::sql(Liquid_Format::DATE, $zend);
        $this->assertEquals('2010-10-11', $output);
        
        $zend = new Zend_Date('11.10.2010');
        $output = Liquid_Format::sql(Liquid_Format::DATE, $zend);
        $this->assertEquals('2010-10-11', $output);
    }
    
    public function testSqlDatetimeFromEmptyValue () {
        $output = Liquid_Format::sql(Liquid_Format::DATETIME, '');
        $this->assertEquals(null, $output);

        $output = Liquid_Format::sql(Liquid_Format::DATETIME, null);
        $this->assertEquals(null, $output);

        $output = Liquid_Format::sql(Liquid_Format::DATETIME, 0);
        $this->assertEquals(null, $output);
    }

    public function testSqlDatetimeFromLocaleFormat () {
        $output = Liquid_Format::sql(Liquid_Format::DATETIME, '11.10.2010 18:34:45');
        $this->assertEquals('2010-10-11 18:34:45', $output);
    }
    
    public function testSqlDatetimeFromDbFormat () {
        $output = Liquid_Format::sql(Liquid_Format::DATETIME, '2010-10-11 18:34:45');
        $this->assertEquals('2010-10-11 18:34:45', $output);
    }

    public function testSqlDatetimeFromDateTime () {
        $date = new DateTime('2010-10-11 18:34:45');
        $output = Liquid_Format::sql(Liquid_Format::DATETIME, $date);
        $this->assertEquals('2010-10-11 18:34:45', $output);

        $date = new DateTime('11.10.2010 18:34:45');
        $output = Liquid_Format::sql(Liquid_Format::DATETIME, $date);
        $this->assertEquals('2010-10-11 18:34:45', $output);
    }

    public function testSqlDatetimeFromZendDate () {
        $zend = new Zend_Date('2010-10-11 18:34:45');
        $output = Liquid_Format::sql(Liquid_Format::DATETIME, $zend);
        $this->assertEquals('2010-10-11 18:34:45', $output);
        
        $zend = new Zend_Date('11.10.2010 18:34:45');
        $output = Liquid_Format::sql(Liquid_Format::DATETIME, $zend);
        $this->assertEquals('2010-10-11 18:34:45', $output);
    }

    public function testConvertFloat () {
        $output = Liquid_Format::convert(Liquid_Format::FLOAT, '11.345');
        $this->assertEquals(11.345, $output);
        
        $output = Liquid_Format::convert(Liquid_Format::FLOAT, 11.345);
        $this->assertEquals(11.345, $output);
    }

    public function testSqlFloat () {
        $output = Liquid_Format::sql(Liquid_Format::FLOAT, '11,345');
        $this->assertEquals(11.345, $output);
        
        $output = Liquid_Format::sql(Liquid_Format::FLOAT, '11.345');
        $this->assertEquals(11.345, $output);

        $output = Liquid_Format::sql(Liquid_Format::FLOAT, 11.345);
        $this->assertEquals(11.345, $output);
    }
    
    public function testSqlAlphanumeric () {
        $output = Liquid_Format::sql(Liquid_Format::ALPHANUMERIC, 'ALKDFHE 1234567890 ;"[_+)(*&^%$');
        $this->assertEquals('ALKDFHE 1234567890 _', $output);
    }
    
    public function testConvertAlphanumeric () {
        $output = Liquid_Format::convert(Liquid_Format::ALPHANUMERIC, 'ALKDFHE 1234567890 ;"[_+)(*&^%$');
        $this->assertEquals('ALKDFHE 1234567890 _', $output);
    }
    
    public function testSqlNumbers () {
        $output = Liquid_Format::sql('whatever', '11,345');
        $this->assertEquals(11.345, $output);
        
        $output = Liquid_Format::sql('whatever', '12.311,345');
        $this->assertEquals(12311.345, $output);
        
        $output = Liquid_Format::sql('whatever', '11.345');
        $this->assertEquals(11.345, $output);

        $output = Liquid_Format::sql('whatever', 11.345);
        $this->assertEquals(11.345, $output);
    }
    
    public function testConvertNumbers () {
        $output = Liquid_Format::convert('#,##0.00', 840293411.345);
        $this->assertEquals('840.293.411,34', $output);
        
        $output = Liquid_Format::convert('#0.00', 11.345);
        $this->assertEquals('11,34', $output);

        $output = Liquid_Format::convert('#0.000', 11.345);
        $this->assertEquals('11,345', $output);
    }
    
    public function testSqlJSON () {
        $output = Liquid_Format::sql(Liquid_Format::JSON, array('foo' => 'bar'));
        $this->assertEquals(array('foo' => 'bar'), Zend_Json::decode($output));
    }
    
    public function testConvertJSON () {
        $output = Liquid_Format::sql(Liquid_Format::JSON, array('foo' => 'bar'));
        $output = Liquid_Format::convert(Liquid_Format::JSON, $output);
        $this->assertEquals(array('foo' => 'bar'), $output);
    }
}
