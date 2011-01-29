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

require_once 'Text/Wiki/Default.php';
require_once 'LaTeX/Render.php';

class Liquid_Wiki_Resolver {
    const HTML = 'html';
    const LATEX = 'latex';
    const TEXT = 'text';
    const resolverStartTag = "\[\[";
	const resolverEndTag = "\]\]";
    protected $resolverInstances = array();
    protected $format = 'html';
	
	public function setFormat ($format) {
	    $this->format = $format;
	}
	
	public function getFormat ($format) {
	    return $this->format;
	}
	
	public function setResolver ($className, $object) {
	    $this->resolverInstances[$className] = $object;
	}
	
	public function getResolver ($className) {
	    if(!isset($this->resolverInstances[$className])) {
	        $filename = ucfirst($className). '.php';
	        $class = 'Liquid_Wiki_Resolver_' . ucfirst($className);
	        
	        @include_once dirname(__FILE__) . '/Resolver/' . $filename;
	        
	        if(!class_exists($class, false)) {
	            throw new Liquid_Wiki_Exception ('Resolver not found');
	        }
	        
	        $this->resolverInstances[$className] = new $class ($this);
	    }
	    
	    return $this->resolverInstances[$className];
	}
	
    protected function render ($matches) {
        $parts = explode(':', $matches[1]);
        if(count($parts) > 1) {
            $className = $parts[0];
            $options = explode('|', $parts[1]);
        } else {
            $className = 'default';
            $options = explode('|', $parts[0]);
        }
        
        try {
            $resolver = $this->getResolver($className);
            
            switch($this->format) {
                case self::HTML:
                    return $resolver->renderAsHtml($options);
                case self::TEXT:
                    return $resolver->renderAsText($options);            
                case self::LATEX:
                    return $resolver->renderAsLatex($options);
                default:
                    throw new Liquid_Wiki_Exception('Unknown render format: ' . $this->format);
            }        
        } catch (Exception $e) {
            Liquid_Log::warning($e->getMessage() . ' (' . $className . ')');
            return '';
        }
    }
    
    public function resolve ($content) {        
        $content = preg_replace_callback("|".self::resolverStartTag."([^\]]*)".self::resolverEndTag."|", array($this, 'render'), $content);

        return $content;
    }
}
