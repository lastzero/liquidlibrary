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
 * @copyright  Copyright (c) 2005-2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://www.zend.com/license/framework/1_0.txt Zend Framework License version 1.0
 */

/**
 * Liquid_Db_Statement_Odbtp_Mssql
 *
 * @package    Zend_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://www.zend.com/license/framework/1_0.txt Zend Framework License version 1.0
 * @author 	   Michael Mayer <michael.mayer@zend.com>
 */
class Liquid_Db_Statement_Odbtp_Mssql extends Zend_Db_Statement
{
  /**
	 * statement resource handle
	 */
  protected $_stmt;

  /**
  	* column names
    */
  protected $_keys;

  /**
     * fetched result values
     */
  protected $_values;

  /**
     *
     * @return integer number of rows updated
     */
  public function rowCount()
  {
    $connection = $this->_adapter->getConnection();

    if (!$connection) {
      return false;
    }

    if(!$this->_stmt) {
      return false;
    }

    $num = odbtp_affected_rows($this->_stmt);

    if ($num === false) {
      throw new Liquid_Db_Statement_Odbtp_Exception($this->errorInfo(), $this->errorCode());
    }

    return $num;
  }

  /**
     * closes the cursor, allowing the statement to be executed again
     */
  public function closeCursor()
  {
    if(!$this->_stmt) {
      return false;
    }

    odbtp_free_query($this->_stmt);
    $this->_stmt = false;
  }


  /**
   	* returns the number of columns in the result set
	*
	* @return integer
    */
  public function columnCount()
  {
    if (!$this->_stmt) {
      return false;
    }
    return odbtp_num_fields($this->_stmt);
  }


  /**
     * retrieves a sql state, if any, from the statement
     *
     * @return string the error code
     */
  public function errorCode()
  {
    $connection = $this->_adapter->getConnection();

    if (!$connection) {
      return false;
    }

    return odbtp_last_error_code($connection);
  }


  /**
     * retrieves a error msg, if any, from the statement
     *
     * @return string the statement error message
     */
  public function errorInfo()
  {
    $connection = $this->_adapter->getConnection();

    if (!$connection) {
      return false;
    }

    return odbtp_last_error($connection);
  }


  /**
     * executes a prepared statement
     *
     * @return void
     */
  public function execute(array $params = array())
  {
    $connection = $this->_adapter->getConnection();

    if (!$this->_stmt) {
      // $connection = $this->_adapter->getConnection();
      $sql = $this->_joinSql();
      $this->_stmt = odbtp_prepare($sql, $connection);
    }

    if(!$this->_stmt) {
      throw new Liquid_Db_Statement_Odbtp_Exception($this->errorInfo(), $this->errorCode());
    }

    if ($params && is_array($params) && count($params) > 0) {

      $error = false;
      $i = 0;

      foreach (array_keys($params) as $name) {
        $i++;

        ob_start();

        odbtp_input($this->_stmt, $i);

        $inputError = ob_get_contents();

        ob_end_clean();

        if (trim($inputError) != '') {
          throw new Liquid_Db_Statement_Odbtp_Exception(trim($inputError));
        }

        if (!odbtp_set($this->_stmt, $i, $params[$name])) {
          $error = true;
          break;
        }

      }
      if ($error) {
        throw new Liquid_Db_Statement_Odbtp_Exception($this->errorInfo(), $this->errorCode());
      }
    }

    $error = '';

    $success = odbtp_execute($this->_stmt);

    if (!$success) {
      throw new Liquid_Db_Statement_Odbtp_Exception($this->errorInfo(), $this->errorCode());
    }

    $this->_keys = array();
    if ($field_num = $this->columnCount()) {
      for ($i = 0; $i < $field_num; $i++) {
        $name = odbtp_field_name($this->_stmt, $i);
        $this->_keys[] = $name;
      }
    }

    $this->_values = array();
    if ($this->_keys) {
      $this->_values = array_fill(0, count($this->_keys), null);
    }

  }

  public function bindParam($parameter, &$variable, $type = null, $length = null, $options = null)
  {
    $connection = $this->_adapter->getConnection();

    Zend_Db_Statement::bindParam($parameter, $variable, $length, $options);

    if($type === null) {
      $type = ODB_CHAR;
    }
    
    return odbtp_attach_param($this->_stmt, $parameter, $variable);
  }
  
  
  /**
     * fetches a row from a result set
     */
  public function fetch($style = null, $cursor = null, $offset = null)
  {
    if (!$this->_stmt){
      return false;
    }
    
    if ($style === null) {
      $style = $this->_fetchMode;
    }
    
    switch ($style) {
      case Zend_Db::FETCH_NUM :
        $fetch_function = "odbtp_fetch_row";
        break;
      case Zend_Db::FETCH_ASSOC :
        $fetch_function = "odbtp_fetch_assoc";
        break;
      case Zend_Db::FETCH_BOTH :
        $fetch_function = "odbtp_fetch_array";
        break;
      case Zend_Db::FETCH_OBJ :
        $fetch_function = "odbtp_fetch_object";
        break;
      default:
        throw new Liquid_Db_Statement_Odbtp_Exception('invalid fetch mode specified');
        break;
    }
    
    $row = $fetch_function($this->_stmt);
    return $row;
  }
  
  /**
     * Prepare statement handle
     */
  public function _prepare($sql)
  {
    // Zend_Db_Statement::_prepare($sql);
    
    $connection = $this->_adapter->getConnection();
    
    $this->_stmt = odbtp_allocate_query($connection);
    
    if (!$this->_stmt) {
      throw new Liquid_Db_Statement_Odbtp_Exception($this->errorInfo(), $this->errorCode());
    }
    
    return odbtp_prepare($sql, $this->_stmt);
  }
  
  public function fetchObject($class = 'stdClass', array $config = array())
  {
    $obj = fetch(Zend_Db::FETCH_OBJ);
    
    return $obj;
  }

  /**
     * fetches an array containing all of the rows from a result set
     */
  public function fetchAll($style = null, $col = null)
  {
    $data = array();
    if ($col === null) {
      while ($row = $this->fetch($style)) {
        $data[] = $row;
      }
    } else {
      while ($val = $this->fetchColumn($col)) {
        $data = $val;
      }
    }
    return $data;
  }
  
  
  /**
     * retrieves the next rowset (result set)
     * @todo not familiar with how to do nextrowset
     */
  public function nextRowset()
  {
    require_once 'Zend/Db/Statement/Odbtp/Exception.php';
    throw new Liquid_Db_Statement_Odbtp_Exception(__FUNCTION__ . ' not implemented');
  }
}
