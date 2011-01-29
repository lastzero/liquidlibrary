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

abstract class Liquid_Db_Robot {
    protected $xmlConfig = null;
    protected $sourceDbParams = array();
    protected $destinationDbParams = array();
    protected $exportDbParams = array();
    protected $currentLocale = 'en';
    protected $currentCharset = 'ISO-8859-15';
    protected $dateFormat = '%d.%m.%Y';
    public $myTables = array();
    public $importLimit;
    public $exportLimit;
    public $sourceDb = null;
    public $destinationDb = null;
    public $exportDb = null;

    public function __construct ($config_file = 'dbconvert.xml') {
        // ...and open it
        if (file_exists($config_file)) {
            $this->xmlConfig = simplexml_load_file($config_file);
        } else {
            exit('Failed to open '.$config_file.'.');
        }

        // Setup source database
        if(isset($this->xmlConfig->source->host)) {
            $this->sourceDbParams = array ('host' => (string) $this->xmlConfig->source->host,
            'username' => (string) $this->xmlConfig->source->username,
            'password' => (string) $this->xmlConfig->source->password,
            'dbname'   => (string) $this->xmlConfig->source->database,
            'profiler' => false);

            $this->sourceDb = Zend_Db::factory((string) $this->xmlConfig->source->type, $this->sourceDbParams);
        }

        // Setup destination database
        if(isset($this->xmlConfig->destination->host)) {
            $this->destinationDbParams = array ('host' => (string) $this->xmlConfig->destination->host,
            'username' => (string) $this->xmlConfig->destination->username,
            'password' => (string) $this->xmlConfig->destination->password,
            'dbname'   => (string) $this->xmlConfig->destination->database,
            'profiler' => false);

            $this->destinationDb = Zend_Db::factory((string) $this->xmlConfig->destination->type, $this->destinationDbParams);
        }
        else {
            $this->destinationDb = $this->sourceDb;
        }

        // Setup export database
        if(isset($this->xmlConfig->export->host)) {
            $this->exportDbParams = array ('host' => (string) $this->xmlConfig->export->host,
            'username' => (string) $this->xmlConfig->export->username,
            'password' => (string) $this->xmlConfig->export->password,
            'dbname'   => (string) $this->xmlConfig->export->database,
            'profiler' => false);

            $this->exportDb = Zend_Db::factory((string) $this->xmlConfig->export->type, $this->exportDbParams);
        }
        else {
            $this->exportDb = $this->destinationDb;
        }

        // Set locale and charset parameters
        $this->resetLocale();
        $this->resetCharset();

        if(defined('TEST_COUNT')) {
            $count = TEST_COUNT;
            $this->importLimit = $count;
            $this->exportLimit = $count;
        }
    }

    public function resetLocale () {
        if(isset($this->xmlConfig->locale)) {
            $this->currentLocale = (string) $this->xmlConfig->locale;
        } else {
            $this->currentLocale = '';
        }
    }

    public function resetCharset () {
        if(isset($this->xmlConfig->charset)) {
            $this->currentCharset = (string) $this->xmlConfig->charset;
        } else {
            $this->currentCharset = 'ISO-8859-15';
        }
    }
    
    public function parseTables () {
        // Parse database tables
        foreach($this->xmlConfig->tables->table as $tableId => $table) {
            if(trim($table->attributes()->src) != '') {
                $srcTableName = strtr($table->attributes()->src, array('$' => $this->xmlConfig->source->prefix));
                if(strpos((string) $table->attributes()->src, '$') === false) {
                    $expTableName = $this->xmlConfig->export->prefix.$table->attributes()->src;
                }
                else {
                    $expTableName = strtr($table->attributes()->src, array('$' => $this->xmlConfig->export->prefix));
                }
            } else {
                $srcTableName = '';
                $expTableName = '';
            }

            if(trim($table->attributes()->dst) != '') {
                $dstTableName = strtr($table->attributes()->dst, array('$' => $this->xmlConfig->destination->prefix));
            } else {
                $dstTableName = '';
            }

            $myTable = $table->children();
            $myTable->srcTableName = $srcTableName;
            $myTable->dstTableName = $dstTableName;

            $myTable->archiveTableName = '';

            if(isset($table->attributes()->archive) && $table->attributes()->archive == 'true') {
                $myTable->archiveTableName = $dstTableName.'Archive';
            }

            $myTable->expTableName = $expTableName;

            if(isset($table->attributes()->exportcount)) {
                $myTable->exportcount = (string) $table->attributes()->exportcount;
            }

            $this->myTables[] = $myTable;
        }
    }

    public function dropTables ($export = true, $import = true) {
        if(!is_array($this->myTables)) {
            return $result;
        }

        foreach($this->myTables as $tableId => $myTable) {
            if($import && !empty($myTable->dstTableName)) {
                try {
                    $result = $this->destinationDb->query('DROP TABLE '.$myTable->dstTableName);
                } catch (Exception $e) {
                    echo '.';
                }
                if($myTable->archiveTableName != '') {
                    try {
                        $result = $this->destinationDb->query('DROP TABLE '.$myTable->archiveTableName);
                    } catch (Exception $e) {
                        echo '.';
                    }
                }
            }
            
            if($export && !empty($myTable->expTableName)) {
                try {
                    $result = $this->exportDb->query('DROP TABLE '.$myTable->expTableName);
                } catch (Exception $e) {
                    echo '.';
                }
            }
        }
    }

    public function createTables ($export = true, $import = true) {
        $result = false;

        if(!is_array($this->myTables)) {
            return $result;
        }

        foreach($this->myTables as $tableId => $myTable) {
            if(isset($myTable->col) && is_object($myTable->col)) {
                $expKeyDefs = array();
                $expMyKeys  = array();
                $archiveKeyDefs = array();
                $archiveMyKeys  = array();
                $keyDefs = array();
                $myKeys = array();
                $createExpQueryItems = array();
                $createDstQueryItems = array();
                $createArchiveQueryItems = array();
                $keyDefString = '';
                $expKeyDefString = '';
                $archiveKeyDefString = '';

                $exportable = $myTable->expTableName != '';

                if($myTable->dstTableName != '') {
                    foreach ($myTable->key as $keyId => $key) {
                        $dstKeyName = (string) $key->attributes()->dst;
                        if($dstKeyName != '') {
                            $keyDefs[$dstKeyName] = $dstKeyName.' '.$key->attributes()->type;
                            $myKeys[$dstKeyName] = $dstKeyName;
                        }
                    }

                    $createDstQuery = 'CREATE TABLE '.$myTable->dstTableName.' (';
                }

                if($myTable->archiveTableName != '') {
                    $archiveKeyDefs['archiveId'] = 'archiveId INT IDENTITY NOT NULL';
                    $archiveMyKeys['archiveId'] = 'archiveId';

                    foreach ($myTable->key as $keyId => $key) {
                        $archiveKeyName = (string) $key->attributes()->dst;
                        if($archiveKeyName != '') {
                            $archiveKeyAttrType = str_ireplace('IDENTITY', '', $key->attributes()->type);
                            $archiveKeyDefs[$archiveKeyName] = $archiveKeyName.' '.$archiveKeyAttrType;
                        }
                    }

                    $createArchiveQuery = 'CREATE TABLE '.$myTable->archiveTableName.' (';
                }

                if($myTable->expTableName != '') {
                    foreach ($myTable->key as $keyId => $key) {
                        $expKeyName = (string) $key->attributes()->src;
                        if($expKeyName != '') {
                            if(!isset($key->attributes()->srctype)) {
                                $expKeyDefs[$expKeyName] = $expKeyName.' '.(string) $key->attributes()->type;
                            }
                            else {
                                $expKeyDefs[$expKeyName] = $expKeyName.' '.(string) $key->attributes()->srctype;
                            }
                            $expMyKeys[$expKeyName] = $expKeyName;
                        }
                    }

                    $createExpQuery = 'CREATE TABLE '.$myTable->expTableName.' (';
                }

                foreach ($myTable->col as $key => $col) {
                    if(!empty($col->attributes()->type) || !empty($col->attributes()->srctype)) {
                        if(!empty($col->attributes()->type)) {
                            $dstColType = $col->attributes()->type;
                            $srcColType = $col->attributes()->type;
                        }

                        // Override
                        if(!empty($col->attributes()->srctype)) {
                            $srcColType = $col->attributes()->srctype;
                        }

                        if($myTable->expTableName != '' && $col->attributes()->src != '') {
                            $createExpQueryItems[] = $col->attributes()->src.' '.$srcColType;
                        }

                        if($myTable->dstTableName != '' && $col->attributes()->dst != '') {
                            $createDstQueryItems[] = $col->attributes()->dst.' '.$dstColType;
                            $createArchiveQueryItems[] = $col->attributes()->dst.' '.$dstColType;
                        }
                    }
                }


                $createExpQuery .= join(',', array_merge($expKeyDefs, $createExpQueryItems));

                $createDstQuery .= join(',', array_merge($keyDefs, $createDstQueryItems));

                if($myTable->archiveTableName != '') {
                    $createArchiveQuery .= join(',', array_merge($archiveKeyDefs, $createArchiveQueryItems));
                }

                if($import && $myTable->dstTableName != '') {
                    if(is_array($myKeys) && count($myKeys) > 0) {
                        $createDstQuery .= ', PRIMARY KEY ('.join(',',$myKeys).'));';
                    } else {
                        $createDstQuery .= ');';
                    }

                    try {
                        $result = $this->destinationDb->query($createDstQuery);
                    } catch (Exception $e) {
                        echo 'Caught exception in createTables() (import: '.$createDstQuery.'): ' . $e->getMessage() . "\n";
                    }

                    if($myTable->archiveTableName != '') {
                        if(is_array($archiveMyKeys) && count($archiveMyKeys) > 0) {
                            $createArchiveQuery .= ', PRIMARY KEY ('.join(',',$archiveMyKeys).'));';
                        }
                        else {
                            $createArchiveQuery .= ');';
                        }

                        try {
                            $result = $this->destinationDb->query($createArchiveQuery);
                        } catch (Exception $e) {
                            echo 'Caught exception in createTables() (import: '.$createArchiveQuery.'): ' . $e->getMessage() . "\n";
                        }
                    }
                }

                if($export && $myTable->expTableName != '') {
                    if(is_array($expMyKeys) && count($expMyKeys) > 0) {
                        $createExpQuery .= ', PRIMARY KEY ('.join(',',$expMyKeys).'));';
                    } else {
                        $createExpQuery .= ');';
                    }
                    
                    try {
                        $result = $this->exportDb->query($createExpQuery);
                    } catch (Exception $e) {
                        echo 'Caught exception in createTables() (export: '.$createExpQuery.'): '.$e->getMessage()."\n";
                    }
                }
            }
        }

        return $result;
    }

    abstract function importTables ();
    abstract function exportTables ();
}
