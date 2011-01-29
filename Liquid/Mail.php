<?php
/**
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Mail
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */

class Liquid_Mail {
    protected $template;
    protected $values = array();
    protected $recipient;
    protected $subject;
    protected $mailer;
    protected $from;
    protected $path;
    protected $htmlFormat = false;

    protected static $defaultFrom;
    protected static $defaultPath;

    public function __construct () {
        $this->mailer = new Zend_Mail('utf-8');
        $this->setFrom(self::$defaultFrom);
        $this->setPath(self::$defaultPath);
    }

    public static function setDefaultFrom ($from) {
        self::$defaultFrom = $from;
    }

    public static function setDefaultPath ($path) {
        self::$defaultPath = $path;
    }

    public function setValues (array $values) {
        $this->values = $values;
    }

    public function getValues () {
        return $this->values;
    }

    public function setFrom ($from) {
        $this->from = (string) $from;
    }

    public function getFrom () {
        return $this->from;
    }

    public function setSubject ($subject) {
        $this->subject = (string) $subject;
    }

    public function getSubject () {
        if(empty($this->subject)) {
            throw new Liquid_Mail_Exception ('No subject set');
        }

        return $this->subject;
    }

    public function setPath ($path) {
        $this->path = (string) $path;
    }

    public function getPath () {
        return $this->path;
    }

    public function setRecipient ($recipient) {
        $this->recipient = $recipient;
    }

    public function getRecipient () {
        if(empty($this->recipient)) {
            throw new Liquid_Mail_Exception('No receipient given');
        }

        return $this->recipient;
    }

    public function setTemplate ($template) {
        if(!file_exists($this->getPath().$template)) {
            throw new Liquid_Mail_Exception('Template ' . $this->getPath().$template . ' does not exist');
        }

        $this->template = $template;
    }

    public function getTemplate () {
        if(empty($this->template)) {
            throw new Liquid_Mail_Exception('No template given');
        }

        return $this->template;
    }
    
    public function setTextFormat () {
        $this->htmlFormat = false;
    }

    public function setHtmlFormat () {
        $this->htmlFormat = true;
    }
    
    public function attachExcelFile ($filename) {
        $attachment = file_get_contents($filename);
        
        $this->mailer->createAttachment(
            $attachment,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            Zend_Mime::DISPOSITION_ATTACHMENT,
            Zend_Mime::ENCODING_BASE64,
            basename($filename)
        );
    }

    public function send () {
        try {
            $view = new Zend_View();
            $view->setScriptPath($this->getPath());
            $values = $this->getValues();

            foreach ($values as $key => $value) {
                $view->$key = $value;
            }

            $body = $view->render($this->getTemplate());

            if($this->htmlFormat) {
                $this->mailer->setBodyHtml($body);
            } else {
                $this->mailer->setBodyText($body);
            }
            
            $this->mailer->setFrom($this->getFrom());
            $this->mailer->addTo($this->getRecipient());
            $this->mailer->setSubject($this->getSubject());
            $this->mailer->send();
        } catch (Exception $e) {
            throw new Liquid_Mail_Exception ($e->getMessage(), $e->getCode());
        }
    }
}
