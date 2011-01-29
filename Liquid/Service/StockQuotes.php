<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Service
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */

class Liquid_Service_StockQuotes {
    protected $fixturePath = false;
    protected $serviceUrl = 'http://de.finance.yahoo.com/d/quotes.csv?f=sl1d1t1c1ohgvenxjkp&e=.csv&s=';
    
    protected function callRemote ($symbol) {
        $recordResult = false;
        
        if($this->fixturePath) {
            $fixture = new Liquid_Fixture($this->fixturePath.Liquid_Fixture::getFilename('stockquotes', $this->serviceUrl . $symbol));
            
            try {                
                $result = $fixture->getData();
                return $result;
            } catch (Liquid_Fixture_Exception $e) {
                $recordResult = true;
            }
        }
        
        $result = file_get_contents($this->serviceUrl . $symbol);
        
        if($recordResult) {
            $fixture->setData($result);
        }

        return $result;
    }
    
    public function useFixtures ($fixturePath) {
        $this->fixturePath = Liquid_Fixture::normalizePath($fixturePath);
    }
    
    public function getSymbols($index) {
        throw new Exception('Not implemented yet');
    }
    
    public function getQuote($symbol) {
        if(empty($symbol)) throw new Exception('No symbol given');
        
        if(is_array($symbol)) {
            $symbol = join(',', $symbol);
        }
        
        $data = $this->callRemote($symbol);
        
        if(empty($data)) throw new Exception('Invalid symbol');
        
        $result = array();
        
        $data = explode("\n", $data);
        
        for($i = 1; $i < count($data); $i++) {
            $quote = $this->formatServiceResponse($data[$i - 1]);
            $result[$quote['symbol']] = $quote;
        }                  
        
        return $result;
    }
    
    protected function formatServiceResponse ($data) {
        $parts = explode(';', $data);
        $date = explode('/', $parts[3]);
        $time = explode(':', $parts[2]);
        
        $timestamp = mktime($time[0], $time[1], 0, (int) $date[1], (int) $date[0], (int) $date[2]);
        
        $gmdate = gmdate('c', $timestamp);
     
        $result = array(
            'symbol' => trim($parts[0]),        
            'name' => trim($parts[10]),
            'exchange' => trim($parts[11]),
            'close' => (float) strtr($parts[14], ',', '.'),        
            'open' => (float) strtr($parts[5], ',', '.'),
            'current' => (float) strtr($parts[1], ',', '.'),                      
            'change' => strtr($parts[4], ',', '.'),            
            'high' => (float) strtr($parts[6], ',', '.'),
            'low' => (float) strtr($parts[7], ',', '.'),            
            'volume' => (int) trim($parts[8]),
            'eps' => (float) strtr($parts[9], ',', '.'),        
            '52w_low' => (float) strtr($parts[12], ',', '.'),
            '52w_high' => (float) strtr($parts[13], ',', '.'),
            'date' => $gmdate
        );               
        
        return $result;
    }
}
