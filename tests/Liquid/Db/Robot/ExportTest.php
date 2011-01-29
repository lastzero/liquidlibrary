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

require_once 'Liquid/Db/Robot/Export.php';

class LiquidDbRobotExportTest extends PHPUnit_Framework_TestCase {
    public function testParseTables () {
        $robot = new Liquid_Db_Robot_Converter(dirname(__FILE__). '/_scripts/export.xml');
        $robot->parseTables();
        $this->assertEquals(23, count($robot->myTables));
        
        $robot = new Liquid_Db_Robot_Converter(dirname(__FILE__). '/_scripts/export2.xml');
        $robot->parseTables();
        $this->assertEquals(7, count($robot->myTables));
    }
}
