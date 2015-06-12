<?php

namespace Sabre\DAVACL\PrincipalBackend;

use Sabre\DAV;
use Sabre\HTTP;


require_once 'Sabre/DAV/Auth/Backend/AbstractPDOTest.php';

class PDOSQLiteTest extends AbstractPDOTest {

    function tearDown() {

        if (file_exists(SABRE_TEMPDIR . '/pdobackend')) unlink(SABRE_TEMPDIR . '/pdobackend');
        if (file_exists(SABRE_TEMPDIR . '/pdobackend2')) unlink(SABRE_TEMPDIR . '/pdobackend2');

    }

    function getPDO() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $pdo = new \PDO('sqlite:'.SABRE_TEMPDIR.'/pdobackend');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
        $pdo->query('CREATE TABLE principals (id INTEGER PRIMARY KEY ASC, uri TEXT, email VARCHAR(80), displayname VARCHAR(80), vcardurl VARCHAR(80))');
        $pdo->query('INSERT INTO principals VALUES (1, "principals/user","user@example.org","User",null)');
        $pdo->query('INSERT INTO principals VALUES (2, "principals/group","group@example.org","Group",null)');

        $pdo->query("CREATE TABLE groupmembers (
                id INTEGER PRIMARY KEY ASC,
                principal_id INT,
                member_id INT,
                UNIQUE(principal_id, member_id)
        );");

        $pdo->query("INSERT INTO groupmembers (principal_id,member_id) VALUES (2,1)");

        return $pdo;

    }

}
