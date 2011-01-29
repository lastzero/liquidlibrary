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
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
class Liquid_User_Facebook {
    const STORAGE_NAMESPACE = 'facebook';
    
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
            throw new Liquid_User_Facebook_Exception('No storage adapter found');
        }
        
        return $this->storage;
    }
    
    protected function loadProfile () {
        $hash = md5('https://graph.facebook.com/me?access_token=' . $this->auth['access_token']);
        
        if(isset($_SESSION[$hash]) && $_SESSION[$hash] != '') {
            $this->profile = Zend_Json::decode($_SESSION[$hash]);
            return;
        }
        
        $json = file_get_contents('https://graph.facebook.com/me?access_token=' . $this->auth['access_token']);
        
        $result = Zend_Json::decode($json);
        
        if($result && is_array($result) && count($result) > 0 && isset($result['id'])) {       
            $this->profile = $result;
            $_SESSION[$hash] = $json;
        } else {
            throw new Liquid_User_Facebook_Exception('Could not load details');
        }           
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
        } catch (Liquid_Storage_Exception $e) {                        
            $this->loadProfile();   
            
            $defaultAlias = 'fb' . $userId;
            
            $entry = new Liquid_Storage_Entry(array(
                'namespace' => self::STORAGE_NAMESPACE,
                'key' => $userId,
                'data' => array(
                    'created' => time(), 
                    'aliases' => array($defaultAlias), 
                    'profile' => $this->profile
                 )
            ));
            
            $this->getStorage()->createEntry($entry);

            $entry = $this->getStorage()->findLast(self::STORAGE_NAMESPACE, $userId);

            $result = $entry->getData();
        }
                
        return $result;
    }
    
    public static function getProfile ($facebookId) {
        $json = file_get_contents('https://graph.facebook.com/' . $facebookId);
        
        return Zend_Json::decode($json);
    }
    
    public function getUserId () {
        $this->checkAuth();
        
        $result = $this->auth['uid'];
        
        return $result;
    }
    
    public function checkAuth () {
        if(!isset($_COOKIE['fbs_' . $this->appId])) {
            throw new Liquid_User_Facebook_Exception('Cookie not found');
        }
        
        $args = array();

        parse_str(trim($_COOKIE['fbs_' . $this->appId], '\\"'), $args);

        ksort($args);

        $payload = '';

        foreach ($args as $key => $value) {
            if ($key != 'sig') {
                $payload .= $key . '=' . $value;
            }
        }

        if (md5($payload . $this->secret) != $args['sig']) {
            $this->auth = null;
            throw new Liquid_User_Facebook_Exception('Cookie not valid');
        } else {
            $this->auth = $args;          
        }
    }
}
