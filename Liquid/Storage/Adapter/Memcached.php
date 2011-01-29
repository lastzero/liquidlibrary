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

class Liquid_Storage_Adapter_Memcached extends Liquid_Storage_Adapter_Abstract {
    protected $memcache = null;
    
    public function __construct (Memcache $memcache) {
        $this->memcache = $memcache;
    }    
    
    protected function find ($namespace = null, $key = null, $id = null) {   
        $result = $this->memcache->get($this->getStoreKey($namespace, $key, $id));        
        
        return $result;
    }

    protected function create ($namespace, $key, $data, array $meta) {       
        if(!$this->memcache->get($this->getStoreKey())) {
            $this->memcache->set($this->getStoreKey(), array());
        }
        
        if(!$this->memcache->get($this->getStoreKey($namespace))) {
            $this->memcache->set($this->getStoreKey($namespace), array());
        }
        
        if(!$this->memcache->get($this->getStoreKey($namespace, $key))) {
            $this->memcache->set($this->getStoreKey($namespace, $key), array());
        }
        
        $id = count($this->memcache->get($this->getStoreKey($namespace, $key))) + 1;
 
        $namespaceIndex = $this->memcache->get($this->getStoreKey());
 
        if(!isset($namespaceIndex[$namespace])) {
            $namespaceIndex[$namespace] = array('created' => time());
            $this->memcache->replace($this->getStoreKey(), $namespaceIndex);
        }
        
        $keyIndex = $this->memcache->get($this->getStoreKey($namespace));
        
        if(!isset($keyIndex[$key])) {
            $keyIndex[$key] = array('created' => time());
            $this->memcache->replace($this->getStoreKey($namespace), $keyIndex);
        }
        
        $index = $this->memcache->get($this->getStoreKey($namespace, $key));
        
        $index[$id] = $meta;
        
        $this->memcache->replace($this->getStoreKey($namespace, $key), $index);
        $this->memcache->set($this->getStoreKey($namespace, $key, $id), $data);
        
        return $id;
    }
    
    protected function replace ($namespace, $key, $id, $data, array $meta) {
        if(!$this->memcache->get($this->getStoreKey($namespace, $key, $id))) {
            throw new Liquid_Storage_Adapter_Exception_EntryNotFound ('Entry does not exist: ' . $id);
        }
        
        $this->replaceMeta($namespace, $key, $id, $meta);
        
        $this->memcache->replace($this->getStoreKey($namespace, $key, $id), $data);
    } 
      
    protected function replaceMeta ($namespace = null, $key = null, $id = null, array $meta) {
        if($id !== null) {
            $index = $this->memcache->get($this->getStoreKey($namespace, $key));
            $index[$id] = $meta;
            $this->memcache->replace($this->getStoreKey($namespace, $key), $index);
        } elseif ($key != null) {
            $index = $this->memcache->get($this->getStoreKey($namespace));
            $index[$key] = $meta;
            $this->memcache->replace($this->getStoreKey($namespace), $index);
        } elseif ($namespace != null) {
            $index = $this->memcache->get($this->getStoreKey());
            $index[$namespace] = $meta;
            $this->memcache->replace($this->getStoreKey(), $index);
        }
    } 
    
    protected function delete ($namespace = null, $key = null, $id = null) {
        if($id !== null) {
            $index = $this->memcache->get($this->getStoreKey($namespace, $key));
            unset($index[$id]);
            $this->memcache->replace($this->getStoreKey($namespace, $key), $index);
            
            $this->memcache->delete($this->getStoreKey($namespace, $key, $id));
        } elseif($key !== null) {
            $index = $this->memcache->get($this->getStoreKey($namespace, $key));
            
            if(is_array($index)) {
                foreach($index as $id => $meta) {
                    $this->memcache->delete($this->getStoreKey($namespace, $key, $id));
                }

                $this->memcache->delete($this->getStoreKey($namespace, $key));
            }
            
            $keyIndex = $this->memcache->get($this->getStoreKey($namespace));
        
            if(isset($keyIndex[$key])) {
                unset($keyIndex[$key]);
                $this->memcache->replace($this->getStoreKey($namespace), $keyIndex);
            }
        } elseif($namespace !== null) {
            $keyIndex = $this->memcache->get($this->getStoreKey($namespace));
            
            if(is_array($keyIndex)) {
                foreach($keyIndex as $key => $time) {
                    $this->delete($namespace, $key);
                }
            }
            
            $this->memcache->delete($this->getStoreKey($namespace));
                            
            $namespaceIndex = $this->memcache->get($this->getStoreKey());
            
            if(is_array($namespaceIndex)) {
               unset($namespaceIndex[$namespace]);
               $this->memcache->replace($this->getStoreKey(), $namespaceIndex);            
            } else {
               $this->memcache->replace($this->getStoreKey(), array());
            }
            
        } else {
            $namespaces = $this->getNamespaces();
            
            if(is_array($namespaces)) {
                foreach($namespaces as $namespace) {
                    $this->delete($namespace);                    
                }
            }
            
            $this->memcache->delete($this->getStoreKey());
        }
    }
    
    protected function _renameNamespace ($oldNamespaceName, $newNamespaceName) {        
        foreach($this->memcache->get($this->getStoreKey($oldNamespaceName)) as $key => $time) {
            $this->memcache->set($this->getStoreKey($newNamespaceName, $key), $this->memcache->get($this->getStoreKey($oldNamespaceName, $key)));
            $this->memcache->delete($this->getStoreKey($oldNamespaceName, $key));
            
            foreach($this->memcache->get($this->getStoreKey($newNamespaceName, $key)) as $id => $meta) {
                $this->memcache->set($this->getStoreKey($newNamespaceName, $key, $id), $this->memcache->get($this->getStoreKey($oldNamespaceName, $key, $id)));
                $this->memcache->delete($this->getStoreKey($oldNamespaceName, $key, $id));
            }    
        }
        
        $this->memcache->set($this->getStoreKey($newNamespaceName), $this->memcache->get($this->getStoreKey($oldNamespaceName)));
        $this->memcache->delete($this->getStoreKey($oldNamespaceName));
        
        $namespaceIndex = $this->memcache->get($this->getStoreKey());
        
        $namespaceIndex[$newNamespaceName] = $namespaceIndex[$oldNamespaceName];
        unset($namespaceIndex[$oldNamespaceName]);
        
        $this->memcache->replace($this->getStoreKey(), $namespaceIndex);
    }

    protected function _renameKey ($namespace, $oldKeyName, $newKeyName) {
        $keyIndex = $this->memcache->get($this->getStoreKey($namespace));
        
        $keyIndex[$newKeyName] = $keyIndex[$oldKeyName];
        unset($keyIndex[$oldKeyName]);
        
        $this->memcache->replace($this->getStoreKey($namespace), $keyIndex);

        $this->memcache->set($this->getStoreKey($namespace, $newKeyName), $this->memcache->get($this->getStoreKey($namespace, $oldKeyName)));
        $this->memcache->delete($this->getStoreKey($namespace, $oldKeyName));
        
        foreach($this->memcache->get($this->getStoreKey($namespace, $newKeyName)) as $id => $meta) {
            $this->memcache->set($this->getStoreKey($namespace, $newKeyName, $id), $this->memcache->get($this->getStoreKey($namespace, $oldKeyName, $id)));
            $this->memcache->delete($this->getStoreKey($namespace, $oldKeyName, $id));
        }    
    }
}
