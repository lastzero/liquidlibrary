<?php 
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Soap
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
require_once 'Zend/Soap/Client.php';

class Liquid_Soap_Client extends Zend_Soap_Client {
    protected $fixturePath = false;
    
    public function useFixtures ($fixturePath) {
        $this->fixturePath = $fixturePath;
    }
    
    /**
     * Sets SOAP headers for subsequent calls
     *
     * @param mixed $headers
     * @return bool
     */
    public function setSoapHeaders($headers)
    {
        if ($this->_soapClient == null) {
            $this->_initSoapClientObject();
        }
        
        $this->_soapClient->__setSoapHeaders($headers);    
    }
    
    public function __call($name, $arguments)
    {
        $recordResult = false;
        
        if($this->fixturePath) {
            $fixture = new Liquid_Fixture($this->fixturePath.Liquid_Fixture::getFilename($name, $arguments));
            
            try {                
                $result = $fixture->getData();
                return $result;
            } catch (Exception $e) {
                $recordResult = true;
            }
        }
        
        $result = parent::__call($name, $arguments);
        
        if($recordResult) {
            $fixture->setData($result);
        }

        return $result;
    }
}
