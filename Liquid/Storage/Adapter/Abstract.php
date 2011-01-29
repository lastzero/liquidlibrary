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
 
require_once 'Liquid/Storage/Adapter/Interface.php';
 
abstract class Liquid_Storage_Adapter_Abstract implements Liquid_Storage_Interface {    
    protected function getStoreKey ($namespace = null, $key = null, $id = null) {
        if($namespace === null) {
            return 'index/#index';
        }        
        
        if($key === null) {
            return 'index/' . urlencode($namespace) . '/#index';
        }
        
        if(is_array($key)) {
            $key = implode('/', $key);
        }

        if($id === null) {
            return 'index/' . urlencode($namespace) . '/' . urlencode($key) . '/#index';
        }
        
        return 'store/' . urlencode($namespace) . '/' . urlencode($key) . '/' . urlencode($id);
    }
    
    abstract protected function find ($namespace = null, $key = null, $id = null);

    abstract protected function create ($namespace, $key, $data, array $meta);

    abstract protected function replace ($namespace, $key, $id, $data, array $meta);        
    
    abstract protected function replaceMeta ($namespace = null, $key = null, $id = null, array $meta);

    abstract protected function delete ($namespace = null, $key = null, $id = null);    
    
    abstract protected function _renameNamespace ($oldNamespaceName, $newNamespaceName);
    
    abstract protected function _renameKey ($namespace, $oldKeyName, $newKeyName);        
    
    // Namespace meta data
    public function setNamespaceMeta ($namespace, array $meta) {
        if($this->namespaceExists($namespace)) {
            $this->replaceMeta($namespace, null, null, $meta);
        } else {
            throw new Liquid_Storage_Adapter_Exception_NamespaceNotFound(
                'Namespace not found: ' . $namespace
            );
        }
    }
    
    public function getNamespaceMeta ($namespace) {
        if($this->namespaceExists($namespace)) {
            $index = $this->find();
            return $index[$namespace];
        } else {
            throw new Liquid_Storage_Adapter_Exception_NamespaceNotFound(
                'Namespace not found: ' . $namespace
            );
        }
    }
    
    public function addNamespaceMeta ($namespace, $name, $value) {
        $meta = $this->getNamespaceMeta($namespace);
        
        if(array_key_exists($name, $meta)) {
            throw new Liquid_Storage_Adapter_Exception (
                'Value already exists in meta data: ' . $name
            );
        }
        
        $meta[$name] = $value;
        
        $this->replaceMeta($namespace, null, null, $meta);
    }
    
    public function replaceNamespaceMeta ($namespace, $name, $value) {
        $meta = $this->getNamespaceMeta($namespace);        
        
        $meta[$name] = $value;
        
        $this->replaceMeta($namespace, null, null, $meta);
    }
    
    public function deleteNamespaceMeta ($namespace, $name) {
        $meta = $this->getNamespaceMeta($namespace);
        
        if(array_key_exists($name, $meta)) {
            unset($meta[$name]);
        }
        
        $this->replaceMeta($namespace, null, null, $meta);        
    }

    // Key meta data
    public function setKeyMeta ($namespace, $key, array $meta) {
        if($this->keyExists($namespace, $key)) {
            $this->replaceMeta($namespace, $key, null, $meta);
        } else {
            throw new Liquid_Storage_Adapter_Exception_KeyNotFound(
                'Key not found in namespace "' . $namespace . '": ' . $key
            );
        }
    }
    
    public function getKeyMeta ($namespace, $key) {
        if($this->keyExists($namespace, $key)) {
            $index = $this->find($namespace);
            return $index[$key];
        } else {
            throw new Liquid_Storage_Adapter_Exception_KeyNotFound(
                'Key not found in namespace "' . $namespace . '": ' . $key
            );
        }
    }
    
    public function addKeyMeta ($namespace, $key, $name, $value) {
        $meta = $this->getKeyMeta($namespace, $key);
        
        if(array_key_exists($name, $meta)) {
            throw new Liquid_Storage_Adapter_Exception (
                'Value already exists in meta data: ' . $name
            );
        }
        
        $meta[$name] = $value;
        
        $this->replaceMeta($namespace, $key, null, $meta);
    }
    
    public function replaceKeyMeta ($namespace, $key, $name, $value) {
        $meta = $this->getKeyMeta($namespace, $key);
        
        $meta[$name] = $value;
        
        $this->replaceMeta($namespace, $key, null, $meta);
    }
    
    public function deleteKeyMeta ($namespace, $key, $name) {
        $meta = $this->getKeyMeta($namespace, $key);
        
        if(array_key_exists($name, $meta)) {
            unset($meta[$name]);
        }
        
        $this->replaceMeta($namespace, $key, null, $meta);        
    }
    
    // Entry meta data
    public function setEntryMeta (Liquid_Storage_Entry $entry, array $meta) {
        $entry->setMeta($meta);
        $this->updateEntry($entry);
    }
    
    public function getEntryMeta (Liquid_Storage_Entry $entry) {
        $this->refreshEntry($entry);
        return $entry->getMeta();
    }
    
    public function addEntryMeta (Liquid_Storage_Entry $entry, $name, $value) {
        $this->refreshEntry($entry);
        $entry->addMeta($name, $value);
        $this->updateEntry($entry);
    }
    
    public function replaceEntryMeta (Liquid_Storage_Entry $entry, $name, $value) {
        $this->refreshEntry($entry);
        $entry->replaceMeta($name, $value);
        $this->updateEntry($entry);
    }
    
    public function deleteEntryMeta (Liquid_Storage_Entry $entry, $name) {
        $this->refreshEntry($entry);
        $entry->deleteMeta($name);
        $this->updateEntry($entry);
    }
    
    public function getNamespaces ($flat = true) {
        $result = array();
        
        $namespaces = $this->find();
        
        if(is_array($namespaces)) {
            if($flat) {
                foreach($namespaces as $space => $time) {
                    $result[] = $space;
                }
            } else {
                $result = $namespaces;
            }
        }
        
        return $result;
    }
    
    public function namespaceExists ($namespace) {
        return is_array($this->find($namespace));
    }

    public function keyExists ($namespace, $key) {
        return is_array($this->find($namespace, $key));
    }

    public function entryExists (Liquid_Storage_Entry $entry) {
        $id        = $entry->getId();
        $key       = $entry->getKey();
        $namespace = $entry->getNamespace();
        
        $index = $this->find($namespace, $key);
        
        return array_key_exists($id, $index) && is_array($index[$id]);
    }
        
    public function findKeys ($namespace, $flat = true) {
        if($this->namespaceExists($namespace)) {
            $result = array();
            
            $keys = $this->find($namespace);
            
            if($flat) {
                foreach($keys as $key => $meta) {
                    $result[] = $key;
                }
            } else {
                $result = $keys;
            }
            
            return $result;
        } else {
            throw new Liquid_Storage_Adapter_Exception_NamespaceNotFound(
                'Namespace not found: ' . $namespace
            );
        }
    }
    
    public function findIndex ($namespace, $key) {
        if($this->keyExists($namespace, $key)) {
            return $this->find($namespace, $key);
        } else {
            throw new Liquid_Storage_Adapter_Exception_KeyNotFound(
                'Key not found in namespace "' . $namespace . '": ' . $key
            );
        }
    }
    
    public function findById ($id) {
        $result = array();
        
        $namespaces = $this->find();
        
        if(is_array($namespaces)) {
            foreach($namespaces as $namespace => $time) {
                $keys = $this->find($namespace);
                
                if(is_array($keys)) {
                    foreach($keys as $key => $time) {
                        $index = $this->find($namespace, $key);
                        
                        if(is_array($index)) {
                            foreach($index as $entryId => $entryMeta) {
                                if($id == $entryId) {
                                    $result[] = new Liquid_Storage_Entry(array(
                                        'id' => $entryId,
                                        'key' => $key,
                                        'namespace' => $namespace,
                                        'meta' => $entryMeta,
                                        'data' => $this->find($namespace, $key, $entryId)
                                    ));
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    public function findByMeta ($name, $value) {
        $result = array();
        
        $namespaces = $this->find();
        
        if(is_array($namespaces)) {
            foreach($namespaces as $namespace => $time) {
                $keys = $this->find($namespace);
                
                if(is_array($keys)) {
                    foreach($keys as $key => $time) {
                        $index = $this->find($namespace, $key);
                        
                        if(is_array($index)) {
                            foreach($index as $entryId => $entryMeta) {
                                if(is_array($name)) {
                                    $hits = 0;
                                    
                                    foreach($name as $metaName => $metaValue) {
                                        if(array_key_exists($metaName, $entryMeta) && $entryMeta[$metaName] == $metaValue) {
                                            $hits++;
                                        }
                                    }
                                    
                                    if($hits == count($name) || (is_numeric($value) && $hits >= $value)) {
                                        $result[] = new Liquid_Storage_Entry(array(
                                            'id' => $entryId,
                                            'key' => $key,
                                            'namespace' => $namespace,
                                            'meta' => $entryMeta,
                                            'data' => $this->find($namespace, $key, $entryId)
                                        ));
                                    }
                                } else {
                                    if(array_key_exists($name, $entryMeta) && $entryMeta[$name] == $value) {
                                        $result[] = new Liquid_Storage_Entry(array(
                                            'id' => $entryId,
                                            'key' => $key,
                                            'namespace' => $namespace,
                                            'meta' => $entryMeta,
                                            'data' => $this->find($namespace, $key, $entryId)
                                        ));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    public function findOne ($namespace, $key, $id) {
        $entry = new Liquid_Storage_Entry(array(
            'namespace' => $namespace,
            'key' => $key,
            'id' => $id
        ));
        
        $this->refreshEntry($entry);
        
        return $entry;
    }
    
    public function findFirst ($namespace, $key) {
        if($this->keyExists($namespace, $key)) {
            $index = $this->findIndex($namespace, $key);
            
            reset($index);
            $id = key($index);
            $meta = current($index);  
            
            $data = $this->find($namespace, $key, $id);
            
            return new Liquid_Storage_Entry(array(
                'namespace' => $namespace,
                'key' => $key,
                'id' => $id,
                'data' => $data,
                'meta' => $meta
            ));
        } else {
            throw new Liquid_Storage_Adapter_Exception_KeyNotFound(
                'Key not found in namespace "' . $namespace . '": ' . $key
            );
        }
    }
    
    public function findLast ($namespace, $key) {
        if($this->keyExists($namespace, $key)) {
            $index = $this->findIndex($namespace, $key);
            
            end($index);
            $id = key($index);
            $meta = current($index);  
            
            $data = $this->find($namespace, $key, $id);
            
            return new Liquid_Storage_Entry(array(
                'namespace' => $namespace,
                'key' => $key,
                'id' => $id,
                'data' => $data,
                'meta' => $meta
            ));
        } else {
            throw new Liquid_Storage_Adapter_Exception_KeyNotFound(
                'Key not found in namespace "' . $namespace . '": ' . $key
            );
        }
    }
    
    public function refreshEntry (Liquid_Storage_Entry $entry) {
        if($this->entryExists($entry)) {
            $index = $this->findIndex($entry->getNamespace(), $entry->getKey());
            $data = $this->find($entry->getNamespace(), $entry->getKey(), $entry->getId());
            $entry->setData($data);
            $entry->setMeta($index[$entry->getId()]);
        } else {
            throw new Liquid_Storage_Adapter_Exception_EntryNotFound(
                'Id not found in key "' . $entry->getKey() . '" and namespace "' . $entry->getNamespace() . '": ' . $entry->getId()
            );
        }
    }
    
    public function createEntry (Liquid_Storage_Entry $entry) {
        return $this->create($entry->getNamespace(), $entry->getKey(), $entry->getData(), $entry->getMeta());
    }
    
    public function replaceEntry (Liquid_Storage_Entry $entry) {  
        $namespace = $entry->getNamespace();
        $key = $entry->getKey();
        
        try {
            $index = $this->findIndex($namespace, $key);
        } catch(Liquid_Storage_Adapter_Exception_KeyNotFound $e) {
            $index = array();
        }
        
        if(count($index) > 0) {
            end($index);
            $id = key($index);       
        
            $this->replace($namespace, $key, $id, $entry->getData(), $entry->getMeta());
            return $id;
        } else {
            return $this->createEntry($entry);
        }
    }
    
    public function updateEntry (Liquid_Storage_Entry $entry) {
        if($this->entryExists($entry)) {
            $this->replace($entry->getNamespace(), $entry->getKey(), $entry->getId(), $entry->getData(), $entry->getMeta());
        } else {
            throw new Liquid_Storage_Adapter_Exception_EntryNotFound(
                'Could not update entry in key "' . $entry->getKey() . '" and namespace "' . $entry->getNamespace() . '": ' . $entry->getId()
            );
        }
    }
    
    public function deleteAll () {
        $this->delete();
    }
    
    public function deleteNamespace ($namespace) {
        if($this->namespaceExists($namespace)) {
            $this->delete($namespace);
        } else {
            throw new Liquid_Storage_Adapter_Exception_NamespaceNotFound(
                'Namespace not found: ' . $namespace
            );
        }
    }
    
    public function deleteKey ($namespace, $key) {
        if($this->keyExists($namespace, $key)) {
            $this->delete($namespace, $key);
        } else {
            throw new Liquid_Storage_Adapter_Exception_KeyNotFound(
                'Key not found in namespace "' . $namespace . '": ' . $key
            );
        }
    }
    
    public function deleteEntry (Liquid_Storage_Entry $entry) {
        if($this->entryExists($entry)) {
            $this->delete($entry->getNamespace(), $entry->getKey(), $entry->getId());
        } else {
            throw new Liquid_Storage_Adapter_Exception_EntryNotFound(
                'Could not delete entry in key "' . $entry->getKey() . '" and namespace "' . $entry->getNamespace() . '": ' . $entry->getId()
            );
        }
    }
    
    public function renameNamespace ($oldNamespaceName, $newNamespaceName) {
        if($oldNamespaceName === $newNamespaceName) {
            throw new Liquid_Storage_Adapter_Exception(
                'Rename cannot be performed. Old and new name are the same: ' . $oldNamespaceName
            );
        } elseif($newNamespaceName == '') {
            throw new Liquid_Storage_Adapter_Exception(
                'Rename cannot be performed. New name is empty.'
            );
        } elseif($this->namespaceExists($newNamespaceName)) {
            throw new Liquid_Storage_Adapter_Exception(
                'Rename cannot be performed. New name already exists: ' . $newNamespaceName
            );
        } elseif($this->namespaceExists($oldNamespaceName)) {
            return $this->_renameNamespace($oldNamespaceName, $newNamespaceName);            
        } else {
            throw new Liquid_Storage_Adapter_Exception_NamespaceNotFound(
                'Rename cannot be performed. Name not found: ' . $oldNamespaceName
            );
        }
    }
    
    public function renameKey ($namespace, $oldKeyName, $newKeyName) {
        if($oldKeyName == $newKeyName) {
            throw new Liquid_Storage_Adapter_Exception(
                'Rename cannot be performed. Old and new name are the same: ' . $oldKeyName
            );
        } elseif($newKeyName == '') {
            throw new Liquid_Storage_Adapter_Exception(
                'Rename cannot be performed. New name is empty.'
            );
        } elseif($this->keyExists($namespace, $newKeyName)) {
            throw new Liquid_Storage_Adapter_Exception(
                'Rename cannot be performed. New name already exists: ' . $newKeyName
            );
        } elseif($this->keyExists($namespace, $oldKeyName)) {
            return $this->_renameKey($namespace, $oldKeyName, $newKeyName);
        } else {
            throw new Liquid_Storage_Adapter_Exception_KeyNotFound(
                'Rename cannot be performed. Name not found: ' . $oldNamespaceName
            );
        }
    }
}
