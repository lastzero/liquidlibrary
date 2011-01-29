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
 
class Liquid_Service_Facebook {
    protected $user;
    
    public function __construct(Liquid_User_Facebook $user) {
        $this->user = $user;
    }
    
    public function getUser() {
        return $this->user->getUser();
    }
}
