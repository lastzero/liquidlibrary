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
 
class Liquid_Wiki_Resolver_Flickr implements Liquid_Wiki_Resolver_Interface {
    protected function getThumbs ($photoId) {
        $json = file_get_contents('http://api.flickr.com/services/rest/?method=flickr.photos.getSizes'.
            '&photo_id='.urlencode($photoId).'&api_key='.urlencode(FLICKR_APP_ID).'&format=json&nojsoncallback=1');
        $result = Zend_Json::decode($json);
        return $result;
    }
    
    protected function fetchDefaultThumb ($photoId) {
        $filename = PUBLIC_CACHE_PATH . 'flickr/' . urlencode($photoId) . '.jpg';
        
        if(!file_exists($filename)) {        
            $thumbs = $this->getThumbs($photoId);
            
            file_put_contents($filename, file_get_contents($thumbs['sizes']['size'][4]['source']));
        }
        
        return $filename;
    }
    
    protected function getDefaultThumbUrl ($photoId) {
        $this->fetchDefaultThumb($photoId);
                
        return '/cache/flickr/' . urlencode($photoId) . '.jpg';
    }
    
    protected function getDefaultThumbFilename ($photoId) {
        return $this->fetchDefaultThumb($photoId);
    }
    
    public function renderAsHtml ($options) {
        $result = '';
        
        if($options[0]) {
            $parts = explode('-', $options[0]);
            $thumbs = $this->getThumbs($parts[0]);
            
            if(isset($parts[1]) && is_numeric($parts[1])) {
                $size = $parts[1];
            } else {
                $size = 4;
            }
            
            $result = '<img class="flickr" onclick="window.open(\'' .
                $thumbs['sizes']['size'][count($thumbs['sizes']['size']) - 1]['source'] . 
                '\', \'height=600,width=800,status=no,menubar=no,toolbar=no\');" src="'. 
                $thumbs['sizes']['size'][$size]['source'] . '" />';
                        
            if(isset($options[2])) {
                $class = 'wiki_image wiki_image_' . htmlspecialchars($options[2], ENT_QUOTES);
            } else {
                $class = 'wiki_image';
            }

            if(isset($options[1])) {
                $result = '<div class="'.$class.'">' . $result .'<div class="caption">'.htmlspecialchars($options[1], ENT_QUOTES).'</div></div>';
            }
        }

        return $result;
    }
    
    public function renderAsLatex ($options) {
        $result = '';
        
        if($options[0]) {
            $parts = explode('-', $options[0]);
            
            $filename = $this->getDefaultThumbFilename($parts[0]);
            
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
