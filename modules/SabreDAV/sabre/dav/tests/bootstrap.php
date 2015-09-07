<?php

set_include_path(__DIR__ . '/../lib/' . PATH_SEPARATOR . __DIR__ . PATH_SEPARATOR . get_include_path());

$autoLoader = include __DIR__ . '/../vendor/autoload.php';

// SabreDAV tests auto loading
$autoLoader->add('Sabre\\', __DIR__);
// VObject tests auto loadiong
$autoLoader->addPsr4('Sabre\\VObject\\',__DIR__ . '/../vendor/sabre/vobject/tests/VObject');


date_default_timezone_set('UTC');

$config = [
    'SABRE_TEMPDIR'   => dirname(__FILE__) . '/temp/',
    'SABRE_HASSQLITE' => in_array('sqlite',PDO::getAvailableDrivers()),
    'SABRE_HASMYSQL'  => in_array('mysql',PDO::getAvailableDrivers()),
    'SABRE_MYSQLDSN'  => 'mysql:host=127.0.0.1;dbname=sabredav',
    'SABRE_MYSQLUSER' => 'root',
    'SABRE_MYSQLPASS' => '',
];

foreach($config as $key=>$value) {
    if (!defined($key)) define($key, $value);
}

if (!file_exists(SABRE_TEMPDIR)) mkdir(SABRE_TEMPDIR);
if (file_exists('.sabredav')) unlink('.sabredav');
