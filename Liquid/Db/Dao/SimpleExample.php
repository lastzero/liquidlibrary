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
 
class Liquid_Db_Dao_SimpleExample extends Liquid_Db_Dao {
    protected $_tableName = '__liquid_db_simple';
    protected $_primaryKey = 'id';
    
    public function createTestTable () {
        $this->_db->query('DROP TABLE IF EXISTS __liquid_db_simple');
        $this->_db->query('
            CREATE TABLE `__liquid_db_simple` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(20)  NOT NULL,
              `value` VARCHAR(30) ,
              `created` TIMESTAMP  NOT NULL,
              PRIMARY KEY (`id`)
            )'
        );        
    }

    public function dropTestTable () {
        $this->_db->query('DROP TABLE __liquid_db_simple');
    }
}
