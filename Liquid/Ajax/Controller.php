<?php 
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Ajax
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
require_once 'Zend/Json.php';
require_once 'Zend/Controller/Action.php';
require_once 'Liquid/Ajax/Handler/Abstract.php';
require_once 'Liquid/Ajax/Controller/Exception.php';

class Liquid_Ajax_Controller extends Zend_Controller_Action {
    const VERSION               = 1;
    
    const BACKEND_NOT_SET       = -2;
    const BACKEND_DISABLED      = -1;
    const BACKEND_OBJECT        = 1;
    const BACKEND_JSON_REST     = 2;
    const BACKEND_JSON_RPC      = 3;
    const BACKEND_XML_REST      = 4;  
    const BACKEND_SOAP          = 5;
    
    const CACHE_DISABLED        = -1;
    const CACHE_SESSION         = 1;
    const CACHE_GLOBAL          = 2;
    
    const ERROR_PARSE           = -32768;
    const ERROR_INVALID_REQUEST = -32600;
    const ERROR_INVALID_METHOD  = -32601;
    const ERROR_INVALID_PARAMS  = -32602;
    const ERROR_INTERNAL        = -32603;
    const ERROR_OTHER           = -32000;
    
    private $debugMode = false; // Output debug messages
    private $developmentMode = false; // Verbose logs
    private $csrfProtection = true;    
    
    private $cacheInSeconds = 0;
    private $cacheScope = self::CACHE_DISABLED;
    private $cacheHandler = null;
    
    private $ajaxHandler = null;
    private $backend = self::BACKEND_NOT_SET;
    private $backendOptions = array();
    
    private $clientConfig = array();

    private $loadBalancingServers = array();
    
    private $aggregationLimit = 10;
   
    private function getNextConnectionNumber () {
        if(!isset($_SESSION['ajaxConnectionNumber']) || empty($_SESSION['ajaxConnectionNumber'])) {
            $_SESSION['ajaxConnectionNumber'] = 1;
        } else {
            $_SESSION['ajaxConnectionNumber']++;
        }
        
        return $_SESSION['ajaxConnectionNumber'];
    }
    
    protected function getSecret () {
        if(!isset($_SESSION['ajaxSecret']) || empty($_SESSION['ajaxSecret'])) {
            $_SESSION['ajaxSecret'] = md5(mt_rand(0, 32) . time());
        }
        
        return $_SESSION['ajaxSecret'];
    }
    
    protected function setClientConfig(Array $config) {
        $this->clientConfig = $config;
    }
    
    protected function getClientConfig() {
        return $this->clientConfig;
    }    
    
    protected function enableDebugMode() {
        return $this->debugMode = true;
    }

    protected function disableDebugMode() {
        return $this->debugMode = false;
    }
    
    protected function enableCsrfProtection() {
        return $this->csrfProtection = true;
    }

    protected function disableCsrfProtection() {
        return $this->csrfProtection = false;
    }        
       
    protected function enableDevelopmentMode() {
        return $this->developmentMode = true;
    }

    protected function disableDevelopmentMode() {
        return $this->developmentMode = false;
    }
    
    protected function getAggregationLimit() {
        return $this->aggregationLimit;
    }
    
    protected function setAggregationLimit($limit) {
        $this->aggregationLimit = (int) $limit;
    }
    
    public function init () {
        if($this->getRequest()->isXmlHttpRequest()) {
            header("Content-Type: application/json");
        }
    }
    
    public function initAction () {
        $connectionNumber = $this->getNextConnectionNumber();

        $result = array(
            'version'           => self::VERSION,
            'secret'            => $this->getSecret(),
            'debugMode'         => $this->debugMode,
            'developmentMode'   => $this->developmentMode,
            'connectionHash'    => md5($this->getSecret() . $connectionNumber),
            'connectionNumber'  => $connectionNumber,
            'handler'           => $this->getAjaxHandler()->getName(),
            'config'            => $this->getClientConfig()
        );        
        
        echo Zend_Json::encode($result);
        
        $this->disableBackend();
    }
    
    protected function setLoadBalancingServers (Array $servers) {
        $this->loadBalancingServers = $servers;
    }
    
    private function getRandomLoadBalancingServer () {
        if(count($this->loadBalancingServers) > 0) {
            return $this->loadBalancingServers[array_rand($this->loadBalancingServers)];
        } else {
            return $_SERVER['SERVER_NAME'];
        }
    }

    public function aggregateAction () { 
        $multi_handle = curl_multi_init();
        $curl_handles = array();
        
        $rpcRequestList = $this->getAjaxHandler()->getRequest();

        if(count($rpcRequestList) > $this->getAggregationLimit()) {
            throw new Liquid_Ajax_Controller_Exception('Too many aggregated request: ' . count($rpcRequestList));
        }

        foreach($rpcRequestList as $i => $request) {
            $curl_handles[$i] = curl_init();

            $url = 'http://' . $this->getRandomLoadBalancingServer() 
                . '/' . $this->getRequest()->getControllerName()
                . '/' . $request['service'] 
                . '?t=' . urlencode($this->getSecret());
            
            curl_setopt($curl_handles[$i], CURLOPT_URL, $url);

            curl_setopt($curl_handles[$i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handles[$i], CURLOPT_POST, true); 
            curl_setopt($curl_handles[$i], CURLOPT_COOKIE, @$_SERVER['HTTP_COOKIE']); // Passtrough cookie data
            curl_setopt($curl_handles[$i], CURLOPT_POSTFIELDS, $this->getAjaxHandler()->getRequest(
                $request['method'], 
                (array) @$request['params'], 
                @$request['id']
            ));
            
            curl_multi_add_handle($multi_handle, $curl_handles[$i]);
        }
        
                
        $active = null;

        do {
            curl_multi_exec($multi_handle, $active);
        } while($active);    
        
        $results = array();
        
        foreach($curl_handles as $handle) {
            $content = curl_multi_getcontent($handle);
            if(!empty($content)) {
                try {
                    $data = Zend_Json::decode($content);
                    $results[] = $data;
                } catch(Exception $e) {
                    $results[] = $content;
                }
            }
            curl_multi_remove_handle($multi_handle, $handle);
        }   
        
        curl_multi_close($multi_handle);

        $this->getAjaxHandler()->sendAggregatedResponse($results);

        $this->disableBackend();
    }
    
    protected function getErrorDataFromException (Exception $e) {
        $result = array('class' => get_class($e));
            
        if($this->developmentMode) {
            $result['trace'] = $e->getTrace();
            $result['file'] = $e->getFile();
            $result['line'] = $e->getLine();
        }
        
        return $result;
    }
    
    protected function setObject ($obj) {
        $this->backend = self::BACKEND_OBJECT;
        $this->backendOptions['object'] = $obj;
    }

    protected function setJsonRestService ($url) {
        $this->backend = self::BACKEND_JSON_REST;
        $this->backendOptions['url'] = $url;
    }
    
    protected function setJsonRpcService ($url) {
        $this->backend = self::BACKEND_JSON_RPC;
        $this->backendOptions['url'] = $url;
    }

    protected function setXmlRestService ($url) {
        $this->backend = self::BACKEND_XML_REST;
        $this->backendOptions['url'] = $url;
    }    
    
    protected function setSoapService ($wsdl) {
        $this->backend = self::BACKEND_SOAP;
        $this->backendOptions['wsdl'] = $wsdl;
    }
    
    protected function disableBackend () {
        $this->backend = self::BACKEND_DISABLED;
    }
    
    protected function setAjaxHandler (Liquid_Ajax_Handler_Abstract $handler) {
        $this->ajaxHandler = $handler; 
        $this->ajaxHandler->setSecret($this->getSecret());       
    }
    
    protected function getAjaxHandler () {
        if(!$this->ajaxHandler->isConnected()) {
            $this->ajaxHandler->connect();
        }
        
        return $this->ajaxHandler;
    }    
    
    protected function setCacheHandler (Zend_Cache_Backend $handler) {
        $this->cacheHandler = $handler; 
    }
    
    protected function getCacheHandler () {
        return $this->cacheHandler;
    }
    
    private function useCache () {
        return (
            $this->cacheHandler != null &&
            $this->cacheInSeconds > 0 && 
            $this->cacheScope != self::CACHE_DISABLED
        );
    }
        
    protected function useSessionCache ($seconds = 60) {
        $this->cacheInSeconds = $seconds;
        $this->cacheScope = self::CACHE_SESSION;
    }
    
    protected function useGlobalCache ($seconds = 60) {
        $this->cacheInSeconds = $seconds;
        $this->cacheScope = self::CACHE_GLOBAL;
    }
    
    protected function cancelRequest ($message, $data = null) {
        $request = $this->getAjaxHandler()->getRequest();
        
        $this->backend = self::BACKEND_DISABLED;
        
        return $this->getAjaxHandler()->sendError($message, self::ERROR_OTHER, $data, $request['id']);
    }

    protected function sendError ($message = null, $code = self::ERROR_OTHER, $data = null, $id = null) {
        return $this->getAjaxHandler()->sendError($message, $code, $data, $id);
    } 
    
    protected function sendSuccess ($result = null, $id = null) {
        return $this->getAjaxHandler()->sendSuccess($result, $id);        
    } 
    
    protected function getCacheId ($request, $scope) {
        switch($scope) {
            case self::CACHE_GLOBAL:
                return md5(
                    $this->getRequest()->getActionName() . $request['method'] . print_r($request['params'], true)
                );
            case self::CACHE_SESSION:
                return md5(
                    $this->getRequest()->getActionName() . session_id() . $request['method'] . print_r($request['params'], true)
                );            
        }
        
        throw new Liquid_Ajax_Controller_Exception ('Invalid cache scope');
    }

    public function unauthorizedAction () {
        $this->getResponse()->setRawHeader('HTTP/1.1 401 Unauthorized');
        $this->disableBackend();
    }
    
    public function debugAction () {           
        $this->setObject(new Liquid_Service_Debug());
    }

    public function preDispatch() {
        $action = $this->getRequest()->getActionName();
        
        if($action != 'init' && $action != 'unauthorized') {
            if($this->csrfProtection && $this->getRequest()->getParam('t') != $this->getSecret()) { // CSRF Protection             
                $this->_forward('unauthorized');
            } else {
                // Send optional HTTP Header, for example to close connection immidiately and proceed in the background
                $this->getAjaxHandler()->sendHttpHeader(); 
            }
        }
    }
        
    public function postDispatch() {
        $this->_helper->viewRenderer->setNoRender();
        
        if($this->backend == self::BACKEND_DISABLED) {
            return;
        }
    
        $request = $this->getAjaxHandler()->getRequest();

        if($this->backend == self::BACKEND_NOT_SET) {
            return $this->sendError('Method not found', self::ERROR_INTERNAL, null, $request['id']);
        }
                
        if (empty($request['method']) || !preg_match('/^[a-z][a-z0-9_.\/]*$/i', $request['method'])) {
            return $this->sendError('Invalid Request', self::ERROR_INVALID_METHOD, null, $request['id']);
        }

        if($this->useCache()) {
            $cacheId = $this->getCacheId($request, $this->cacheScope);
            $cached = $this->getCacheHandler()->load($cacheId);
            
            if($cached) {           
                return $this->sendSuccess(unserialize($cached), $request['id']);
            }
        }
        
        try{    
            $result = $this->callBackend($request);                        
            
            $this->sendSuccess($result, $request['id']);
            
            if($this->useCache()) {
                $this->getCacheHandler()->save(serialize($result), $cacheId, array(), $this->cacheInSeconds);
            }            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), $e->getCode(), $this->getErrorDataFromException($e), $request['id']);
        }
    }
    
    private function callBackend ($request) {
        switch($this->backend) {
            case self::BACKEND_OBJECT:
                $result = call_user_func_array(
                    array(
                        $this->backendOptions['object'], 
                        $request['method']
                    ), 
                    (array) @$request['params']
                );
                break;
            case self::BACKEND_JSON_REST:
                if($request['params']) {
                    $result = Zend_Json::decode(
                        http_post_fields(
                            $this->backendOptions['url'] . $request['method'], 
                            $request['params']
                        )
                    );
                } else {
                    $result = Zend_Json::decode(
                        file_get_contents($this->backendOptions['url'] . $request['method'])
                    );
                }
                break;
            case self::BACKEND_JSON_RPC:
                $result = http_post_data(
                    $this->backendOptions['url'],
                    Zend_Json::encode(
                        array(
                            'method' => $request['method'], 
                            'params' => (array) @$request['params'], 
                            'id' => @$request['id']
                        )
                    )
                );
                break;
            case self::BACKEND_XML_REST:
                require_once 'Zend/Rest/Client.php';
                $client = new Zend_Rest_Client($this->backendOptions['url']);
                $result = $client->__call($request['method'], $request['params'])->get();
                break;
            case self::BACKEND_SOAP:
                require_once 'Zend/Soap/Client.php';
                $client = new Zend_Soap_Client($this->backendOptions['wsdl']);
                $result = $client->__call($request['method'], $request['params']);
                break;          
        }
            
        return $result;
    }    
}
