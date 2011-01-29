<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Wiki
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
class Liquid_Wiki_Resolver_Youtube implements Liquid_Wiki_Resolver_Interface {
    protected function getDetails ($videoId) {        
        $json = file_get_contents('http://gdata.youtube.com/feeds/api/videos/' . urlencode($videoId) . '?alt=json');
        return Zend_Json::decode($json);
    }
    
    protected function getThumb ($videoId) {
        $filename = PUBLIC_CACHE_PATH . 'youtube/' . urlencode($videoId) . '.jpg';
        
        if(!file_exists($filename)) {        
            $details = $this->getDetails($videoId);
           
            $thumb = $details['entry']['media$group']['media$thumbnail'][3]['url'];
            
            file_put_contents($filename, file_get_contents($thumb));
        }
        
        return $filename;
    }
    
    public function renderAsHtml ($options) {
        $result = '';
        
        if(isset($options[0])) {
            $videoId = urlencode($options[0]);
            
            $result = '<iframe title="YouTube video player" class="youtube-player" type="text/html"' .
                ' width="640" height="390" src="http://www.youtube.com/embed/' . $videoId . 
                '" frameborder="0"></iframe>';                               
        }

        return $result;
    }
    
    public function renderAsLatex ($options) {
        $result = '';
        
        if(isset($options[0])) {
            $filename = $this->getThumb($options[0]);
            
            $result .= '\includegraphics[scale=0.6]{' . $filename . '}';
        }
        
        if(isset($options[1])) {
            $result = '\begin{figure}[ht] \centering' . "\n" . 
                $result . ' \caption{' . 
                Liquid_Wiki::escapeLatex($options[1]) . '}' .
                "\n" . '\end{figure}';
        }
        
        return $result;    
    }

    public function renderAsText ($options) {
        return $options[0];
    }
}
