<?php

require_once 'PHPUnit/Framework.php';
require_once 'Zend/Registry.php';
require_once 'Zend/Db/Adapter/Pdo/Mysql.php';

function __autoload($class_name)
{
    $filename = trim(strtr ($class_name, '_', '/')) . '.php';

    include $filename;
}

define('BASE_PATH', realpath(dirname(__FILE__)).'/../..');
define('CACHE_PATH', dirname(__FILE__) . '/_cache/');
define('HTDOCS_PATH', dirname(__FILE__) . '/_htdocs/');

// Check for custom config file
if(file_exists(dirname(__FILE__) . '/_config.local.php')) {
    include(dirname(__FILE__) . '/_config.local.php');
} else {
    include(dirname(__FILE__) . '/_config.dist.php');
}

$mail_transport = new Liquid_Mail_Transport_Dummy();
Zend_Mail::setDefaultTransport($mail_transport);

// Setup database
Zend_Registry::set('db', new Zend_Db_Adapter_Pdo_Mysql($db_config));

// Set locale
Zend_Registry::set('Zend_Locale', new Zend_Locale('de_DE'));
