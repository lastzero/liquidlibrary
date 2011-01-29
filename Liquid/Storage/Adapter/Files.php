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

class Liquid_Storage_Adapter_Files extends Liquid_Storage_Adapter_Abstract {
    protected $directory = '';
    protected $locks = array();
    
    public function __construct ($directory) {
        $this->setDirectory($directory);
    }
    
    public function __destruct () {
        foreach($this->locks as $key => $handle) {
            fflush($handle);
            flock($handle, LOCK_UN); 
            fclose($handle);            
        }
    }
        
    public function setDirectory ($directory) {
        if(strrpos($directory, DIRECTORY_SEPARATOR) !== (strlen($directory) - 1)) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        $this->directory = $directory;
    }
    
    public function getDirectory () {
        return $this->directory;
    }       
    
    protected function fileGet ($key, $lock = false) {
        $filename = $this->getDirectory() . $key;

        if(file_exists($filename)) {
            if($lock) {
                try {
                    $this->getLock($key);
                } catch (Liquid_Storage_Adapter_Exception $e) {
                    $this->setLock($key, fopen($filename, 'r+'));                                   
                }
            }

            $result = file_get_contents($filename);
            
            $ext = strrchr(strrchr($key, '/'), '.');

            if($ext == '' || $ext == '.json') {
                $result = Zend_Json::decode($result);
            }            

            return $result;
        } else {
            return false;
        }
    }
    
    protected function fileSet ($key, $val) {
        $filename = $this->getDirectory() . $key;
        
        if(file_exists($filename)) {
            return false;
        }  
        
        $dir = dirname($filename);
            
        if(!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $ext = strrchr(strrchr($key, '/'), '.');
            
        if($ext == '' || $ext == '.json') {
            file_put_contents($filename, Zend_Json::encode($val));
        } else {
            file_put_contents($filename, $val);
        }        
        
        return true;
    }

    protected function fileDelete ($key) {
        if(file_exists($this->getDirectory() . $key)) {
            if(is_file($this->getDirectory() . $key)) {
                unlink($this->getDirectory() . $key);
            } elseif(is_dir($this->getDirectory() . $key)) {
                rmdir($this->getDirectory() . $key);
            }
        }
    }
    
    protected function setLock ($key, $handle) { 
        if(isset($this->locks[$key])) {
            throw new Liquid_Storage_Adapter_Exception ('Lock already exists: ' . $key);
        }

        flock($handle, LOCK_EX); 
        $this->locks[$key] = $handle;
    }
    
    protected function getLock ($key) {
        if(!isset($this->locks[$key])) {
            throw new Liquid_Storage_Adapter_Exception ('Could not find lock for key: ' . $key);
        }
        
        return $this->locks[$key];
    }

    protected function clearLock ($key) {
        if(!isset($this->locks[$key])) {
            throw new Liquid_Storage_Adapter_Exception ('Could not find lock for key: ' . $key);
        }
        
        fflush($this->locks[$key]);
        flock($this->locks[$key], LOCK_UN); 
        fclose($this->locks[$key]);        
        unset($this->locks[$key]);
    }
    
    protected function fileReplace($key, $val) {
        $filename = $this->getDirectory() . $key;
        
        if(!file_exists($filename)) {
            return false;
        }

        $handle = $this->getLock($key);
        
        ftruncate($handle, 0);
        rewind($handle);
        
        try {
            $ext = strrchr(strrchr($key, '/'), '.');
                
            if($ext == '' || $ext == '.json') {
                fwrite($handle, Zend_Json::encode($val));
            } else {
                fwrite($handle, $val);
            }
        } catch (Exception $e) {
            $this->clearLock($key);
            throw new Liquid_Storage_Adapter_Exception ('Data could not be replaced: ' . $e->getMessage());
        }          
        
        $this->clearLock($key);
        
        return true;
    }

    protected function find ($namespace = null, $key = null, $id = null) {
        $result = $this->fileGet($this->getStoreKey($namespace, $key, $id));        
        
        return $result;
    }
    
    protected function onCreate (&$id, &$data, &$meta) {
        if(is_scalar($data)) {
            $fileinfo = new Liquid_Fileinfo($data);
            
            if($fileinfo->getCharset()) {
                $meta['charset'] = $fileinfo->getCharset();
            }
            
            if($fileinfo->getMime()) {
                $meta['mime'] = $fileinfo->getMime();
            }
            
            if($fileinfo->getExtension() != '') {
                $id .= '.' . $fileinfo->getExtension();                
            } else {
                $id .= '.json';
            }
        } else {
            $id .= '.json';
        }
        
        $meta['created'] = time();
    }

    protected function create ($namespace, $key, $data, array $meta) {       
        if(!$this->fileGet($this->getStoreKey())) {
            $this->fileSet($this->getStoreKey(), array());
        }
        
        if(!$this->fileGet($this->getStoreKey($namespace))) {
            $this->fileSet($this->getStoreKey($namespace), array());
        }
        
        if(!$this->fileGet($this->getStoreKey($namespace, $key))) {
            $this->fileSet($this->getStoreKey($namespace, $key), array());
        }
        
        $id = count($this->fileGet($this->getStoreKey($namespace, $key))) + 1;
        
        $this->onCreate($id, $data, $meta);                

        $namespaceIndex = $this->fileGet($this->getStoreKey(), true);
 
        if(!isset($namespaceIndex[$namespace])) {
            $namespaceIndex[$namespace] = array('created' => time());
            $this->fileReplace($this->getStoreKey(), $namespaceIndex);
        } else {
            $this->clearLock($this->getStoreKey());
        }
        
        $keyIndex = $this->fileGet($this->getStoreKey($namespace), true);
        
        if(!isset($keyIndex[$key])) {
            $keyIndex[$key] = array('created' => time());
            $this->fileReplace($this->getStoreKey($namespace), $keyIndex);
        } else {
            $this->clearLock($this->getStoreKey($namespace));
        }
        
        $index = $this->fileGet($this->getStoreKey($namespace, $key), true);
        
        $index[$id] = $meta;
        
        $this->fileReplace($this->getStoreKey($namespace, $key), $index);
        $this->fileSet($this->getStoreKey($namespace, $key, $id), $data);
        
        return $id;
    }
    
    protected function onReplace ($id, &$data, &$meta) {
        if(is_scalar($data)) {
            $fileinfo = new Liquid_Fileinfo($data);
            
            if($fileinfo->getCharset()) {
                $meta['charset'] = $fileinfo->getCharset();
            }
            
            if($fileinfo->getMime()) {
                $meta['mime'] = $fileinfo->getMime();
            }
        }
        
        $meta['updated'] = time();
    }
        
    protected function replace ($namespace, $key, $id, $data, array $meta) {
        if(!$this->fileGet($this->getStoreKey($namespace, $key, $id), true)) {
            throw new Liquid_Storage_Adapter_Exception_EntryNotFound ('Entry does not exist: ' . $id);
        }
        
        $this->onReplace($id, $data, $meta);     
        
        $this->replaceMeta($namespace, $key, $id, $meta);           
                
        $this->fileReplace($this->getStoreKey($namespace, $key, $id), $data);
    }  
    
    protected function replaceMeta ($namespace = null, $key = null, $id = null, array $meta) {
        if($id !== null) {
            $index = $this->fileGet($this->getStoreKey($namespace, $key), true);
            $index[$id] = $meta;
            $this->fileReplace($this->getStoreKey($namespace, $key), $index);
        } elseif ($key != null) {
            $index = $this->fileGet($this->getStoreKey($namespace), true);
            $index[$key] = $meta;
            $this->fileReplace($this->getStoreKey($namespace), $index);
        } elseif ($namespace != null) {
            $index = $this->fileGet($this->getStoreKey(), true);
            $index[$namespace] = $meta;
            $this->fileReplace($this->getStoreKey(), $index);
        }
    } 
    
    protected function delete ($namespace = null, $key = null, $id = null) {
        if($id !== null) {
            $index = $this->fileGet($this->getStoreKey($namespace, $key), true);
            unset($index[$id]);
            $this->fileReplace($this->getStoreKey($namespace, $key), $index);
            
            $this->fileDelete($this->getStoreKey($namespace, $key, $id));
        } elseif($key !== null) {
            $index = $this->fileGet($this->getStoreKey($namespace, $key));
            
            if(is_array($index)) {
                foreach($index as $id => $meta) {
                    $this->fileDelete($this->getStoreKey($namespace, $key, $id));
                }

                $this->fileDelete($this->getStoreKey($namespace, $key));
                $this->fileDelete('index/' . urlencode($namespace) . '/' . urlencode($key));
                $this->fileDelete('store/' . urlencode($namespace) . '/' . urlencode($key));
            }
            
            $keyIndex = $this->fileGet($this->getStoreKey($namespace), true);
        
            if(isset($keyIndex[$key])) {
                unset($keyIndex[$key]);
                $this->fileReplace($this->getStoreKey($namespace), $keyIndex);
            } else {               
                $this->clearLock($this->getStoreKey($namespace));
            }
        } elseif($namespace !== null) {
            $keyIndex = $this->fileGet($this->getStoreKey($namespace));
            
            if(is_array($keyIndex)) {
                foreach($keyIndex as $key => $time) {
                    $this->delete($namespace, $key);
                }
            }
            
            $this->fileDelete($this->getStoreKey($namespace));
            $this->fileDelete('index/' . urlencode($namespace));
            $this->fileDelete('store/' . urlencode($namespace));
                
            $namespaceIndex = $this->fileGet($this->getStoreKey(), true);
            
            if(is_array($namespaceIndex)) {
               unset($namespaceIndex[$namespace]);
               $this->fileReplace($this->getStoreKey(), $namespaceIndex);            
            } else {
               $this->fileReplace($this->getStoreKey(), array());
            }
            
        } else {
            $namespaces = $this->getNamespaces();
            
            if(is_array($namespaces)) {
                foreach($namespaces as $namespace) {
                    $this->delete($namespace);                    
                }
            }

            $this->fileDelete($this->getStoreKey());
        }
    }
        
    protected function _renameNamespace ($oldNamespaceName, $newNamespaceName) {  
        $old = urlencode($oldNamespaceName);
        $new = urlencode($newNamespaceName);
       
        rename($this->getDirectory() . 'index/' . $old, $this->getDirectory() . 'index/' . $new);        
        rename($this->getDirectory() . 'store/' . $old, $this->getDirectory() . 'store/' . $new);
        
        // Update Index
        $namespaceIndex = $this->fileGet($this->getStoreKey(), true);
            
        if(is_array($namespaceIndex)) {
            $namespaceIndex[$newNamespaceName] = $namespaceIndex[$oldNamespaceName];
            unset($namespaceIndex[$oldNamespaceName]);
            $this->fileReplace($this->getStoreKey(), $namespaceIndex);            
        } else {
            $this->fileReplace($this->getStoreKey(), array());
        }
    }

    protected function _renameKey ($namespace, $oldKeyName, $newKeyName) {
        if(is_array($oldKeyName)) {
            $oldKeyName = implode('/', $oldKeyName);
        }

        if(is_array($newKeyName)) {
            $newKeyName = implode('/', $newKeyName);
        }
        
        $old = urlencode($namespace) . '/' . urlencode($oldKeyName);
        $new = urlencode($namespace) . '/' . urlencode($newKeyName);

        rename($this->getDirectory() . 'index/' . $old, $this->getDirectory() . 'index/' . $new);        
        rename($this->getDirectory() . 'store/' . $old, $this->getDirectory() . 'store/' . $new);
        
        // Update Index
        $keyIndex = $this->fileGet($this->getStoreKey($namespace), true);
            
        if(is_array($keyIndex)) {
            $keyIndex[$newKeyName] = $keyIndex[$oldKeyName];
            unset($keyIndex[$oldKeyName]);
            $this->fileReplace($this->getStoreKey($namespace), $keyIndex);            
        } else {
            $this->fileReplace($this->getStoreKey(), array());
        }
    }
}
