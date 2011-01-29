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
 
class Liquid_Service_Chat {
    protected $ajax;
    protected $user;
    
    public function __construct($user, Liquid_Ajax_Handler_Abstract $ajax) {
        $this->user = $user;
        $this->ajax = $ajax;
    }
    
    public function post ($message) {
        $this->ajax->send('chat.message', array('icon' => '', 'username' => $this->user, 'message' => $message));
    }
}
