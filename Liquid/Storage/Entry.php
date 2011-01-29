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

require_once 'Liquid/Storage/Entry/Exception.php';

class Liquid_Storage_Entry {
    private $data = null;
    private $meta = array();
    private $id = null;
    private $key = null;
    private $namespace = null;
    
    public function __construct (array $props = array()) {
        if(isset($props['data'])) {
            $this->setData($props['data']);
        }
        
        if(isset($props['meta'])) {
            $this->setMeta($props['meta']);
        }
        
        if(isset($props['id'])) {
            $this->setId($props['id']);
        }

        if(isset($props['key'])) {
            $this->setKey($props['key']);
        }

        if(isset($props['namespace'])) {
            $this->setNamespace($props['namespace']);
        }
    }
    
    public function setData ($data) {
        $this->data = $data;
    }
    
    public function setDataAsJson ($data) {
        $this->data = Zend_Json::decode($data);
    }
    
    public function setMeta (array $meta) {
        $this->meta = $meta;
    }        
    
    public function addMeta ($name, $value) {
        if(!array_key_exists($name, $this->meta)) {
            $this->meta[$name] = $value;
        } else {
            throw new Liquid_Storage_Entry_Exception('Meta information for ' . $name . ' already exists');
        }
    }
    
    public function replaceMeta ($name, $value) {
        $this->meta[$name] = $value;
    }
    
    public function deleteMeta ($name) {
        if(array_key_exists($name, $this->meta)) {
            unset($this->meta[$name]);
        } else {
            throw new Liquid_Storage_Entry_Exception('Meta information for ' . $name . ' did not exist');
        }
    }
    
    public function setId ($id) {
        if($this->id == null) {
            $this->id = $id;
        } else {
            throw new Liquid_Storage_Entry_Exception('Id already set');
        }
    }
    
    public function setKey ($key) {
        if($this->key == null) {
            $this->key = $key;
        } else {
            throw new Liquid_Storage_Entry_Exception('Key already set');
        }
    }
    
    public function setNamespace ($namespace) {
        if($this->namespace == null) {
            $this->namespace = $namespace;
        } else {
            throw new Liquid_Storage_Entry_Exception('Namespace already set');
        }
    }
    
    public function getData () {
        return $this->data;
    }
    
    public function getDataAsJson () {
        return Zend_Json::encode($this->data);
    }
    
    public function getDataHash () {
        return sha1($this->data);
    }
    
    public function getMeta () {
        return $this->meta;
    }
    
    public function getId () {
        if($this->id !== null) {
            return $this->id;
        }

        throw new Liquid_Storage_Entry_Exception('Id not set');
    }

    public function getKey () {
        if($this->key !== null) {
            return $this->key;
        }

        throw new Liquid_Storage_Entry_Exception('Key not set');
    }
    
    public function getNamespace () {
        if($this->namespace !== null) {
            return $this->namespace;
        }

        throw new Liquid_Storage_Entry_Exception('Namespace not set');
    }
    
    public function isScalar () {
        return is_scalar($this->data);
    }
    
    public function getAsArray () {
        return array(
            'id' => $this->getId(), 
            'key' => $this->getKey(), 
            'namespace' => $this->getNamespace(), 
            'data' => $this->getData(), 
            'meta' => $this->getMeta()
        );
    }
    
    public function getAsJson () {
        return Zend_Json::encode($this->getAsArray());
    }
}
