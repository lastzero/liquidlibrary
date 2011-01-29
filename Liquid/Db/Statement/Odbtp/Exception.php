<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Db
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 * @copyright  Copyright (c) 2005-2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://www.zend.com/license/framework/1_0.txt Zend Framework License version 1.0
 */

/**
 * Liquid_Db_Statement_Odbtp_Exception
 *
 * @package    Zend_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://www.zend.com/license/framework/1_0.txt Zend Framework License version 1.0
 * @author 	   Michael Mayer <michael.mayer@zend.com>
 */
class Liquid_Db_Statement_Odbtp_Exception extends Zend_Db_Statement_Exception {
  protected $code = '00000';
  protected $message = 'unknown exception';

  function __construct($msg = 'unknown exception', $state = '00000') {
    $this->message = $msg;
    $this->code = $state;
  }

}

