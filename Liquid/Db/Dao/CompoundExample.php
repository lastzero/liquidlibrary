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
 
require_once 'Liquid/Db/Dao.php'; 
 
class Liquid_Db_Dao_CompoundExample extends Liquid_Db_Dao {
    protected $_tableName = '__liquid_db_compound';
    protected $_primaryKey = array('code', 'year');
    
    public function createTestTable () {
        $this->_db->query('DROP TABLE IF EXISTS __liquid_db_compound');
        $this->_db->query('
            CREATE TABLE `__liquid_db_compound` (
              `code` INT UNSIGNED NOT NULL,
              `year` INT  NOT NULL,
              `name` VARCHAR(20)  NOT NULL,
              `value` VARCHAR(30)  NOT NULL,
              `created` TIMESTAMP  NOT NULL,
              PRIMARY KEY (`code`, `year`)
            )'
        );
    }

    public function dropTestTable () {
        $this->_db->query('DROP TABLE __liquid_db_compound');
    }
}
