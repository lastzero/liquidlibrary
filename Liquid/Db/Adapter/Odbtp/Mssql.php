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
 * Liquid_Db_Adapter_Odbtp_Mssql
 *
 * @package    Zend_Db
 * @copyright  Copyright (c) 2005-2006 Zend Technologies Inc. (http://www.zend.com)
 * @license    Zend Framework License version 1.0
 * @author 	   Michael Mayer <michael.mayer@zend.com>
 */

class Liquid_Db_Adapter_Odbtp_Mssql extends Zend_Db_Adapter_Abstract
{
  /**
     * User-provided configuration.
     *
     * Basic keys are:
     *
     * username   => (string)  Connect to the database as this username.
     * password   => (string)  Password associated with the username.
     * host       => (string)  What host to connect to (default 127.0.0.1)
     * dbname     => (string)  The name of the database to user
     * protocol   => (string)  Protocol to use, defaults to "TCPIP"
     * port       => (integer) Port number to use for TCP/IP if protocol is "TCPIP"
     * persistent => (boolean) Set TRUE to use a persistent connection (odbtp_pconnect)
     *
     * @var array
     */
  protected $_config = array(
  'dbname' 		=> null,
  'username' 		=> null,
  'password' 		=> null,
  'host' 			=> 'localhost',
  'port' 			=> '1433',
  'protocol' 		=> 'TCPIP',
  'persistent'	=> false
  );

  /**
     * Transaction type used for queries. Valid types are:
     * ODB_TXN_NONE
     * ODB_TXN_DEFAULT
     * ODB_TXN_READUNCOMMITTED
     * ODB_TXN_READCOMMITTED
     * ODB_TXN_REPEATABLEREAD
     * ODB_TXN_SERIALIZABLE
     *
     * @var int execution flag
     * @access protected
     */
  protected $_execute_mode = ODB_ATTR_TRANSACTIONS;

  /**
     * Table name of the last accessed table for an insert operation
     * This is a DB2-Adapter-specific member variable with the utmost
     * probability you might not find it in other adapters...
     *
     * @var string
     * @access protected
     */
  protected $_lastInsertTable = null;

  /**
     * Constructor.
     *
     * $config is an array of key/value pairs containing configuration
     * options.  These options are common to most adapters:
     *
     * dbname   	=> (string) The name of the database to user
     * username 	=> (string) Connect to the database as this username.
     * password 	=> (string) Password associated with the username.
     * host     	=> (string) What host to connect to, defaults to localhost
     * port     	=> (string) The port of the database, defaults to 50000
     * persistent 	=> (boolean) Whether to use a persistent connection or not,
     * 				   defaults to false
     * protocol 	=> (string) The network protocol, defaults to TCPIP
     * options  	=> (array)  Other database options such as,
     * 				   autocommit, case, and cursor options
     *
     * @param array $config An array of configuration keys.
     */
  public function __construct($config)
  {
    // make sure the config array exists
    if (! is_array($config)) {
      throw new Zend_Db_Adapter_Exception('must pass a config array');
    }

    // we need at least a dbname, a user and a password
    if (! array_key_exists('password', $config) ||
    ! array_key_exists('username', $config) ||
    ! array_key_exists('dbname', $config)) {
      throw new Zend_Db_Adapter_Exception('config array must have at least a username, a password, and a database name');
    }

    // keep the config
    $this->_config = array_merge($this->_config, (array) $config);

    // create a profiler object
    $enabled = false;
    if (array_key_exists('profiler', $this->_config)) {
      $enabled = (bool) $this->_config['profiler'];
      unset($this->_config['profiler']);
    }

    $this->_profiler = new Zend_Db_Profiler($enabled);
  }

  /**
    * Creates a connection resource.
 	*
    * @return void
    */
  protected function _connect()
  {
    if (is_resource($this->_connection)) {
      // connection already exists
      return;
    }

    if($this->_config['persistent']) {
      // use persistent connection
      $conn_func_name = 'odbtp_connect';
    } else {
      // use "normal" connection
      $conn_func_name = 'odbtp_connect';
    }

    if (!isset($this->_config['options'])) {
      // config options were not set, so set it to an empty array
      $this->_config['options'] = array();
    }

    if (!isset($this->_config['options']['autocommit'])) {
      // set execution mode
      $this->_config['options']['autocommit'] = &$this->_execute_mode;
    }
    
    $dbname = 'DRIVER={SQL Server}' .
      ';DATABASE='	. $this->_config['dbname'] .
      ';SERVER=' 	. $this->_config['server'] .
      ';UID=' 		. $this->_config['username'] .
      ';PWD=' 		. $this->_config['password'] .';';
    $this->_connection = $conn_func_name($this->_config['host'], $dbname);
    
    // check the connection
    if (!$this->_connection) {
      throw new Liquid_Db_Adapter_Odbtp_Exception(odbtp_get_error($this->_connection));
    }
  }

  /**
     * Returns an SQL statement for preparation.
     *
     * @param string $sql The SQL statement with placeholders.
     * @return Zend_Db_Statement_Odbtp
     */
  public function prepare($sql)
  {
    $this->_connect();
    $stmt = new Liquid_Db_Statement_Odbtp_Mssql($this, $sql);
    $stmt->setFetchMode($this->_fetchMode);
    return $stmt;
  }

  /**
	* Gets the execution mode
	*
	* @return int the execution mode
	*/
  public function _getExecuteMode()
  {
    return $this->_execute_mode;
  }

  public function _setExecuteMode($mode)
  {
    switch ($mode) {
      case ODB_TXN_NONE:
      case ODB_TXN_READUNCOMMITTED:
      case ODB_TXN_READCOMMITTED:
      case ODB_TXN_REPEATABLEREAD:
      case ODB_TXN_SERIALIZABLE:
      case ODB_TXN_DEFAULT:
        $this->_execute_mode = $mode;
        odbtp_autocommit($this->_connection, $mode);
        break;
      default:
        throw new Liquid_Db_Adapter_Odbtp_Exception("execution mode not supported");
        break;
    }
  }

  /**
     * Returns a list of the tables in the database.
     *
     * @return array
     */
  public function listTables()
  {
    if (!$this->_connection) {
      $this->_connect();
    }
    // take the most general case and assume no z/OS
    // since listTables() takes no parameters

    $stmt = odbtp_prepare("SELECT name FROM sysobjects WHERE type = 'U' ORDER BY name", $this->_connection);

    $stmt->execute();

    $tables = array();

    while( $tables[] = odbtp_fetch_assoc($stmt));

    return $tables;
  }

  /**
     *
     * Returns the column descriptions for a table.
     * @param string schema.tablename or just tablename
     * @return array
     */
  public function describeTable($table, $schemaName = null)
  {
    $sql = "exec sp_columns @table_name = " . $this->quoteIdentifier($table);
    $result = $this->fetchAll($sql);
    $descr = array();
    foreach ($result as $key => $val) {
      if (strstr($val['TYPE_NAME'], ' ')) {
        list($type, $identity) = explode(' ', $val['TYPE_NAME']);
      } else {
        $type = $val['TYPE_NAME'];
        $identity = '';
      }

      if ($type == 'varchar') {
        // need to add length to the type so we are compatible with
        // Zend_Db_Adapter_Pdo_Mysql!
        $type .= '('.$val['LENGTH'].')';
      }

      $descr[$val['COLUMN_NAME']] = array(
      'name'    => $val['COLUMN_NAME'],
      'type'    => $type,
      'notnull' => (bool) ($val['IS_NULLABLE'] === 'NO'),
      'default' => $val['COLUMN_DEF'],
      'primary' => (strtolower($identity) == 'identity'),
      );
    }
    return $descr;
  }

  /**
     * Gets the last inserted ID.
     *
     * @param  string $tableName   name of table associated with sequence
     * @param  string $primaryKey  primary key in $tableName (not used in this adapter)
     * @todo   can we skip the select COLNAME query,
     *         if primaryKey is available?
     * @return integer
     */

  public function lastInsertId($tableName = null, $primaryKey = null)
  {
    $sql = 'select @@IDENTITY';

    $result = $this->fetchOne($sql);

    if (is_numeric($result)) {
      return $result;
    } else {
      return null;
    }
  }

  /**
     * Begin a transaction.
     */
  protected function _beginTransaction()
  {
    $this->_setExecuteMode(ODBTP_AUTOCOMMIT_OFF);
  }

  /**
     * Commit a transaction.
     */
  protected function _commit()
  {
    if (!odbtp_commit($this->_connection)) {
      throw new Liquid_Db_Adapter_Odbtp_Exception(odbtp_conn_errormsg($this->_connection),
      odbtp_conn_error($this->_connection));
    }

    $this->_setExecuteMode(ODB_TXN_READCOMMITTED);
  }

  /**
     * Roll-back a transaction.
     */
  protected function _rollBack()
  {
    if (!odbtp_rollback($this->_connection)) {
      throw new Liquid_Db_Adapter_Odbtp_Exception(odbtp_conn_errormsg($this->_connection),
      odbtp_conn_error($this->_connection));
    }
    $this->_setExecuteMode(ODB_TXN_READCOMMITTED);
  }

  /**
     * Set the fetch mode.
     *
     * @param integer $mode
     */
  public function setFetchMode($mode)
  {
    switch ($mode) {
      case Zend_Db::FETCH_NUM:   // seq array
      case Zend_Db::FETCH_ASSOC: // assoc array
      case Zend_Db::FETCH_BOTH:  // seq+assoc array
      case Zend_Db::FETCH_OBJ:   // object
      $this->_fetchMode = $mode;
      break;
      default:
        throw new Liquid_Db_Adapter_Odbtp_Exception('Invalid fetch mode specified');
        break;
    }
  }

  /**
     * Adds an adapter-specific LIMIT clause to the SELECT statement.
     *
     * @return string
     */
  public function limit($sql, $count, $offset = 0)
  {
    if ($count) {

      // we need the starting SELECT clause for later
      $select = 'SELECT ';
      if (preg_match('/^[[:space:]*SELECT[[:space:]]*DISTINCT/i', $sql, $matches) == 1) {
        $select .= 'DISTINCT ';
      }

      // we need the length for substr() later
      $selectLen = strlen($select);

      // is there an offset?
      if (! $offset) {
        // no offset, it's a simple TOP count
        return "$select TOP $count " . substr($sql, $selectLen);
      }

      // the total of the count **and** the offset, combined.
      // this will be used in the "internal" portion of the
      // hacked-up statement.
      $total = $count + $offset;

      // build the "real" order for the external portion.
      $order = implode(',', $parts['order']);

      // build a "reverse" order for the internal portion.
      $reverse = $order;
      $reverse = str_ireplace(" ASC",  " \xFF", $reverse);
      $reverse = str_ireplace(" DESC", " ASC",  $reverse);
      $reverse = str_ireplace(" \xFF", " DESC", $reverse);

      // create a main statement that replaces the SELECT
      // with a SELECT TOP
      $main = "\n$select TOP $total " . substr($sql, $selectLen) . "\n";

      // build the hacked-up statement.
      // do we really need the "as" aliases here?
      $sql = "SELECT * FROM ("
      . "SELECT TOP $count * FROM ($main) AS select_limit_rev ORDER BY $reverse"
      . ") AS select_limit ORDER BY $order";

    }

    return $sql;
  }

  /**
     * Inserts a table row with specified data.
     *
     * @param string $table The table to insert data into.
     * @param array $bind Column-value pairs.
     * @return int The number of affected rows.
     */
  public function insert($table, $bind)
  {

    // col names come from the array keys
    $cols = array_keys($bind);

    $sql = '';
    $values = array();
    foreach($bind as $key => $value){
      if($value !== null) {
        if($sql){
          $sql .= ', ';
        }
        $sql .= $key;
        $values[] = $value;
      }
    }

    $sql = "INSERT INTO $table (" . $sql . ") VALUES (";

    $markers = '';
    $numParams = count($values);

    for ($i = 0; $i < $numParams; $i++) {
      $markers .= '?';
      if ($i != $numParams - 1 ) {
        $markers .= ',';
      }
    }
    $sql .= $markers . ')';

    // execute the statement and return the number of affected rows
    $result = $this->query($sql, $values);

    $this->_lastInsertTable = $table;

    return $result->rowCount();
  }

  /**
     * Updates table rows with specified data based on a WHERE clause.
     *
     * @param string $table The table to udpate.
     * @param array $bind Column-value pairs.
     * @param string $where UPDATE WHERE clause.
     * @return int The number of affected rows.
     */
  public function update($table, $bind, $where)
  {
    // build "col = :col" pairs for the statement
    $set = array();
    $values = array_values($bind);
    $newValues = array();
    foreach ($bind as $col => $val) {
      $set[] = "$col = ?";
      $newValues[] = $val;
    }

    $where = $this->_whereExpr($where);

        // build the statement
        $sql = "UPDATE $table "
      . ' SET ' . implode(', ', $set)
      . (($where) ? " WHERE $where" : '');
    
    // execute the statement and return the number of affected rows
    $result = $this->query($sql, $newValues);
    return $result->rowCount();
  }

  /**
     * Fetches all SQL result rows as an associative array.
     *
     * The first column is the key, the entire row array is the
     * value.
     *
     * @param string $sql An SQL SELECT statement.
     * @param array $bind Data to bind into SELECT placeholders.
     * @return string
     */
  public function fetchAssoc($sql, $bind = null)
  {
    $result = $this->query($sql, $bind);
    $data = array();
    while ($row = $result->fetch($this->_fetchMode)) {
      $data[] = $row;
    }
    return $data;
  }

    /**
     * Force the connection to close.
     *
     * @return void
     */
    public function closeConnection() {
    odbtp_close($this->_connection);
    $this->_connection = null;
  }
  
    /**
     * Check if the adapter supports real SQL parameters.
     *
     * @param string $type 'positional' or 'named'
     * @return bool
     */
    public function supportsParameters($type) {
    switch ($type) {
      case 'positional':
                return true;
      case 'named':
      default:
                return false;
    }
  }
}
