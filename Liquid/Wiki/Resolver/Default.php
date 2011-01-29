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
 
class Liquid_Wiki_Resolver_Default implements Liquid_Wiki_Resolver_Interface {
    public function renderAsHtml ($options) {
        if(isset($options[1])) {
            if($options[1] != '') {
                return '<a rel="page" class="wiki" href="/wiki/' . rawurlencode(html_entity_decode($options[0])) . '">' . $options[1] . '</a>';
            } else {
                return '';
            }
        }
        
        return '<a rel="page" class="wiki" href="/wiki/' . rawurlencode(html_entity_decode($options[0])) . '">' . $options[0] . '</a>';
    }
    
    public function renderAsLatex ($options) {
        if(isset($options[1])) {
            return $options[1];
        } else {
            return $options[0];
        }
    }
    
    public function renderAsText ($options) {
        if(isset($options[1])) {
            return $options[1];
        } else {
            return $options[0];
        }
    }        
}
