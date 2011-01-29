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

class Liquid_Db_Structure {
    private static $db = false;
    protected $db = null;

    protected $tableName = null;
    protected $structure = array();
    
    // Static functions are there to handle MANY rows
    
    public static function setDefaultAdapter (Zend_Db_Adapter_Abstract $db) {
        self::$db = $db;
    }
    
    protected static function getAdapter () {
        if(self::$db) {
            return self::$db;
        }
        
        return Zend_Registry::get('db');
    }    
        
    // Other functions are there to handle ONE row
    
    public function __construct (Zend_Db_Adapter_Abstract $db = null) {
        if(!$db) {
            $this->db = self::getAdapter();
        } else {
            $this->db = $db;
        }       
    }
    
    public function repairTable ($table_name, $table_prefix = '') {
        $info = $this->db->describeTable($this->tableName);

	    if ($info === FALSE) {
		    // Create a new table
		    $field_list = array();
		
		    foreach($this->structure as $table_field => $table_type) {
			    if($table_field != '') {
				    $field_list[] = $this->db->quoteIdentifier($table_field).' '.$table_type;
				    }
			    else {
				    $field_list[] = $table_type;
				    }				
			    }
		
		    return $this->db->query('CREATE TABLE '.$this->db->quoteIdentifier($this->tableName).' ('.implode(',', $field_list).')');
		    }
	    else {
		    // Extend existing table
		    foreach($info as $field) {
			    $field_names[$field['name']] = $field['type'];
			}
		
		    foreach($this->structure as $table_field => $table_type) {
			    if($table_field != '' && !array_key_exists($table_field, $field_names)) {
				    $this->db->query('ALTER TABLE '.$this->db->quoteIdentifier($this->tableName).
					    ' ADD '.$this->db->quoteIdentifier($table_field).
					    $table_type);
		    }
		}
	}
}
