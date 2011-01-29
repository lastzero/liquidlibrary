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
 
require_once 'Zend/Log.php';
require_once 'Liquid/Soap/Client.php';

abstract class Liquid_Soap_Model
{
    const USE_WSDL_CACHE = true;
    const DONT_CACHE_WSDL = false;
    
    protected $soapClient;
    protected $wsdlFile; // Must be set by the implementation
    protected $proxyHost;
    protected $proxyPort;
    
    public function __construct ($wsdlFile)
    {
        if (! $wsdlFile) {
            throw new Exception('No WSDL file provided to connect to SOAP service');
        }
       
        $this->wsdlFile = $wsdlFile;               
    }    
    
    protected function useProxy ($proxyHost = '', $proxyPort = 8080) {
        if($this->soapClient) {
            throw new Exception('soapClient already initialized');    
        }
        
        $this->proxyHost = $proxyHost;
        $this->proxyPort = $proxyPort;
    }
    
    protected function initSoapClient ($useCache = false, $soapVersion = SOAP_1_1) {
        ini_set('soap.wsdl_cache_enabled', $useCache ? '1' : '0');

        $options = array('soap_version' => $soapVersion);
        
        if($this->proxyHost != '') {
            $options['proxy_host'] = $this->proxyHost;
            $options['proxy_port'] = $this->proxyPort;
        }
        
        $this->soapClient = new Liquid_Soap_Client($this->wsdlFile, $options); 
    }    
    
    protected function logLastSoapRequest ()
    {
        $this->log('REQUEST SOAP: ' . $this->soapClient->getLastRequest(), Zend_Log::INFO);
        $this->log('RESPONSE SOAP: ' . $this->soapClient->getLastResponse(), Zend_Log::INFO);
    }
    
    public function useFixtures ($fixturePath) {
        $this->soapClient->useFixtures($fixturePath);
    }
    
    public function log ($text, $priority) {
        Liquid_Log::log($text, $priority);
    }
}
