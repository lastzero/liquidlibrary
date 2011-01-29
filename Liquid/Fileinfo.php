<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Fileinfo
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */

require_once 'Liquid/Fileinfo/Extensions.php';

class Liquid_Fileinfo {
    protected $mime = '';
    protected $charset = '';
    protected $extension = '';
    protected static $extensions = array();
    
    public static function setExtensions (array $extensions) {
        self::$extensions = $extensions;
    }

    public static function generateFileExtensionArray () {
        $result = array();
        
        $types = explode("\n", file_get_contents('/etc/mime.types'));
        
        foreach($types as $type) {
            $line = explode("\t", $type);
            if(count($line) > 1) {
                $ext = explode(' ', $line[count($line) - 1]);
                if(strpos($line[0], '#') === false) {
                    $result[$line[0]] = $ext[0];
                }
            }
        }
        
        return $result;
    }

    public static function regenerateFileinfoExtensionsFile () {
        file_put_contents(
            dirname(__FILE__) . '/Fileinfo/Extensions.php', 
            '<?php Liquid_Fileinfo::setExtensions(' . var_export(self::generateFileExtensionArray(), true) . ');'
        );
    }

    public function __construct ($data) {
        if(class_exists('finfo', false)) {
            $finfo = new finfo(FILEINFO_MIME);
            $info = explode(';', $finfo->buffer($data));            
        } else {
            $filename = tempnam(sys_get_temp_dir(), "FOO");
            file_put_contents($filename, $data);
            $info = array(mime_content_type($filename));
        }
        
        
        if(count($info) == 2) {
            $temp = explode('=', $info[1]);
            $this->charset = trim($temp[1]);
        }
        
        $this->mime = trim($info[0]);
        
        if(isset(self::$extensions[$this->mime])) {
            $this->extension = self::$extensions[$this->mime];
        }
    }
    
    public function getMime () {
        return $this->mime;
    }
    
    public function getCharset () {
        return $this->charset;
    }
    
    public function getExtension () {
        return $this->extension;
    }
}
