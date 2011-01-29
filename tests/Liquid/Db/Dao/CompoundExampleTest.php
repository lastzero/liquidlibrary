<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Db
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */

require_once 'tests/_config.php';

require_once 'Liquid/Db/Dao/CompoundExample.php';

class LiquidDbDaoCompoundExampleTest extends PHPUnit_Framework_TestCase {
    public function setUp () {
        $this->dao = new Liquid_Db_Dao_CompoundExample();
        $this->dao->createTestTable();    
    }
    
    public function testInsert() {
        $this->dao->code = 123;
        $this->dao->year = 2010;
        $this->dao->name = 'foo';
        $this->dao->value = 'foo';
        $this->dao->insert();       
    }
    
    public function testUpdate() {
        $this->dao->code = 123;
        $this->dao->year = 2010;
        $this->dao->name = 'foo';
        $this->dao->value = 'foo';
        $this->dao->insert();   
        
        $this->dao->value = 'bar'; 
        $result = $this->dao->update();    
        $this->assertEquals(1, $result);        
    }
        
    public function testFind () {        
        $this->dao->code = 123;
        $this->dao->year = 2010;
        $this->dao->name = 'foo';
        $this->dao->value = 'bar';
        $this->dao->insert();   
        
        $dao = new Liquid_Db_Dao_CompoundExample();
        $dao->find(array('code' => 123, 'year' => 2010));
        
        $this->assertEquals(123, $dao->code);
        $this->assertEquals(2010, $dao->year);
        $this->assertEquals('foo', $dao->name);
        $this->assertEquals('bar', $dao->value);        
    }
    
    public function testDelete () {
        $this->dao->code = 123;
        $this->dao->year = 2010;
        $this->dao->name = 'foo';
        $this->dao->value = 'foo';
        $this->dao->insert();   
        $result = $this->dao->delete();
        $this->assertEquals(1, $result);        
        
        $this->dao->insert();   
        
        $dao = new Liquid_Db_Dao_CompoundExample();
        $dao->find(array('code' => 123, 'year' => 2010));
        $result = $dao->delete();   
        $this->assertEquals(1, $result);        
    }
    
    public function testGetId () {
        $this->dao->code = 123;
        $this->dao->year = 2010;
        $this->dao->name = 'foo';
        $this->dao->value = 'foo';
        $this->dao->insert();
        
        $id = $this->dao->getId(); 
        
        $this->assertArrayHasKey('code', $id);
        $this->assertArrayHasKey('year', $id);
        $this->assertEquals(123, $id['code']);
        $this->assertEquals(2010, $id['year']);
    }

    public function testSetId () {
        $this->dao->setId(array('code' => 789, 'year' => 1981));
        $this->dao->name = 'name';
        $this->doa->value = 'value';
        $this->dao->insert();
    }

    public function tearDown () {
        $this->dao->dropTestTable();    
    }
}
