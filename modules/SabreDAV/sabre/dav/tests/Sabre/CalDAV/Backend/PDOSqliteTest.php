<?php

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;

require_once 'Sabre/CalDAV/Backend/AbstractPDOTest.php';

class PDOSQLiteTest extends AbstractPDOTest {

    function setup() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');

        if (file_exists(SABRE_TEMPDIR . '/testdb.sqlite'))
            unlink(SABRE_TEMPDIR . '/testdb.sqlite');

        $pdo = new \PDO('sqlite:' . SABRE_TEMPDIR . '/testdb.sqlite');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);

        // Yup this is definitely not 'fool proof', but good enough for now.
        $queries = explode(';', file_get_contents(__DIR__ . '/../../../../examples/sql/sqlite.calendars.sql'));
        foreach($queries as $query) {
            $pdo->exec($query);
        }
        $this->pdo = $pdo;

    }

    function teardown() {

        $this->pdo = null;
        unlink(SABRE_TEMPDIR . '/testdb.sqlite');

    }

}
