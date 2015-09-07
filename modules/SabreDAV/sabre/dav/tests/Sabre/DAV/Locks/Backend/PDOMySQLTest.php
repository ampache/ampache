<?php

namespace Sabre\DAV\Locks\Backend;

require_once 'Sabre/TestUtil.php';

class PDOMySQLTest extends AbstractTest {

    function getBackend() {

        if (!SABRE_HASMYSQL) $this->markTestSkipped('MySQL driver is not available, or it was not properly configured');
        $pdo = \Sabre\TestUtil::getMySQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to MySQL database');
        $pdo->query('DROP TABLE IF EXISTS locks;');
        $pdo->query("
CREATE TABLE locks (
	id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
	owner VARCHAR(100),
	timeout INTEGER UNSIGNED,
	created INTEGER,
	token VARCHAR(100),
	scope TINYINT,
	depth TINYINT,
	uri text
);");

        $backend = new PDO($pdo);
        return $backend;

    }

}
