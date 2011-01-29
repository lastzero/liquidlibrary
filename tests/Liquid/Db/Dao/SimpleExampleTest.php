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

require_once 'Liquid/Db/Dao/SimpleExample.php';

class LiquidDbDaoSimpleExampleTest extends PHPUnit_Framework_TestCase {
    public function setUp () {
        $this->dao = new Liquid_Db_Dao_SimpleExample();
        $this->dao->createTestTable();    
    }
    
    public function testInsert() {
        $this->dao->id = 123;
        $this->dao->name = 'foo';
        $this->dao->value = 'foo';
        $this->dao->insert();      
        
        $this->assertEquals(123, $this->dao->getId());
    }
    
    public function testUpdate() {
        $this->dao->id = 123;
        $this->dao->name = 'foo';
        $this->dao->value = 'foo';
        $this->dao->insert();   
        
        $this->dao->value = 'bar'; 
        $result = $this->dao->update();    
        $this->assertEquals(1, $result);        
    }
        
    public function testFind () {        
        $this->dao->name = 'foo';
        $this->dao->value = 'bar';
        $this->dao->insert();  
        
        $id = $this->dao->getId(); 
        
        $dao = new Liquid_Db_Dao_SimpleExample();
        $dao->find($id);
        
        $this->assertEquals($id, $dao->id);
        $this->assertEquals('foo', $dao->name);
        $this->assertEquals('bar', $dao->value);        
    }
    
    public function testDelete () {
        $this->dao->id = 3;
        $this->dao->name = 'foo';
        $this->dao->value = 'foo';
        $this->dao->insert();   
        $result = $this->dao->delete();
        $this->assertEquals(1, $result);        
        
        $this->dao->insert();   
        
        $dao = new Liquid_Db_Dao_SimpleExample();
        $dao->find(3);
        $result = $dao->delete();   
        $this->assertEquals(1, $result);        
    }
    
    public function testGetId () {
        $this->dao->id = 1234;
        $this->dao->name = 'foo';
        $this->dao->value = 'foo';
        $this->dao->insert();
        
        $id = $this->dao->getId(); 
        
        $this->assertEquals(1234, $id);
    }

    public function testSetId () {
        $this->dao->setId(12347);
        $this->dao->name = 'name';
        $this->doa->value = 'value';
        $this->dao->insert();
    }
    
    public function tearDown () {
        $this->dao->dropTestTable();    
    }
}
