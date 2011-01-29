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

require_once 'Liquid/Storage/Adapter/Interface.php';
require_once 'Liquid/Wiki.php';

class Liquid_Service_Wiki {
    protected $wiki;
    protected $storage;
    protected $htdocsPath;
    protected $namespace;
    protected $writable;
    protected $owner;
    protected $user;
    protected $memcache;
    
    public function __construct ($wikiBaseUrl, $htdocsPath, Liquid_Storage_Adapter_Abstract $storage, $namespace = 'wiki', $writable = true, $owner = true, Liquid_User_Facebook $user = null) {
        $this->storage = $storage;
        $this->wiki = new Liquid_Wiki($wikiBaseUrl, $htdocsPath . 'img/latex/');      
        $this->htdocsPath = $htdocsPath;
        $this->namespace = $namespace;
        $this->writable = $writable;
        $this->owner = $owner;
        $this->user = $user;
    }
    
    public function setMemcache (Memcache $memcache) {
        Liquid_Log::debug('Memcache set');
        $this->memcache = $memcache;
    }
    
    public function isWritable () {
        return $this->writable;
    }
    
    public function isOwner () {
        return $this->owner;
    }
        
    public function renderAsHtml ($content) { 
        if($this->memcache) {
            $cached = $this->memcache->get('wikipage_' . md5($content));
        
            if($cached) {
                Liquid_Log::debug('Page found in memcache');
                return $cached;
            }

            Liquid_Log::debug('Page not found in memcache');            
        }
        
        $result = $this->wiki->renderAsHtml($content);
        
        if($this->memcache) {
            $this->memcache->set('wikipage_' . md5($content), $result);
        }
        
        return $result;
    }

    protected function getLocaleString () {
        return Zend_Registry::get('Zend_Locale')->toString();
    }

    protected function renderAsPdf ($content) {
        $md5 = md5($content);
        
        $directory = 'cache/pdf/' . 
            $md5[0] . '/' . 
            $md5[1] . '/' . 
            $md5[2] . '/' . 
            $md5[3];
        
        $filename =  $directory . '/' . 
            $md5 . '_' . $this->getLocaleString() . '.pdf';
        
        if(!file_exists($this->htdocsPath . $filename)) {
            if(!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $meta = $this->storage->getNamespaceMeta($this->namespace);
            
            $author = '';
            
            if(isset($meta['authors']) && is_array($meta['authors'])) {
                $authors = array();
                
                foreach($meta['authors'] as $profile) {
                    $authors[] = $profile['name'];
                }
                
                $author = implode(', ', $authors);
            }
            
            $pdf = $this->wiki->renderAsPdf($content, $author);
            
            if(!$pdf) {
                return false;
            }
            
            file_put_contents($this->htdocsPath . $filename, $pdf);
        }        
        
        return '/' . $filename;
    }
    
    protected function renderAsPdfBook ($content, $title = '') {
        preg_match_all("|\[\[([^\]]*)\]\]|", $content, $matches, PREG_PATTERN_ORDER);
        
        if(is_array($matches[1])) {
            $pages = array();
            foreach($matches[1] as $match) {
                $parts = explode('|', $match);
                if(!in_array($parts[0], $pages)) {
                    $pages[] = $parts[0];

                    try {
                        $entry = $this->storage->findLast($this->namespace, $parts[0]);
                        $content .= "\n\n" . $entry->getData();
                    } catch (Exception $e) {
                    }
                }
            }            
        }                
        
        $md5 = md5($content);
        
        $directory = 'cache/pdf/' . 
            $md5[0] . '/' . 
            $md5[1] . '/' . 
            $md5[2] . '/' . 
            $md5[3];                    
        
        $filename =  $directory . '/' . 
            $md5 . '_book_' . $this->getLocaleString() . '.pdf';
        
        if(!file_exists($this->htdocsPath . $filename)) {
            if(!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $meta = $this->storage->getNamespaceMeta($this->namespace);
            
            $author = '';
            
            if(isset($meta['authors']) && is_array($meta['authors'])) {
                $authors = array();
                
                foreach($meta['authors'] as $profile) {
                    $authors[] = $profile['name'];
                }
                
                $author = implode(', ', $authors);
            }
            
            $pdf = $this->wiki->renderAsPdfBook($content, $title, $author);
            
            if(!$pdf) {
                return false;
            }
            
            file_put_contents($this->htdocsPath . $filename, $pdf);
        }        
        
        return '/' . $filename;
    }

    public function init ($page = null, $output = null, $id = null) {
        $result = array(
            'wiki' => $this->getProperties()
        );

        if($page != null) {
            try {
                if($id != null) {
                    $entry = $this->storage->findOne($this->namespace, $page, $id);
                } else {
                    $entry = $this->storage->findLast($this->namespace, $page);
                }                        
                
                switch($output) {
                    case 'html'     : $result['rendered'] = $this->renderAsHtml($entry->getData()); break;
                    case 'text'     : $result['rendered'] = $this->wiki->renderAsText($entry->getData()); break;
                    case 'latex'    : $result['rendered'] = $this->wiki->renderAsLatex($entry->getData()); break;
                    case 'pdf'      : $result['rendered'] = $this->renderAsPdf($entry->getData()); break;
                    case 'pdfbook'  : $result['rendered'] = $this->renderAsPdfBook($entry->getData(), urldecode($entry->getKey())); break;
                    default         : $result['rendered'] = $entry->getData(); break;
                }
                
                $result['page'] = $entry->getAsArray();
                
                $result['history'] = $this->getPageRevisions($page);
            } catch (Liquid_Storage_Exception $e) {
                $result['error'] = array('message' => $e->getMessage(), 'class' => get_class($e));
            }                        
        }   
        return $result;
    }
        
    public function html ($page, $id = null) {
        $content = $this->find($page, $id);
        
        return $this->wiki->renderAsHtml($content);
    }
    
    public function text ($page, $id = null) {
        $content = $this->find($page, $id);
        
        return $this->wiki->renderAsText($content);
    }
    
    public function latex ($page, $id = null) {
        $content = $this->find($page, $id);
        
        return str_replace(HTDOCS_PATH, '/', $this->wiki->renderAsLatex($content));
    }
        
    public function pdf ($page, $id = null) {
        $content = $this->find($page, $id);
        
        return $this->renderAsPdf($content);
    }
    
    public function find ($page, $id = null) {
        try {
            if($id != null) {
                $entry = $this->storage->findOne($this->namespace, $page, $id);
            } else {
                $entry = $this->storage->findLast($this->namespace, $page);
            }
            
            return $entry->getData();
        } catch (Liquid_Storage_Exception $e) {
            return '';
        }
    }
    
    public function create ($page, $content, $style = '') { 
        if(!$this->isWritable()) {
            throw new Liquid_Service_Wiki_Exception ('This wiki is not writable');
        }
        
        if($this->find($page) != $content) {        
            $entry = new Liquid_Storage_Entry(array(
                'namespace' => $this->namespace,
                'key' => $page,
                'data' => $content,
                'meta' => array('style' => $style)
            ));
            
            return $this->storage->createEntry($entry);
        }
    }
    
    public function replace ($page, $content, $style = '') { 
        if(!$this->isWritable()) {
            throw new Liquid_Service_Wiki_Exception ('This wiki is not writable');
        }

        $entry = new Liquid_Storage_Entry(array(
            'namespace' => $this->namespace,
            'key' => $page,
            'data' => $content,
            'meta' => array('style' => $style)
        ));
        
        return $this->storage->replaceEntry($entry);       
    }
    
    public function update ($page, $content) {
        if(!$this->isWritable()) {
            throw new Liquid_Service_Wiki_Exception ('This wiki is not writable');
        }

        $entry = $this->storage->findLast($this->namespace, $page);
        $entry->setData($content);        
        
        return $this->storage->updateEntry($entry);
    }
    
    public function delete ($page) {   
        if(!$this->isWritable()) {
            throw new Liquid_Service_Wiki_Exception ('This wiki is not writable');
        }

        return $this->storage->deleteKey($this->namespace, $page); 
    }
    
    public function getPages () {
        $keys = $this->storage->findKeys($this->namespace, false); 


        $result = array();
        
        foreach($keys as $key => $meta) {
            $date = new Zend_Date($meta['created'], Zend_Date::TIMESTAMP);
            $dateString = $date->toString(Zend_Date::DATETIME_MEDIUM);    
            
            $result[] = array(
                'page' => $key,
                'timestamp' => $meta['created'],
                'created' => $dateString,
                'urlencoded' => rawurlencode($key),
                'escaped' => htmlspecialchars($key, ENT_QUOTES)
            );
        }
        
        return $result;
    }

    public function getPageRevisions ($page) {
        $result = $this->storage->findIndex($this->namespace, $page); 
        
        foreach($result as &$meta) {
            if(isset($meta['updated'])) {
                $date = new Zend_Date($meta['updated'], Zend_Date::TIMESTAMP);
                $meta['date'] = $date->toString(Zend_Date::DATETIME_MEDIUM);     
            } elseif(isset($meta['created'])) {
                $date = new Zend_Date($meta['created'], Zend_Date::TIMESTAMP);
                $meta['date'] = $date->toString(Zend_Date::DATETIME_MEDIUM);     
            }
        }
        
        return $result;
    }
    
    public function getProperties () {
        $meta = $this->storage->getNamespaceMeta($this->namespace);
        
        $result = array(
            'name' => $this->namespace,
            'authors' => @$meta['authors'],
            'public' => @$meta['public'],
            'history' => @$meta['history'],
            'created' => @$meta['created'],
            'owner' => $this->owner,
            'writable' => $this->writable
            );
        
        return $result;
    }    
    
    public function addAuthors ($authors) {
        if(!$this->isOwner()) {
            throw new Liquid_Service_Wiki_Exception ('You must be the owner of this Wiki to perform this action');
        }
        
        $authors = explode(',', $authors);
        
        $meta = $this->storage->getNamespaceMeta($this->namespace);
        
        if(!isset($meta['authors']) || !is_array($meta['authors'])) {
            $clean = array();
        } else {
            $clean = $meta['authors'];
        }
        
        foreach($authors as $key => $author) {
            if(is_string($author) && trim($author) != '') {       
                $author = preg_replace('/[^a-zA-Z0-9_\.-]/', '', trim($author));
                $data = Zend_Json::decode(file_get_contents('https://graph.facebook.com/' . $author));
                
                if(isset($data['id'])) {
                    $clean['facebook://' . $data['id']] = $data;
                }
            }
        }
        
        $this->storage->replaceNamespaceMeta($this->namespace, 'authors', $clean);
    }
    
    public function removeAuthor ($author) {
        if(!$this->isOwner()) {
            throw new Liquid_Service_Wiki_Exception ('You must be the owner of this Wiki to perform this action');
        }
        
        $meta = $this->storage->getNamespaceMeta($this->namespace);
        
        if(!isset($meta['authors']) || !is_array($meta['authors'])) {
            throw new Liquid_Service_Wiki_Exception ('There are no authors');
        }
        
        if(isset($meta['authors']['facebook://' . $author])) {
            unset($meta['authors']['facebook://' . $author]);
        } else {
            throw new Liquid_Service_Wiki_Exception ('Author does not exist');
        }
                
        $this->storage->replaceNamespaceMeta($this->namespace, 'authors', $meta['authors']);
    }
    
    public function enablePublic () {
        if(!$this->isOwner()) {
            throw new Liquid_Service_Wiki_Exception ('You must be the owner of this Wiki to perform this action');
        }
        
        $this->storage->replaceNamespaceMeta($this->namespace, 'public', true);
    }
    
    public function disablePublic () {
        if(!$this->isOwner()) {
            throw new Liquid_Service_Wiki_Exception ('You must be the owner of this Wiki to perform this action');
        }
        
        $this->storage->replaceNamespaceMeta($this->namespace, 'public', false);
    }
    
    public function enableHistory () {
        if(!$this->isOwner()) {
            throw new Liquid_Service_Wiki_Exception ('You must be the owner of this Wiki to perform this action');
        }
        
        $this->storage->replaceNamespaceMeta($this->namespace, 'history', true);
    }
    
    public function disableHistory () {
        if(!$this->isOwner()) {
            throw new Liquid_Service_Wiki_Exception ('You must be the owner of this Wiki to perform this action');
        }
        
        $this->storage->replaceNamespaceMeta($this->namespace, 'history', false);
    }
    
    
    public function setStyle ($pageName, $style) {
        if(!$this->isWritable()) {
            throw new Liquid_Service_Wiki_Exception ('You must be an author of this Wiki to perform this action');
        }
        
        $entry = $this->storage->findLast($this->namespace, $pageName);
        
        $this->storage->replaceEntryMeta($entry, 'style', $style);
    }
    
    public function renamePage ($oldName, $newName) {
        $this->storage->renameKey($oldName, $newName);
    }
}
