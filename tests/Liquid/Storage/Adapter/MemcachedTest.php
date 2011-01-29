<?php
/** 
 * LICENSE
 *
 * This source file is subject to the new BSD license.
 * It is available through the world-wide-web at this URL:
 * http://www.liquidbytes.net/bsd.html
 *
 * @category   Liquid
 * @package    Liquid_Storage
 * @copyright  Copyright (c) 2010 Liquid Bytes Technologies (http://www.liquidbytes.net/)
 * @license    http://www.liquidbytes.net/bsd.html New BSD License
 */
 
require_once 'tests/_config.php';

require_once 'tests/Liquid/Storage/Adapter/InterfaceTest.php';
require_once 'Liquid/Storage/Adapter/Memcached.php';

class LiquidStorageAdapterMemcachedTest extends LiquidStorageAdapterInterfaceTest {
    public function setUp () {
        $memcache = new Memcache();
        $memcache->connect('localhost', 11211);
        
        $this->adapter = new Liquid_Storage_Adapter_Memcached($memcache);

        $this->createFixtures();
    }
}
