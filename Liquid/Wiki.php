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

class Liquid_Wiki {
    protected $pear = null;
    protected $headings = array();
    protected $footnotes = 0;
    protected $wikiBaseUrl = '';
    protected $latexPath = '';
	protected $resolvers = array('image');
    protected $numberedHeadings = true;
    protected $resolverStartTag = "\[\[";
    protected $resolverEndTag	= "\]\]";	
    
    public function __construct ($wikiBaseUrl = '', $latexPath = '') {
        $this->wikiBaseUrl = $wikiBaseUrl;
        $this->latexPath = $latexPath;        
        
        $wiki = new Text_Wiki();
        
        $this->pear = $wiki->factory('Default');       

        $this->pear->disableRule('freelink');
        $this->pear->disableRule('interwiki');
        $this->pear->disableRule('wikilink');
        $this->pear->setFormatConf('Xhtml', 'charset', 'UTF-8');
        $this->pear->setFormatConf('Xhtml', 'quotes', ENT_QUOTES);
        $this->pear->setFormatConf('Xhtml', 'translate', HTML_SPECIALCHARS);
        $this->pear->setRenderConf('Xhtml', 'url', 'images', FALSE);
        $this->pear->setRenderConf('Xhtml', 'url', 'css_descr', 'http');
        $this->pear->setRenderConf('Xhtml', 'url', 'target', FALSE);
        $this->pear->setRenderConf('Xhtml', 'table', 'css_table', 'wiki');
        $this->pear->setRenderConf('Xhtml', 'table', 'css_td', 'wiki');			
        $this->pear->setRenderConf('Xhtml', 'table', 'css_th', 'wiki');
        $this->pear->setRenderConf('Xhtml', 'table', 'css_tr', 'wiki'); 
    }
    
    protected function unescapeLatexFormula ($content) {
        $txt = $content[1];
        $txt = str_replace("\\\\", "\\", $txt);
        $txt = str_replace('\#', '#', $txt);
        $txt = str_replace('\$', '$', $txt);
        $txt = str_replace('\%', '%', $txt);
        $txt = str_replace('\^', '^', $txt);
        $txt = str_replace('\&', '&', $txt);
        $txt = str_replace('\_', '_', $txt);
        $txt = str_replace('\{', '{', $txt);
        $txt = str_replace('\}', '}', $txt);
        
        // Typeset things a bit prettier than normas
        $txt = str_replace('$\sim$', '~', $txt);
        $txt = str_replace('\ldots', '...', $txt);
        
        // security filter: try to match against LaTeX-Tags Blacklist
        for ($i = 0; $i < sizeof(LaTeX_Render::$_latex_tags_blacklist[$i]); $i++) {
            if (stristr($txt, LaTeX_Render::$_latex_tags_blacklist[$i])) {
            	throw new Liquid_Wiki_Exception ('Insecure LaTeX formula: ' . LaTeX_Render::$_latex_tags_blacklist[$i]);
            }
        }
        
        return $txt . ' ';
    }
    
    public static function escapeLatex ($txt) {
        $txt = str_replace("\\", "\\\\", $txt);
        $txt = str_replace('#', '\#', $txt);
        $txt = str_replace('$', '\$', $txt);
        $txt = str_replace('%', '\%', $txt);
        $txt = str_replace('^', '\^', $txt);
        $txt = str_replace('&', '\&', $txt);
        $txt = str_replace('_', '\_', $txt);
        $txt = str_replace('{', '\{', $txt);
        $txt = str_replace('}', '\}', $txt);
        
        // Typeset things a bit prettier than normas
        $txt = str_replace('~',   '$\sim$', $txt);
        $txt = str_replace('...', '\ldots', $txt);

        return $txt;
    }
    
    protected function getLatexHeader ($class = 'article', $language = '') {
        $locale = Zend_Registry::get('Zend_Locale')->toString();           
        $result = '';
        
        switch($locale) {
            case 'de_DE':
            case 'de_AT':
            case 'de_CH':
                $result .= "\\documentclass[a4paper]{" . $class . "}\n".
                    "\\usepackage{a4wide}\n";
                break;
            case 'en_US':
                $result .= "\\documentclass[letter]{" . $class . "}\n".
                    "\\usepackage{fullpage}\n";
                break;
            default:
                $result .= "\\documentclass[a4paper]{" . $class . "}\n".
                    "\\usepackage{a4wide}\n";
        }
        
        if($language) {
            switch(strtolower($language)) {
                case 'german': 
                    $result .= "\\usepackage{ngerman}\n";
                    break;
            }
        }
        
        return $result;
    }
    
    public function renderAsLatex ($result, $author = '') {  
        $detect = new Text_LanguageDetect();
        $language = $detect->detectSimple($result);
        
		$header = $this->getLatexHeader('article', $language);

        $this->pear->setRenderConf('Latex', 'heading', 'article', true);
        
		$result = $this->raw_escape_tags($result);
		$result = $this->convert_mediawiki_to_pear_syntax($result);
		
		$lines = explode("\n", $result);
        
        if(isset($lines[0]) && strpos($lines[0], '+') === 0 && substr_count($lines[0], '+') == 1) {
            $title = trim(substr($lines[0], 1));
            array_shift($lines);
            $result = implode("\n", $lines);            
        }
		
		$result = $this->clean_urls($result);
		$result = $this->render_anchors($result);
        $result = strtr($result, array('<math>' => "[tex]\n\n".'\begin{displaymath}'."\n", '</math>' => "\n".'\end{displaymath}'."\n[/tex]"));
		$result = $this->pear->transform($result, 'latex');
		$result = $this->render_escaped_shortcuts($result);
		$result = $this->resolve($result, Liquid_Wiki_Resolver::LATEX);
		$result = preg_replace_callback("#\[tex\](.*?)\[/tex\]#si", array($this, 'unescapeLatexFormula'), $result);
        $result = str_replace('&#91;', '[', $result);
        $result = str_replace('&#93;', ']', $result);

        $header = $header .
            "\\usepackage{ulem}\n".
            "\\usepackage[utf8]{inputenc}\n".
            "\\usepackage[T1]{fontenc}\n".
            "\\usepackage{times}\n".
            "\\usepackage{amsmath}\n".
            "\\usepackage{amsfonts}\n".
            "\\usepackage{amssymb}\n".
            "\\usepackage{graphicx}\n".
            // "\\pagestyle{headings}\n".
            "\\begin{document}\n";
        
        if($title) {
            $date = new Zend_Date();
            $header .= "\\title{".self::escapeLatex($title)."}\n".
                "\\author{".self::escapeLatex($author)."} \n".
                "\\date{".$date->toString(Zend_Date::DATE_LONG)."}\n". 
                "\\maketitle\n";
        }
        
        $result = $header . $result;
		
		return $result;
    }
    
    public function renderAsLatexBook ($result, $title = '', $author = '') {  
        $detect = new Text_LanguageDetect();
        $language = $detect->detectSimple($result);

        $header = $this->getLatexHeader('report', $language);

        $this->pear->setRenderConf('Latex', 'heading', 'article', false);

        $result = $this->raw_escape_tags($result);
        $result = $this->convert_mediawiki_to_pear_syntax($result);

        $lines = explode("\n", $result);
        
        if(isset($lines[0]) && strpos($lines[0], '+') === 0 && substr_count($lines[0], '+') == 1) {
            $title = trim(substr($lines[0], 1));
            array_shift($lines);
            $result = implode("\n", $lines);            
        }

        $result = $this->clean_urls($result);
        $result = $this->render_anchors($result);
        $result = strtr($result, array('<math>' => "[tex]\n\n".'\begin{displaymath}'."\n", '</math>' => "\n".'\end{displaymath}'."\n[/tex]"));
        $result = $this->pear->transform($result, 'latex');
        $result = $this->render_escaped_shortcuts($result);
        $result = $this->resolve($result, Liquid_Wiki_Resolver::LATEX);
        $result = preg_replace_callback("#\[tex\](.*?)\[/tex\]#si", array($this, 'unescapeLatexFormula'), $result);
        $result = str_replace('&#91;', '[', $result);
        $result = str_replace('&#93;', ']', $result);

        $date = new Zend_Date();
                
        $result = $header . 
            "\\usepackage{ulem}\n".
            "\\usepackage[utf8]{inputenc}\n".
            "\\usepackage[T1]{fontenc}\n".
            "\\usepackage{times}\n".
            "\\usepackage{amsmath}\n".
            "\\usepackage{amsfonts}\n".
            "\\usepackage{amssymb}\n".
            "\\usepackage{graphicx}\n".
//            "\\pagestyle{headings}\n".
            "\\begin{document}\n".
            "\\begin{titlepage}\n".
            "\\author{".self::escapeLatex($author)."} \n".
            "\\title{".self::escapeLatex($title)."}\n". 
            "\\date{".$date->toString(Zend_Date::DATE_LONG)."}\n". 
            "\\maketitle\n".
            "\\end{titlepage}\n".
            "\\tableofcontents\n".
            "\\newpage\n".
            $result;
		
		return $result;
    }
    
    protected function renderLatexAsPdf ($latex) {
		$file = sys_get_temp_dir(). '/' . md5($latex);
		
		file_put_contents($file . '.tex', $latex);
		
		exec('latex -interaction=batchmode -output-format=pdf -output-directory='.sys_get_temp_dir(). ' ' . $file . '.tex');
		exec('latex -interaction=batchmode -output-format=pdf -output-directory='.sys_get_temp_dir(). ' ' . $file . '.tex');

        $pdf = file_get_contents($file . '.pdf');
        
        return $pdf;
    }   
    
    public function renderAsPdfBook ($wikiCode, $title = '', $author = '') {
        return $this->renderLatexAsPdf($this->renderAsLatexBook($wikiCode, $title, $author));
    }    

    public function renderAsPdf ($wikiCode, $author = '') {
        return $this->renderLatexAsPdf($this->renderAsLatex($wikiCode, $author));
    }    
    
    public function renderAsText ($wikiCode) {
        $result = $wikiCode;
        
        /* if(file_exists('/usr/bin/latex')) {
            $result = $this->renderLaTeX($result, $this->latexPath, $this->wikiBaseUrl);
        } */
        
		$result = $this->raw_escape_tags($result);
		$result = $this->convert_mediawiki_to_pear_syntax($result);
		$result = $this->clean_urls($result);
		$result = $this->render_anchors($result);
		$result = $this->pear->transform($result, 'plain');
		/* $result = $this->render_footnotes($result); */
		$result = $this->render_escaped_shortcuts($result);
		$result = $this->resolve($result, Liquid_Wiki_Resolver::TEXT);
        $result = str_replace('&#91;', '[', $result);
        $result = str_replace('&#93;', ']', $result);

        return $result;
    }
    
    public function renderAsHtml ($wikiCode) {
        $result = $wikiCode;
        
        if(file_exists('/usr/bin/latex')) {
            $result = $this->renderLaTeX($result, $this->latexPath, $this->wikiBaseUrl);
        }
        
		$result = $this->raw_escape_tags($result);
		$result = $this->convert_mediawiki_to_pear_syntax($result);
		$result = $this->clean_urls($result);
		$result = $this->render_anchors($result);
		$result = $this->pear->transform($result, 'xhtml');
		$result = $this->render_footnotes($result);
		$result = $this->render_escaped_shortcuts($result);
		$result = $this->resolve($result, Liquid_Wiki_Resolver::HTML);

        return $result;
    }
    
    public function callback_footnote_as_html ($matches) {
		$this->footnotes = 0;
		$this->footnotes++;
		$result = '<sup style="font-size: 75%;">'.$this->footnotes.'</sup>';
	        
        return $result;	
	}
    

    public function resolve ($content, $format = Liquid_Wiki_Resolver::HTML) {
        $resolver = new Liquid_Wiki_Resolver;
        $resolver->setFormat($format);
        return $resolver->resolve($content);
    }
	
	/* function resolve_all ($content) {
        if($content == '') {
        	return FALSE;
        	}

        if(!is_array($this->resolvers)) {
        	foreach($this->my_cms->containers as $class_name => $object) {
        		if(method_exists($object, 'callback_resolve_shortcut_as_'.$this->output_format) 
        		  && $object->cms_namespace != '' 
                        	  && $object->shortcut_namespace != '') {
        			$this->resolvers[$object->shortcut_namespace] = $object->cms_namespace;
        			}
        		}
        	}

        if(!is_array($this->resolvers)) {
        	return $content;
        	}

        foreach($this->resolvers as $resolver => $cms_namespace) {
        	if($resolver != $this->default_resolver) {
        		$content = $this->resolve($cms_namespace, $resolver, $content);
        		}
        	}

        if(isset($this->resolvers[$this->default_resolver])) {
        	$content = $this->resolve($this->resolvers[$this->default_resolver], $this->default_resolver, $content);
        	}

        return $content;
    } */
		
	protected function render_footnotes ($content) {
        //$content = preg_replace("|\(\(footnote:(.*)\)\)|", "<sup>$i</sup>", $content);
        $content = preg_replace_callback(
            "|\(\(footnote:(.*)\)\)|", 
            array($this, 'callback_footnote_as_html'), 
            $content
        );

        return $content;
    }
		
	public function callback_url_as_html ($matches) {
		// Callback function for preg_replace_callback()
		
		$url = preg_replace("|%%(\d+)%%|", '', $matches[1].$matches[2]);
		
		if(isset($matches[3])) {
			// Do not show glossary link if word is part of an external link
			$title = preg_replace("|%%(\d+)%%|", '', $matches[3]);
			}
		else {
			$title = $url;
			}

	        $result = '<a href="'.$url.'" class="'.$matches[1].'" rel="nofollow">'.$title.'</a>';
	        
	        return $result;
		}
	
	public function callback_remove_lexicon_wildcards ($matches) {
		return preg_replace("|%%(\d+)%%|", '', $matches[0]);
		}
	
	protected function clean_urls ($content) {
		$content = preg_replace_callback(
		    "|\[([^\]]*)\]|", 
			array($this, 'callback_remove_lexicon_wildcards'), 
			$content
        );
        
		return $content;
    }
	
	protected function render_urls ($content) {
		// Remove Lexicon/Glossary wildcards inside brackets
		$content = $this->clean_urls($content);

		$content = preg_replace_callback(
		    "|\[([a-z]+)(://\S+)\]|", 
			array($this, 'callback_url_as_html'), 
			$content
        );
		
		// Render URL's
		$content = preg_replace_callback("|\[([a-z]+)(://\S+)\s([^\]]*)\]|", 
			create_function('$matches', 'return Liquid_Wiki::callback_url_as_html($matches);'), $content);
			
		
		return $content;
		}
	
	protected function callback_numbered_heading_as_html ($matches) {
        // Callback function for preg_replace_callback()
		$heading = preg_replace("|%%(\d+)%%|", '', $matches[2]);

		$this->headings[strlen($matches[1])]++;
		
		$numbering = '';

		$numbering = $headings[2];

		foreach($this->headings as $headingLevel => $count) {
			if($headingLevel > strlen($matches[1])) {
				$this->headings[$headingLevel] = 0;
			} elseif($headingLevel <= strlen($matches[1]) && $headingLevel > 2) {
				$numbering = $numbering . '.' . $count;
			}
        }
			
		$numbering .= ' ';

	    $result = str_repeat ('+', (strlen($matches[1]) - 1)).' '.$numbering.$heading."\n";

	    return $result;
	}
	        	        
	protected function callback_heading_as_html ($matches) {
	    // Callback function for preg_replace_callback()
		$heading = preg_replace("|%%(\d+)%%|", '', $matches[2]);

        $result = str_repeat ('+', (strlen($matches[1]) - 1)).' '.$heading."\n";

        return $result;
    }

	protected function convert_headings ($content) {
        if($this->numberedHeadings) { 
            $content = preg_replace_callback(
                "|(={2,7})\s([^\n\r]*)\s(={2,7})|",
                array($this, 'callback_numbered_heading_as_html'),
                $content
            );
        } else {
            $content = preg_replace_callback(
                "|(={2,7})\s([^\n\r]*)\s(={2,7})|",
                array($this, 'callback_heading_as_html'),
                $content
            );
        }

        return $content;
    }

    protected function convert_mediawiki_to_pear_syntax ($content) {
		$content = preg_replace("|'''([^\']*)'''|", "**$1**", $content);
		$content = preg_replace("|''([^\']*)''|", "//$1//", $content);
		$content = $this->convert_headings($content);
		return $content;
		}
			
	protected function raw_escape_tags ($content) {
        $content = preg_replace_callback(
            '|``([^`]*)``|',
            create_function('$matches', 'return \'``\'.htmlentities($matches[1]).\'``\';'), 
            $content
        );
        
        return $content;
    }

	protected function render_escaped_shortcuts ($content) {
		$content = preg_replace("|\[".$this->resolverStartTag.'([^\'\]]+)'.$this->resolverEndTag."\]|", "&#91;&#91;$1&#93;&#93;", $content);
		return $content;
		}

	protected function render_anchors ($content) {
		$headings = $this->get_headings_as_array($content);

		if(!is_array($headings)) {
			return $content;
			}

		foreach($headings as $text => $heading) {
			$content = strtr($content, array('[#'.$heading['anchor'].']' => '['.$this->wikiBaseUrl.'#'.$heading['anchor'].' '.trim($text).']'));
			}

		return $content;
		}
    
    protected function get_headings_as_array ($content) {
        $result = array();

        $content = $this->convert_headings($content);
        preg_match_all('/^(\+{1,6}) (.*)/m', $content, $matches);

        $headings = array();
        $i = 0;

        foreach($matches[2] as $match_key => $match) {
            if(isset($headings[strlen($matches[1][$match_key])])) {
            	$headings[strlen($matches[1][$match_key])]++;
            } else {
            	$headings[strlen($matches[1][$match_key])] = 1;
            }

            if(isset($headings[2])) {
            	$numbering = $headings[2];
            } else {
            	$numbering = 1;
            }

            foreach($headings as $headingLevel => $count) {
            	if($headingLevel > strlen($matches[1][$match_key])) {
            		$headings[$headingLevel] = 0;
            	} elseif($headingLevel <= strlen($matches[1][$match_key]) && $headingLevel > 2) {
            		$numbering = $numbering.'.'.$count;
            	}
            }

            $numbering .= ' ';

            if(isset($heading_number[strlen($matches[1][$match_key]) - 2])) {
            	$heading_number[strlen($matches[1][$match_key]) - 2]++;
            } else {
            	$heading_number[strlen($matches[1][$match_key]) - 2] = 1;
            }

            if(!isset($heading_number[strlen($matches[1][$match_key])])) {
            	$heading_number[strlen($matches[1][$match_key])] = 1;
            }

            $result[$match] = array(
            		'anchor' => 'toc'.$i++, 
            		'level'  => strlen($matches[1][$match_key]) - 1, 
            		'number' => $heading_number[strlen($matches[1][$match_key])],
            		'numbering' => $numbering
            );
        }

        return $result;
    }        
		
    protected function renderLaTeX ($text, $path, $url) {
        $text = strtr($text, array('<math>' => "[tex]\n\n".'\begin{displaymath}'."\n", '</math>' => "\n".'\end{displaymath}'."\n[/tex]"));

        preg_match_all("#\[tex\](.*?)\[/tex\]#si",$text,$tex_matches);

        $latex = new LaTeX_Render(
            $path,
            'latex',
            CACHE_PATH . 'latex'
        );

        for ($i=0; $i < count($tex_matches[0]); $i++) {
            $pos = strpos($text, $tex_matches[0][$i]);
            $latex_formula = $tex_matches[1][$i];

	    /* if you use htmlArea to input the text then uncomment the next 6 lines
	     $latex_formula = str_replace("&amp;","&",$latex_formula);
	    $latex_formula = str_replace("&#38;","&",$latex_formula);
	    $latex_formula = str_replace("&nbsp;"," ",$latex_formula);
	    $latex_formula = str_ireplace("<br>","",$latex_formula);
	    $latex_formula = str_ireplace("<br />","",$latex_formula);
	    $latex_formula = str_ireplace("<P>","",$latex_formula);
	    $latex_formula = str_ireplace("</P>","",$latex_formula); */
            
            $url = $latex->getFormulaURL($latex_formula);

		    $alt_latex_formula = htmlentities($latex_formula, ENT_QUOTES);
		    $alt_latex_formula = str_replace("\r","&#13;",$alt_latex_formula);
		    $alt_latex_formula = str_replace("\n","&#10;",$alt_latex_formula);

            if ($url != false) {	        
    	        $alt_latex_formula = preg_replace('/([^a-zA-Z0-9?=!+*{}\)\(<>:\ _\/\-]+)/', '', $latex_formula);
                $text = substr_replace($text, "[[image:$url|latex|$alt_latex_formula]]", $pos, strlen($tex_matches[0][$i]));
            } else {
                $text = substr_replace($text, "[Unparseable or potentially dangerous latex formula. Error $latex->_errorcode $latex->_errorextra]",$pos,strlen($tex_matches[0][$i]));
            }
        }
        
        return $text;
    }
}
