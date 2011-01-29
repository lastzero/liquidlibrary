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

require_once 'Liquid/Db.php';

class Liquid_Db_Dao extends Liquid_Db {
    protected $_db = null;
    protected $_data = array();
    protected $_originalData = array();
    protected $_tableName = null;
    protected $_primaryKey = 'id';
    protected $_fieldMap = array(); // 'db_field' => 'object_field'
    protected $_formatMap = array(); // 'db_field' => Liquid_Format::TYPE
    protected $_valueMap = array(); // 'object_field' => 'db_field'

    public function __construct (Zend_Db_Adapter_Abstract $db = null) {
        if(!$db) {
            $this->_db = self::getAdapter();
        } else {
            $this->_db = $db;
        }

        if(empty($this->_valueMap)) {
            $this->_valueMap = array_flip($this->_fieldMap);
        }
    }

    public function __get ($name) {
        if(isset($this->_valueMap[$name])) {
            $key = $this->_valueMap[$name];
        }else {
            $key = $name;
        }

        if(!is_array($this->_primaryKey) && $key == $this->_primaryKey) {
            return $this->getId();
        }

        $result = $this->_data[$key];

        if(isset($this->_formatMap[$key])) {
            $result = Liquid_Format::convert($this->_formatMap[$key], $result);
        }

        return $result;
    }

    public function __set ($name, $value) {
        if(isset($this->_valueMap[$name])) {
            $key = $this->_valueMap[$name];
        } else {
            $key = $name;
        }

        if(isset($this->_formatMap[$key])) {
            $value = Liquid_Format::sql($this->_formatMap[$key], $value);
        }

        if(!is_array($this->_primaryKey) && $key == $this->_primaryKey) {
            return $this->setId($value);
        }

        $this->_data[$key] = $value;
    }

    public function setData ($data) {
        $this->_data = $data;
        $this->_originalData = $data;
    }

    public function setValues ($data) {
        foreach($data as $name => $value) {
            $this->$name = $value;
        }
    }

    public function getValues () {
        $result = array();

        foreach($this->_data as $name => $value) {
            if(isset($this->_fieldMap[$name])) {
                $key = $this->_fieldMap[$name];

                if(isset($this->_formatMap[$name])) {
                    $value = Liquid_Format::convert($this->_formatMap[$name], $value);
                }

                $result[$key] = $value;
            } else {
                if(isset($this->_formatMap[$name])) {
                    $value = Liquid_Format::convert($this->_formatMap[$name], $value);
                }

                $result[$name] = $value;
            }
        }

        return $result;
    }

    public function find ($id) {
        $select = $this->_db->select();
        $select->from($this->_tableName);

        if(is_array($id)) {
            foreach($id as $key => $val) {
               $select->where($this->_db->quoteIdentifier($key) . ' = ?', $val);
            }
        } else {
            $select->where($this->_db->quoteIdentifier($this->_primaryKey) . ' = ?', $id);
        }

        $data = $this->_db->fetchRow($select);

        if(!is_array($data)) {
            throw new Liquid_Db_Dao_Exception('No matching row found');
        }

        $this->setData($data);
    }

    public function exists ($id) {
        $select = $this->_db->select();
        $select->from($this->_tableName);

        if(is_array($id)) {
            foreach($id as $key => $val) {
               $select->where($this->_db->quoteIdentifier($key) . ' = ?', $val);
            }
        } else {
            $select->where($this->_db->quoteIdentifier($this->_primaryKey) . ' = ?', $id);
        }

        $data = $this->_db->fetchRow($select);

        return is_array($data);
    }

    public function insert () {
        $this->_db->insert($this->_tableName, $this->_data);

        if(!is_array($this->_primaryKey) && !isset($this->_data[$this->_primaryKey])) {
            $this->setId($this->_db->lastInsertId());
        }
    }

    protected function getWhere () {
        if(is_array($this->_primaryKey)) {
            $list = array();

            foreach($this->_primaryKey as $key) {
                $list[] = $this->_db->quoteIdentifier($key) . ' = ' . $this->_db->quote($this->_data[$key]);
            }

            $where = implode(' AND ', $list);
        } else {
            $where = $this->_db->quoteIdentifier($this->_primaryKey) . ' = ' . $this->_db->quote($this->getId());
        }

        return $where;
    }

    public function update () {
        $fields = array();

        foreach($this->_data as $key => $value) {
            if((!isset($_originalData[$key]) || $_originalData[$key] != $value)
                    && ((!is_array($this->_primaryKey) && $key != $this->_primaryKey)
                        || (is_array($this->_primaryKey) && !in_array($key, $this->_primaryKey)))) {
                $fields[$key] = $value;
            }
        }

        if(count($fields) > 0) {
            return $this->_db->update($this->_tableName, $fields, $this->getWhere());
        }
    }

    public function getId () {
        if(!is_array($this->_primaryKey) && isset($this->_data[$this->_primaryKey])) {
            return $this->_data[$this->_primaryKey];
        } elseif(is_array($this->_primaryKey)) {
            $result = array();

            foreach($this->_primaryKey as $key) {
                if(!isset($this->_data[$key])) {
                    throw new Liquid_Db_Dao_Exception('Primary key not complete: ' . $key);
                }

                $result[$key] = $this->_data[$key];
            }

            return $result;
        }

        throw new Liquid_Db_Dao_Exception('No Primary ID set for this object');
    }

    public function setId ($id) {
        if(!is_array($this->_primaryKey) && !isset($this->_data[$this->_primaryKey])) {
            $this->_data[$this->_primaryKey] = $id;
        } elseif(is_array($this->_primaryKey)) {
            foreach($this->_primaryKey as $key) {
                if(!isset($id[$key])) {
                    throw new Liquid_Db_Dao_Exception('Primary key not complete: ' . $key);
                }

                 $this->_data[$key] = $id[$key];
            }
        } else {
            throw new Liquid_Db_Dao_Exception('Can not set Primary ID again');
        }
    }

    public function delete () {
        return $this->_db->delete($this->_tableName, $this->getWhere());
    }

    public function findAll ($cond = null, $wrapResult = true) {
        $select = $this->_db->select();
        $select->from($this->_tableName);

        if(is_array($cond)) {
            foreach($cond as $key => $val) {
                if(is_int($key)) {
                    $select->where($val);
                } else {
                    $select->where($this->_db->quoteIdentifier($key) . ' = ?', $val);
                }
            }
        }

        $rows = $this->_db->fetchAll($select);

        if ($wrapResult) {
            return $this->wrapAll($rows);
        } else {
            return $rows;
        }
    }

    public function wrapAll ($rows) {
        $class = get_class($this);
        $result = array();

        foreach($rows as $row) {
            $dao = new $class($this->_db);
            $dao->setData($row);
            $result[] = $dao;
        }

        return $result;
    }
}
