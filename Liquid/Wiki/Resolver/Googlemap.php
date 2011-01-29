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
 
class Liquid_Wiki_Resolver_Googlemap implements Liquid_Wiki_Resolver_Interface {
    protected function getImageFilename ($location) {
        $filename = PUBLIC_CACHE_PATH . 'googlemaps/' . md5($location) . '.png';
        
        if(!file_exists($filename)) {        
            file_put_contents($filename, file_get_contents('http://maps.google.com/maps/api/staticmap?center=' . 
                urlencode($location).'&zoom=14&size=640x480&maptype=roadmap&sensor=false'));
        }
        
        return $filename;
    }
    
    public function renderAsHtml ($options) {
        if(isset($options[0]) && $options[0] != '') {
            $result = '<img class="googlemap" onclick="window.open(\'http://maps.google.com/?q=' .
                urlencode($options[0]) . 
                '\', \'height=600,width=800,status=no,menubar=no,toolbar=no\');" src="http://maps.google.com/maps/api/staticmap?center=' . 
                urlencode($options[0]).'&zoom=14&size=640x480&maptype=roadmap&sensor=false" />';
                        
            if(isset($options[2])) {
                $class = 'wiki_image wiki_image_' . $options[2];
            } else {
                $class = 'wiki_image';
            }

            if(isset($options[1])) {
                $result = '<div class="'.$class.'">' . $result .'<div class="caption">'.$options[1].'</div></div>';
            }
        }

        return $result;
    }
    
    public function renderAsLatex ($options) {
        $result = '';
        
        if(isset($options[0])) {
            $filename = $this->getImageFilename($options[0]);
            
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
