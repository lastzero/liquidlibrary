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

class Liquid_Db_Robot_Export extends Liquid_Db_Robot {
    public function dateObjectToMySQLDate ($date) {
        if(!is_object($date) || empty($date->year)) {
            $result = NULL;
        }
        else {
            $result = $date->year.'-'.$date->month.'-'.$date->day.' '.$date->hour.':'.$date->minute.':'.$date->second;
        }
        return $result;
    }

    public function convertData ($tableName, $keyValue, $originalValues, $original, $conversion = '', $export = false) {
        $myConversion = explode(':', $conversion);

        if(isset($myConversion[1])) {
            $myParameters = explode(',', $myConversion[1]);
        } else {
            $myParameters = '';
        }
        try {
            if(!$export) {
            switch($myConversion[0]) {
                case '': $result = $original; break;
                case 'int': $result = (int) $original; break;
                case 'bool': $result = ((string) $original == $myParameters[0]); break;
                case 'mysqldate': $result = $this->dateObjectToMySQLDate($original); break;
                case 'utf8': $result = mb_convert_encoding($original, 'UTF-8', $this->currentCharset); break;
                case 'default': $result = ($original == null) ? $myParameters[0] : $original; break;
                default: $result = '';
            }
            } else {
            switch($myConversion[0]) {
                case '': $result = $original; break;
                case 'int': $result = $original; break;
                case 'bool': $result = ((bool) $original ? $myParameters[0] : $myParameters[1]); break;
                case 'mysqldate': $result = $result = $this->sqlDateToDate($original); break;
                case 'utf8': $result = mb_convert_encoding($original, $this->currentCharset, 'UTF-8'); break;
                case 'default': $result = $original; break;
                default: $result = '';
            }
            }
        } catch (Exception $e) {
            echo "Caught exception in convertData (Table: $tableName, KeyValue: $keyValue, OriginalValue: $originalValues, Original: $original, Conversion: $conversion): ".$e->getMessage();
        }

        return $result;
    }

    public function importTables () {
        $result = false;

        if(!is_array($this->myTables)) {
            return $result;
        }

        foreach($this->myTables as $tableId => $myTable) {
            if(isset($myTable->sql) && is_object($myTable->sql)) {
                foreach ($myTable->sql as $key => $script) {
                    $sqlScript = strtr((string) $script, array('%%srctable%%' => $myTable->srcTableName, '%%exptable%%' => $myTable->expTableName, '%%dsttable%%' => $myTable->dstTableName));
                    try {
                        $this->sourceDb->query($sqlScript);
                    }
                    catch (Exception $e) {
                        echo "Caught exception in importTables ($sqlScript): ".$e->getMessage();
                    }
                }
            }
        }
    }

    public function exportTables () {
        $result = false;

        $this->destinationDb->query("SET NAMES utf8");

        if(!is_array($this->myTables)) {
            return $result;
        }

        foreach($this->myTables as $tableId => $myTable) {
            if(isset($myTable->col) && is_object($myTable->col) && $myTable->srcTableName != '' && $myTable->dstTableName != '') {

            echo "\n  ".$myTable->dstTableName;

            try {

                $selectSQL = 'SELECT * FROM '.$myTable->srcTableName;

                if($this->importLimit > 0) {
                    $selectSQL = $this->sourceDb->limit($selectSQL, $this->importLimit, 0);
                }

                try {
                    $rowset = $this->sourceDb->fetchAll($selectSQL);
                }
                catch (Exception $e) {
                    echo 'Caught exception during fetchAll('.$selectSQL.') in importTables(): ',  $e->getMessage(), "\n";
                }

                $rowCounter = 0;

                foreach ($rowset as $row) {
                    $rowCounter++;
                    $keyValue = $rowCounter;
                    $identity = null;

                    $row = array_change_key_case($row, CASE_LOWER);

                    $newRow = array();

                    // Copy Keys
                    foreach ($myTable->key as $keyId => $key) {
                        if(!empty($key->attributes()->dst)) {
                            $keyValue = null;

                            if(!empty($key->attributes()->src) && (string) $key->attributes()->src != '') {
                                $keyValue = $row[strtolower((string) $key->attributes()->src)];
                            }
                            elseif(!empty($key->attributes()->default)) {
                                $keyValue = (string) $key->attributes()->default;
                            }

                            if(isset($key->attributes()->convert)) {
                                $keyValue = $this->convertData($myTable->dstTableName, $keyValue, $row, $keyValue, $key->attributes()->convert);
                            }

                            if($keyValue === null && isset($key->attributes()->autoincrement)) {
                                $keyValue = $rowCounter;
                            }

                            if(isset($key->attributes()->identity)) {
                                $identity = $keyValue;
                            }

                            $newRow[(string) $key->attributes()->dst] = $keyValue;
                        }
                    }

                    // Copy other colums
                    foreach ($myTable->col as $key => $col) {
                        $data = null;
                        $dstColName = '';
                        $srcColName = '';

                        if(!empty($col->attributes()->dst)) {
                            $dstColName = (string) $col->attributes()->dst;
                        }

                        if(!empty($col->attributes()->src) && (string) $col->attributes()->src != '') {
                            $srcColName = strtolower((string) $col->attributes()->src);
                            $data = $row[$srcColName];
                        }
                        elseif(isset($col->attributes()->default)) {
                            $data = (string) $col->attributes()->default;
                        }

                        if(isset($col->attributes()->locale)) {
                            $this->currentLocale = (string) $col->attributes()->locale;
                        }

                        if(isset($col->attributes()->convert)) {
                            $data = $this->convertData($myTable->dstTableName, $identity, $row, $data, $col->attributes()->convert, false);
                            if($col->attributes()->convert == 'bin') {
                                $binKeys[] = $dstColName;
                            }
                        }

                        if(isset($col->attributes()->identity)) {
                            $identity = $data;
                        }

                        if(isset($col->attributes()->locale)) {
                            $this->resetLocale();
                        }

                        if($dstColName != '') {
                            $newRow[$dstColName] = $data;
                        }
                    }

                    echo '.';
                    
                    if($myTable->dstTableName != '') {
                        try {
                            //$this->sqlInsert($myTable->dstTableName, $newRow, $binKeys);
                            $this->destinationDb->insert($myTable->dstTableName, $newRow);
                        } catch (Exception $e) {
                            echo 'Caught exception during INSERT ('.$myTable->dstTableName.') in importTables(): ',  $e->getMessage(), "\n";
                            print_r($newRow);
                        }
                    }

                    }
                }
                catch (Exception $e) {
                    echo 'Caught exception during select iteration ('.$selectSQL.') in importTables(): ',  $e->getMessage(), "\n";
                }
            }
        }
    }
}

