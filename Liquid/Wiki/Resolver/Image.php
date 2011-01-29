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
 
class Liquid_Wiki_Resolver_Image implements Liquid_Wiki_Resolver_Interface {
    public function renderAsHtml ($options) {
        $result = '';
        
        if(in_array(strrchr($options[0], '.'), array('.jpg', '.gif', '.png'))) {
            if(isset($options[1])) {
                $divClass = 'wiki_image wiki_image_' . $options[1];
                $imgClass = $options[1];
            } else {
                $divClass = 'wiki_image';
                $imgClass = 'wiki';
            }

            $result = '<img src="/img/' . $options[0] . '" class="' . $imgClass . '" />';
                        
            
            if(isset($options[2]) && $options[1] != 'latex') {
                $result = '<div class="'.$divClass.'">' . $result . '<div class="caption">' . 
                    $options[2] . '</div></div>';
            }
        }

        return $result;
    }
    
    public function renderAsLatex ($options) {
        $result = '';
        
        if(in_array(strrchr($options[0], '.'), array('.jpg', '.gif', '.png')) && strpos($options[0], '..') === false) {
            $file = str_replace('\_', '_', $options[0]);
            $result .= '\includegraphics[scale=0.5]{' . HTDOCS_PATH . 'img/' . $file . '}';
        }
        
        if(isset($options[2])) {
            $result = '\begin{figure}[ht] \centering' . "\n" . 
                $result . ' \caption{' . 
                $options[2] . '}' .
                "\n" . '\end{figure}';
        }
        
        return $result;        
    }

    public function renderAsText ($options) {
        return $options[0];
    }
}
