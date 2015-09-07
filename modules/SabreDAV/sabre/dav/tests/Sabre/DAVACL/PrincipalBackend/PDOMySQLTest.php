<?php

namespace Sabre\DAVACL\PrincipalBackend;

use Sabre\DAV;
use Sabre\HTTP;


require_once 'Sabre/TestUtil.php';

class PDOMySQLTest extends AbstractPDOTest {

    function getPDO() {

        if (!SABRE_HASMYSQL) $this->markTestSkipped('MySQL driver is not available, or not properly configured');
        $pdo = \Sabre\TestUtil::getMySQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to MySQL database');
        $pdo->query("DROP TABLE IF EXISTS principals");
        $pdo->query("
create table principals (
	id integer unsigned not null primary key auto_increment,
	uri varchar(50),
    email varchar(80),
    displayname VARCHAR(80),
    vcardurl VARCHAR(80),
	unique(uri)
);");

        $pdo->query("INSERT INTO principals (uri,email,displayname) VALUES ('principals/user','user@example.org','User')");
        $pdo->query("INSERT INTO principals (uri,email,displayname) VALUES ('principals/group','group@example.org','Group')");
        $pdo->query("DROP TABLE IF EXISTS groupmembers");
        $pdo->query("CREATE TABLE groupmembers (
                id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    principal_id INTEGER UNSIGNED NOT NULL,
                        member_id INTEGER UNSIGNED NOT NULL,
                            UNIQUE(principal_id, member_id)
                        );");

        $pdo->query("INSERT INTO groupmembers (principal_id,member_id) VALUES (2,1)");

        return $pdo;

    }

}
