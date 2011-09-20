<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_User
 * @copyright  Copyright (c) 2011 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
class Liquid_User_Local implements Liquid_User {
    const STORAGE_NAMESPACE = 'localuser';
    
    protected $auth;
    protected $profile;
    protected $storage;
    
    public function setStorage (Liquid_Storage_Adapter_Abstract $storage) {
        $this->storage = $storage;  
    }
    
    protected function getStorage () {
        return $this->storage;
    }    
    
    public function renameAlias ($oldAlias, $newAlias) {
        $userId = $this->getUserId();
        
        $entry = $this->getStorage()->findLast(self::STORAGE_NAMESPACE, $userId);            
            
        $data = $entry->getData();
        
        $data['aliases'][array_search($oldAlias, $data['aliases'])] = $newAlias;
        
        $entry->setData($data);
        
        $this->getStorage()->updateEntry($entry);
    }
    
    public function getUser () {                
        $userId = $this->getUserId();
        
        try {            
            $entry = $this->getStorage()->findLast(self::STORAGE_NAMESPACE, $userId);            
            
            $result = $entry->getData();

            $result['new'] = false;
        } catch (Liquid_Storage_Exception $e) {                        
            $defaultAlias = 'guest';
            
            if(isset($_SESSION['username'])) {
                $userName = $_SESSION['username'];
            } else {
                $userName = 'Unknown';
            }
            
            $entry = new Liquid_Storage_Entry(array(
                'namespace' => self::STORAGE_NAMESPACE,
                'key' => $userId,
                'data' => array(
                    'created' => time(), 
                    'aliases' => array($defaultAlias), 
                    'id' => $userId,
                    'name' => $userName
                 )
            ));
            
            $this->getStorage()->createEntry($entry);

            $entry = $this->getStorage()->findLast(self::STORAGE_NAMESPACE, $userId);

            $result = $entry->getData();

            $result['new'] = true;
        }
                
        return $result;
    }
    
    public function getProfile ($userId = false) {
        if(!$userId) {
            $userId = $this->getUserId();
        }
        
        $entry = $this->getStorage()->findLast(self::STORAGE_NAMESPACE, $userId);            
            
        return $entry->getData();
    }
    
    public function getUserId () {
        if(!isset($_SESSION['userid'])) {
            $this->auth = null;
            throw new Liquid_User_Local_Exception('Unauthenticated');
        }
        
        return $_SESSION['userid'];
    }
}
