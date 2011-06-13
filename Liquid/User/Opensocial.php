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
 
class Liquid_User_Opensocial implements Liquid_User {
    const STORAGE_NAMESPACE = 'opensocial';
    
    protected $appId;
    protected $secret;
    protected $auth;
    protected $profile;
    protected $storage;
    
    public function __construct ($appId, $secret) {
        $this->appId = $appId;
        $this->secret = $secret;      
    }
    
    public function setStorage (Liquid_Storage_Adapter_Abstract $storage) {
        $this->storage = $storage;  
    }
    
    protected function getStorage () {
        if(!$this->storage) {
            throw new Liquid_User_Opensocial_Exception('No storage adapter found');
        }
        
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
            
            $entry = new Liquid_Storage_Entry(array(
                'namespace' => self::STORAGE_NAMESPACE,
                'key' => $userId,
                'data' => array(
                    'created' => time(), 
                    'aliases' => array($defaultAlias), 
                    'profile' => $this->auth['profile']['entry']
                 )
            ));
            
            $this->getStorage()->createEntry($entry);

            $entry = $this->getStorage()->findLast(self::STORAGE_NAMESPACE, $userId);

            $result = $entry->getData();

            $result['new'] = true;
        }
                
        return $result;
    }
    
    public function getProfile ($userId = '@viewer') {
        $this->checkAuth();
        
        $json = file_get_contents('https://www.google.com/friendconnect/api/people/'
            .$userId
            .'/@self?fcauth='
            .$this->auth['access_token']
        );
        
        $profile = Zend_Json::decode($json);
        
        return $profile['entry'];
    }
    
    public function getUserId () {
        $this->checkAuth();

        $result = $this->auth['profile']['entry']['id'];
        
        return $result;
    }
    
    public function checkAuth () {
        if(!isset($_COOKIE['fcauth' . $this->appId])) {
            unset($_SESSION['opensocial_authenticated']);
            $this->auth = null;
            throw new Liquid_User_Opensocial_Exception('Cookie not found');
        }
        
        $url = 'https://www.google.com/friendconnect/api/people/@viewer/@self?fcauth=' . $_COOKIE['fcauth' . $this->appId];
        $hash = 'os_' . md5($url);
        
        if(!isset($_SESSION[$hash])) {
            $json = file_get_contents($url);

            try {
                $profile = Zend_Json::decode($json);
            } catch (Exception $e) {
                throw new Liquid_User_Opensocial_Exception('Could not get profile');
            }
            
            $this->auth = array('access_token' => $_COOKIE['fcauth' . $this->appId], 'profile' => $profile);
            
            $_SESSION[$hash] = serialize($this->auth);
        } else {
            $this->auth = unserialize($_SESSION[$hash]);
        }        
    }
}
