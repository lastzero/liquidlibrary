<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Storage
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */

require_once 'Liquid/Storage/Adapter/Abstract.php';

class Liquid_Storage_Adapter_Runtime extends Liquid_Storage_Adapter_Abstract {    
    protected function find ($namespace = null, $key = null, $id = null) {
        if(isset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key, $id)])) {
            return $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key, $id)];
        }
        
        return null;
    }

    protected function create ($namespace, $key, $data, array $meta) {
        if(!isset($GLOBALS['Liquid_Storage'])) {
            $GLOBALS['Liquid_Storage'] = array();
        }
        
        if(!isset($GLOBALS['Liquid_Storage'][$this->getStoreKey()])) {
            $GLOBALS['Liquid_Storage'][$this->getStoreKey()] = array();
        }
        
        if(!isset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace)])) {
            $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace)] = array();
        }
        
        if(!isset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key)])) {
            $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key)] = array();
        }
        
        $id = count($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key)]) + 1;
 
        if(!isset($GLOBALS['Liquid_Storage'][$this->getStoreKey()][$namespace])) {
            $GLOBALS['Liquid_Storage'][$this->getStoreKey()][$namespace] = array('created' => time());
        }
        
        if(!isset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace)][$key])) {
            $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace)][$key] = array('created' => time());
        }
        
        $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key)][$id] = $meta;
        $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key, $id)] = $data;
        
        return $id;
    }

    protected function replace ($namespace, $key, $id, $data, array $meta) {
        if(!isset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key, $id)])) {
            throw new Liquid_Storage_Adapter_Exception_EntryNotFound ('Entry does not exist: ' . $id);
        }

        $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key)][$id] = $meta;
        $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key, $id)] = $data;
    } 
    
    protected function replaceMeta ($namespace = null, $key = null, $id = null, array $meta) {
        if($id !== null) {
            $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key)][$id] = $meta;
        } elseif ($key != null) {
            $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace)][$key] = $meta;
        } elseif ($namespace != null) {
            $GLOBALS['Liquid_Storage'][$this->getStoreKey()][$namespace] = $meta;
        }
    } 
        
    protected function delete ($namespace = null, $key = null, $id = null) {
        if($id !== null) {
            unset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key)][$id]);
            unset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key, $id)]);
        } elseif($key !== null) {
            if(isset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key)])) {
                foreach($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key)] as $id => $meta) {
                    unset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key, $id)]);
                }

                unset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $key)]);
            }
                        
            unset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace)][$key]);
        } elseif($namespace !== null) {
            if(isset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace)])) {
                foreach($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace)] as $key => $time) {
                    $this->delete($namespace, $key);
                }

                unset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace)]);
            }
        } else {
            $GLOBALS['Liquid_Storage'] = array();
        }
    }
        
    protected function _renameNamespace ($oldNamespaceName, $newNamespaceName) {        
        foreach($GLOBALS['Liquid_Storage'][$this->getStoreKey($oldNamespaceName)] as $key => $time) {
            $GLOBALS['Liquid_Storage'][$this->getStoreKey($newNamespaceName, $key)] = $GLOBALS['Liquid_Storage'][$this->getStoreKey($oldNamespaceName, $key)];
            unset($GLOBALS['Liquid_Storage'][$this->getStoreKey($oldNamespaceName, $key)]);
            
            foreach($GLOBALS['Liquid_Storage'][$this->getStoreKey($newNamespaceName, $key)] as $id => $meta) {
                $GLOBALS['Liquid_Storage'][$this->getStoreKey($newNamespaceName, $key, $id)] = $GLOBALS['Liquid_Storage'][$this->getStoreKey($oldNamespaceName, $key, $id)];
                unset($GLOBALS['Liquid_Storage'][$this->getStoreKey($oldNamespaceName, $key, $id)]);
            }    
        }
        
        $GLOBALS['Liquid_Storage'][$this->getStoreKey($newNamespaceName)] = $GLOBALS['Liquid_Storage'][$this->getStoreKey($oldNamespaceName)];
        unset($GLOBALS['Liquid_Storage'][$this->getStoreKey($oldNamespaceName)]);
        
        $GLOBALS['Liquid_Storage'][$this->getStoreKey()][$newNamespaceName] = $GLOBALS['Liquid_Storage'][$this->getStoreKey()][$oldNamespaceName];
        unset($GLOBALS['Liquid_Storage'][$this->getStoreKey()][$oldNamespaceName]);
    }

    protected function _renameKey ($namespace, $oldKeyName, $newKeyName) {
        $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace)][$newKeyName] = $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace)][$oldKeyName];
        unset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace)][$oldKeyName]);

        $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $newKeyName)] = $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $oldKeyName)];
        unset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $oldKeyName)]);
        
        foreach($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $newKeyName)] as $id => $meta) {
            $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $newKeyName, $id)] = $GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $oldKeyName, $id)];
            unset($GLOBALS['Liquid_Storage'][$this->getStoreKey($namespace, $oldKeyName, $id)]);        
        }    
    }
}
