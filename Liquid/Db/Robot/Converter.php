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

class Liquid_Db_Robot_Converter extends Liquid_Db_Robot {
    public function csvImport($table, $filename) {
        if(!file_exists($filename)) {
              echo " failed";
              return FALSE;
        }
        $row = 1;
        $handle = fopen($filename, "r");
        $cols = fgetcsv($handle, 0, ",", '"'); // Unlimted line length requires PHP >= 5.0.4!
        while (($data = fgetcsv($handle, 0, ",", '"')) !== FALSE) {
              $num = count($data);
              $newRow = array();

              $row++;
              for ($c=0; $c < $num; $c++) {
                $newRow[$cols[$c]] = $data[$c];
              }

              $this->destinationDb->insert($table, $newRow);
              echo '.';
        }
        fclose($handle);
        echo " ok";
    }

    public function getRelationValue ($dbTable, $fromValue, $fromKey, $toKey, $recursion = false) {
        if(empty($dbTable) || empty($fromValue) || empty($fromKey) || empty($toKey)) {
          return null;
        }

        if($this->currentLocale == '') {
          $sqlQuery = "SELECT $toKey FROM $dbTable WHERE $fromKey = ?";
          $sqlParams = array($fromValue);
        }
        else {
          $sqlQuery = "SELECT $toKey FROM $dbTable WHERE $fromKey = ? AND locale = ?";
          $sqlParams = array($fromValue, (string) $this->currentLocale);
        }

        try {
          $SQLResult = $this->sourceDb->fetchCol($sqlQuery, $sqlParams);
        } catch (Exception $e) {
          echo "Caught exception in getRelationValue ($sqlQuery): ".$e->getMessage();
        }


        if(empty($SQLResult) && !$recursion) {
          if($fromValue != '' && $fromValue != null) {
            $newRow = array($fromKey => $fromValue);
            if($this->currentLocale != '') {
              $newRow['locale'] = (string) $this->currentLocale;
            }
            try {
              $this->sourceDb->insert($dbTable, $newRow);
              $result = $this->getRelationValue ($dbTable, $fromValue, $fromKey, $toKey, true);
            }
            catch (Exception $e) {
              $result = null;
            }
          }
          else {
            $result = null;
          }
        } elseif(empty($SQLResult)) {
          $result = null;
        }
        elseif(!empty($SQLResult[0]) && (is_numeric($SQLResult[0]) || is_string($SQLResult[0]))) {
          $result = $SQLResult[0];
        }

        return $result;
    }

    public function setManyToManyRelation ($fromTable, $fromValue, $fromKey, $toTable, $toKeyName, $toColName, $relTable, $relToKey, $relFromKey, $orderKey = null, $orderValue = null, $function = null, $recursion = false) {
        if(empty($fromTable) || empty($fromValue) || empty($fromKey)|| empty($toTable) || empty($toKeyName) || empty($toColName) || empty($relTable) || empty($relToKey) || empty($relFromKey) ) {
            // echo "Rel: $relTable From: $fromTable To: $toTable relFromKey:$relFromKey, relToKey: $relToKey => FromKey:$fromKey, FromValue:$fromValue\n";
            return null;
        }

        // TODO: Small hack
        if(mb_detect_encoding($fromValue) != 'ASCII') {
            $fromValue = mb_convert_encoding($fromValue, 'UTF-8', $this->currentCharset);
        }

        $toKeyValue = $this->sourceDb->fetchCol("SELECT $toKeyName FROM $toTable WHERE $toColName = ?",
        array(trim($fromValue))
        );

        if(isset($toKeyValue[0]) && trim($toKeyValue[0]) != '') {
            if($orderValue != null && $orderKey != null) {
            $newRow = array($relToKey => $toKeyValue[0], $relFromKey => $fromKey, $orderKey => $orderValue);
            }
            else {
            $newRow = array($relToKey => $toKeyValue[0], $relFromKey => $fromKey);
            }

            if(!empty($function)) {
            $functionId = $this->getRelationValue($this->xmlConfig->destination->prefix.'functions', $function, 'fncTitle', 'rowId');
            if(is_numeric($functionId) && $functionId > 0) {
                $newRow['fncId'] = $functionId;
            }
            }

            try {
            $this->sourceDb->insert($relTable, $newRow);
            }
            catch (Exception $e) {
            // echo 'Caught exception in setManyToManyRelation ('.$fromTable.'): ',  $e->getMessage(), "\n";
            }

            $result = true;
        }
        elseif(empty($toKeyValue) && !$recursion) {
            if(trim($fromValue) != '') {
            $newRow = array($toColName => trim($fromValue));

            if($this->currentLocale != '') {
                $newRow['locale'] = (string) $this->currentLocale;
            }

            try {
                $this->sourceDb->insert($toTable, $newRow);
                $result = $this->setManyToManyRelation($fromTable, $fromValue, $fromKey, $toTable, $toKeyName, $toColName, $relTable, $relToKey, $relFromKey, $orderKey, $orderValue, $function, true);
            }
            catch (Exception $e) {
                $result = false;
            }

            }
            else {
            $result = false;
            }
        }
        elseif(empty($toKeyValue)) {
            $result = false;
        }


        return $result;
    }

    public function getManyToManyRelationValue ($fromKey, $toTable, $toKeyName, $toColName, $relTable, $relToKey, $relFromKey, $orderKey = null, $orderValue = null) {
        if(empty($fromKey)|| empty($toTable) || empty($toKeyName) || empty($toColName) || empty($relTable) || empty($relToKey) || empty($relFromKey) ) {
          return null;
        }
        if($this->currentLocale != '') {
          $sqlWhere = " AND locale = ?";
          $sqlParams = array((string) $this->currentLocale);
        }
        else {
          $sqlWhere = '';
          $sqlParams = array();
        }

        if($orderValue != null && $orderKey != null) {
          $sqlQuery = "SELECT $toTable.$toColName
				    FROM
					    $toTable, $relTable
				    WHERE
					    $toTable.$toKeyName = $relTable.$relToKey
				    AND $relTable.$relFromKey = ?
				    AND $relTable.$orderKey = ?".$sqlWhere;
          $sqlParams = array_merge(array($fromKey, $orderValue), $sqlParams);
        }
        else {
          $sqlQuery = "SELECT $toTable.$toColName
				    FROM
					    $toTable, $relTable
				    WHERE
					    $toTable.$toKeyName = $relTable.$relToKey
				    AND $relTable.$relFromKey = ?".$sqlWhere;
          $sqlParams = array_merge(array($fromKey), $sqlParams);
        }

        $myValueResults = $this->sourceDb->fetchAll($sqlQuery, $sqlParams);

        if(count($myValueResults) > 0 && is_array($myValueResults[0])) {
            foreach($myValueResults[0] as $myValue) {
                $myValues[] = trim($myValue);
            }
            $result = join(',', $myValues);
        } else {
            $result = '';
        }

        return $result;
    }

    public function transposeRowCol ($tableName, $original, $sortOrder, $rowTableName, $rowKeyName, $rowValueName, $colTableName, $colKeyName, $colValueName, $relTableName, $relRowKey, $relColKey, $relSortOrderKey, $colKey, $function, $export = false) {
        $result = null;

        if($export) {

          $colId = $this->getRelationValue($colTableName, $colKey, $colValueName, $colKeyName);
          $functionId = $this->getRelationValue($this->xmlConfig->destination->prefix.'functions', $function, 'fncTitle', 'fncId');

          $myResults = $this->sourceDb->fetchAll("SELECT $rowTableName.$rowValueName as myvalue
				    FROM
					    $rowTableName, $relTableName
				    WHERE
					    $rowTableName.$rowKeyName = $relTableName.$relRowKey
				    AND $relTableName.$relColKey = ?
				    AND $relTableName.$relSortOrderKey = ?
				    AND $relTableName.fncId = ?",
          array($colId, $sortOrder, $functionId)
          );

          if(count($myResults) > 0 && is_array($myResults[0]) && isset($myResults[0]['myvalue'])) {
            $result = $myResults[0]['myvalue'];
          }
          else {
            $result = null;
          }

        }
        else {
            if(empty($original)) {
            return null;
            }

            $rowId = $this->getRelationValue($rowTableName, $original, $rowValueName, $rowKeyName);
            $colId = $this->getRelationValue($colTableName, $colKey, $colValueName, $colKeyName);

            if(!empty($rowId) && !empty($colId)) {
            $newRow = array($relRowKey => $rowId, $colKeyName => $colId, $relSortOrderKey => $sortOrder);

            $functionId = $this->getRelationValue($this->xmlConfig->destination->prefix.'functions', $function, 'fncTitle', 'fncId');

            if(is_numeric($functionId) && $functionId > 0) {
                $newRow['fncId'] = $functionId;
            }

            try {
                $this->sourceDb->insert($relTableName, $newRow);
                $result = true;
            }
            catch (Exception $e) {
                echo "\nException: ".$e->getMessage()."\n";
                $result = false;
            }
            }
        }

        return $result;
    }

    public function getMergeKey ($mergeTableName, $mergeKeyName, $mergeValueName, $mergeValue) {
        if(empty($mergeTableName) || empty($mergeKeyName) || empty($mergeValueName) || empty($mergeValue)) {
          return null;
        }

        $SQLResult = $this->sourceDb->fetchCol("SELECT $mergeKeyName FROM $mergeTableName WHERE $mergeValueName = ?",
        array($mergeValue)
        );

        if(empty($SQLResult)) {
          $result = null;
        }
        elseif(!empty($SQLResult[0]) && (is_numeric($SQLResult[0]) || is_string($SQLResult[0]))) {
          $result = $SQLResult[0];
        }

        return $result;
    }

    public function dateToSqlDate ($date) {
        if(is_object($date)) {
          return $date;
        }

        $date = trim($date);

        if($date == '') {
            return null;
        }

        $expDate = explode('.', $date);

        $dateTime = odbtp_new_datetime();

        if(strlen($date) == 4 && strpos($date, ':') === false) {
            $dateTime->year = $date;
            $dateTime->month = 1;
            $dateTime->day = 1;
            // $result = $date.'-01-01';
        }
        elseif(strlen($date) == 5 && strpos($date, '/') == 2) {
            $expDate = explode('/', $date);
            // $result = '20'.$expDate[1].'-01-'.$expDate[0];
            $dateTime->year = '20'.$expDate[1];
            $dateTime->month = $expDate[0];
            $dateTime->day = 1;
        }
        elseif(count($expDate) == 3 && strpos($date, ':') === false) {
            // $result = $expDate[2].'-'.$expDate[0].'-'.$expDate[1];
            $year = $expDate[2];

            if(strlen($year) == 2) {
                if($year > 50) {
                    $year = (int) '19'.$year;
                }
                else {
                    $year = (int) '20'.$year;
                }
            }

            $dateTime->year = $year;
            $dateTime->month = $expDate[1];
            $dateTime->day = $expDate[0];
        }
        else {
            // $result = strftime('%Y-%m-%d', strtotime($date)); // %d.%m.%Y %H:%M:%S
            $dateTime->year = strftime('%Y', strtotime($date));
            $dateTime->month = strftime('%m', strtotime($date));
            $dateTime->day = strftime('%d', strtotime($date));
        }

        return $dateTime;
    }

  public function sqlDateToDate ($date) {
        $date = trim($date);

        if($date == '') {
            return '';
        }

        $expDate = explode('-', $date);

        if(count($expDate) == 3 && strpos($date, ':') === false) {
            $result = $expDate[0].'.'.$expDate[2].'.'.$expDate[1];
        }
        else {
            $result = strftime('%d.%m.%Y %H:%M:%S', strtotime($date));
        }

        return $result;
    }

    public function bin2hex ($binData) {
        $data = unpack("H*hex", $binData);
        $result = '0x'.$data['hex'];
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
                case 'bool': $result = (trim((string) $original) == $myParameters[0]); break;
                case 'date': $result = $this->dateToSqlDate($original); break;
                case 'rel': $result = $this->getRelationValue($myParameters[0], $original, $myParameters[1], $myParameters[2]); break;
                case 'lcrel': $result = $this->getRelationValue($myParameters[0], strtolower($original), $myParameters[1], $myParameters[2]); break;
                case 'mrel': $result = $this->setManyToManyRelation($tableName, $original, $keyValue, $myParameters[0], $myParameters[1], $myParameters[2], $myParameters[3], $myParameters[4], $myParameters[5], null, null, null); break;
                case 'orel': $result = $this->setManyToManyRelation($tableName, $original, $keyValue, $myParameters[0], $myParameters[1], $myParameters[2], $myParameters[3], $myParameters[4], $myParameters[5], $myParameters[6], $myParameters[7], null); break;
                case 'fmrel': $result = $this->setManyToManyRelation($tableName, $original, $keyValue, $myParameters[0], $myParameters[1], $myParameters[2], $myParameters[3], $myParameters[4], $myParameters[5], null, null, $myParameters[6]); break;
                case 'forel': $result = $this->setManyToManyRelation($tableName, $original, $keyValue, $myParameters[0], $myParameters[1], $myParameters[2], $myParameters[3], $myParameters[4], $myParameters[5], $myParameters[6], $myParameters[7], $myParameters[8]); break;
                case 'trans': $result = $this->transposeRowCol($tableName, $original, $keyValue, $myParameters[0], $myParameters[1], $myParameters[2], $myParameters[3], $myParameters[4], $myParameters[5], $myParameters[6], $myParameters[7], $myParameter[8], $myParameters[9], $myParameters[10], $myParameters[11], false); break;
                case 'bin': $result = $original; break;
                case 'trim': $result = trim($original); break;
                case 'merge': $result = $this->getMergeKey($myParameters[0], $myParameters[1], $myParameters[2], $originalValues[$myParameters[3]]); break;
                case 'utf8': $result = mb_convert_encoding($original, 'UTF-8', $this->currentCharset); break;
                case 'default': $result = ($original == null) ? $myParameters[0] : $original; break;
                default: $result = '';
            }
            } else {
            switch($myConversion[0]) {
                case '': $result = $original; break;
                case 'int': $result = $original; break;
                case 'bool': $result = ((bool) $original ? $myParameters[0] : $myParameters[1]); break;
                case 'date': $result = $result = $this->sqlDateToDate($original); break;
                case 'rel': $result = $this->getRelationValue($myParameters[0], $original, $myParameters[2], $myParameters[1]); break;
                case 'lcrel': $result = $this->getRelationValue($myParameters[0], strtolower($original), $myParameters[2], $myParameters[1]); break;
                case 'mrel': $result = $this->getManyToManyRelationValue($keyValue, $myParameters[0], $myParameters[1], $myParameters[2], $myParameters[3], $myParameters[4], $myParameters[5]); break;
                case 'orel': $result = $this->getManyToManyRelationValue($keyValue, $myParameters[0], $myParameters[1], $myParameters[2], $myParameters[3], $myParameters[4], $myParameters[5], $myParameters[6], $myParameters[7]); break;
                case 'fmrel': $result = $this->getManyToManyRelationValue($keyValue, $myParameters[0], $myParameters[1], $myParameters[2], $myParameters[3], $myParameters[4], $myParameters[5]); break;
                case 'forel': $result = $this->getManyToManyRelationValue($keyValue, $myParameters[0], $myParameters[1], $myParameters[2], $myParameters[3], $myParameters[4], $myParameters[5], $myParameters[6], $myParameters[7]); break;
                case 'trans': $result = $this->transposeRowCol($tableName, $original, $keyValue, $myParameters[0], $myParameters[1], $myParameters[2], $myParameters[3], $myParameters[4], $myParameters[5], $myParameters[6], $myParameters[7], $myParameters[8], $myParameters[9], $myParameters[10], $myParameters[11], true); break;
                case 'bin': $result = $original; break;
                case 'trim': $result = $original; break;
                case 'merge': $result = null; break;
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
          $rowCounter = 0;

          if(isset($myTable->col) && is_object($myTable->col) && $myTable->srcTableName != '') {
            if($myTable->dstTableName != '') {
              echo "\n  ".$myTable->dstTableName;
            }

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

              foreach ($rowset as $row) {
                $rowCounter++;
                $keyValue = $rowCounter;
                $identity = null;

                $row = array_change_key_case($row, CASE_LOWER);

                $newRow = array();
                $binKeys = array();
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

                // TODO: Create relations

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
          if(isset($myTable->csv) && is_object($myTable->csv)) {
            foreach ($myTable->csv as $key => $csvFile) {
              echo "\n  Import ".trim($csvFile)." to ".$myTable->dstTableName."...";
              $this->csvImport($myTable->dstTableName, trim($csvFile));
            }
          }
          if(isset($myTable->sql) && is_object($myTable->sql)) {
            foreach ($myTable->sql as $key => $script) {
              $sqlScript = strtr((string) $script, array('%%srctable%%' => $myTable->srcTableName, '%%exptable%%' => $myTable->expTableName, '%%dsttable%%' => $myTable->dstTableName));
              try {
                $this->destinationDb->query($sqlScript);
              } catch (Exception $e) {
                echo 'Caught exception during SQL script ('.$sqlScript.'): '.$e->getMessage();
              }
            }
          }
        }
        return $result;
    }

    public function exportTables () {
        $result = false;

        if(!is_array($this->myTables)) {
          return $result;
        }

        foreach($this->myTables as $tableId => $myTable) {

          if(isset($myTable->col) && is_object($myTable->col) && $myTable->srcTableName != '') {
            $exportCount = null;
            /*
            try {
            $this->sourceDb->query('SET IDENTITY_INSERT '.$myTable->expTableName.' ON');
            } catch (Exception $e) {

            }
            */
            if(isset($myTable->exportcount)) {
              $exportCount = $this->sourceDb->fetchOne($myTable->exportcount);

              if(empty($exportCount) || !is_numeric($exportCount)) {
                echo "\nError in exportcount: ".$myTable->exportcount;
              }
              else {
                for($i = 1; $i <= $exportCount; $i++) {
                  $newRow = array();
                  $emptyRow = true;
                  // Copy other colums
                  foreach ($myTable->col as $key => $col) {
                    if(!empty($col->attributes()->src)) {
                      $srcColName = (string) $col->attributes()->src;

                      if(isset($col->attributes()->convert)) {
                        $data = $this->convertData($myTable->expTableName, $i, null, null, $col->attributes()->convert, true);
                      }

                      if($data != null) {
                        $emptyRow = false;
                      }

                      $newRow[$srcColName] = $data;
                    }
                  }

                  echo '.';

                  if(!$emptyRow) {

                    try {
                      $this->exportDb->insert($myTable->expTableName, $newRow);
                    } catch (Exception $e) {
                      echo 'Caught exception during INSERT in exportTables: ',  $e->getMessage(), "\n";
                      // print_r($newRow);
                      echo "\n\n";
                    }
                  }
                }
              }
            }
            else {
              $selectSQL = 'SELECT * FROM '.$myTable->dstTableName;

              if($this->importLimit > 0) {
                $selectSQL = $this->sourceDb->limit($selectSQL, $this->exportLimit, 0);
              }

              $rowset = $this->destinationDb->fetchAll($selectSQL);

              foreach ($rowset as $row) {
                $row = array_change_key_case($row, CASE_LOWER);
                $identity = null;

                $newRow = array();
                $binKeys = array();

                // Copy Keys
                foreach ($myTable->key as $keyId => $key) {
                  $keyName = (string) $key->attributes()->src;
                  $keyValue = $row[strtolower((string) $key->attributes()->dst)];

                  if(isset($key->attributes()->convert)) {
                    $keyValue = $this->convertData($myTable->expTableName, $keyValue, $row, $keyValue, $key->attributes()->convert, true);
                  }

                  if(isset($key->attributes()->identity)) {
                    $identity = $keyValue;
                  }

                  if($keyValue !== null && $keyName != '') {
                    $newRow[$keyName] = $keyValue;
                  }
                }

                // Copy other colums
                foreach ($myTable->col as $key => $col) {
                  if(!empty($col->attributes()->src)) {
                    $srcColName = (string) $col->attributes()->src;
                  } else {
                    $srcColName = '';
                  }

                  $dstColName = (string) $col->attributes()->dst;

                  if($dstColName != '' && isset($row[strtolower($dstColName)])) {
                    $data = $row[strtolower($dstColName)];
                  }
                  else {
                    $data = null;
                  }

                  if(isset($col->attributes()->locale)) {
                    $this->currentLocale = (string) $col->attributes()->locale;
                  }

                  if(isset($col->attributes()->convert)) {
                    $data = $this->convertData($myTable->expTableName, $keyValue, $row, $data, $col->attributes()->convert, true);
                    if($col->attributes()->convert == 'bin') {
                      $binKeys[] = $srcColName;
                    }
                  }

                  if(isset($col->attributes()->identity)) {
                    $identity = $data;
                  }

                  if(isset($col->attributes()->locale)) {
                    $this->resetLocale();
                  }

                  if($srcColName != '') {
                    $newRow[$srcColName] = $data;
                  }
                }

                echo '.';

                if(!empty($newRow)) {
                  try {
                    $this->exportDb->insert($myTable->expTableName, $newRow);
                    // $this->sqlInsert($myTable->expTableName, $newRow, $binKeys);
                  } catch (Exception $e) {
                    echo 'Caught exception during INSERT in exportTables: ',  $e->getMessage(), "\n";
                    print_r($newRow);
                    echo "\n\n";
                  }
                }
              }
            }
          }
          if(isset($myTable->expsql) && is_object($myTable->expsql)) {
            foreach ($myTable->expsql as $key => $script) {
              $sqlScript = strtr((string) $script, array('%%srctable%%' => $myTable->srcTableName, '%%exptable%%' => $myTable->expTableName, '%%dsttable%%' => $myTable->dstTableName));
              try {
                $this->exportDb->query($sqlScript);
              } catch (Exception $e) {
                echo 'Caught exception during SQL script ('.$sqlScript.'): ',  $e->getMessage(), "\n";
              }
            }
          }

        }
        return $result;
    }
}
