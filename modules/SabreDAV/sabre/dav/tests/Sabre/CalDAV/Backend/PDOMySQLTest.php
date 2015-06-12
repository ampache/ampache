<?php

namespace Sabre\CalDAV\Backend;

require_once 'Sabre/TestUtil.php';
require_once 'Sabre/CalDAV/TestUtil.php';
require_once 'Sabre/CalDAV/Backend/AbstractPDOTest.php';

class PDOMySQLTest extends AbstractPDOTest {

    function setup() {

        if (!SABRE_HASMYSQL) $this->markTestSkipped('MySQL driver is not available, or not properly configured');
        $pdo = \Sabre\TestUtil::getMySQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to mysql database');

        $pdo->query('DROP TABLE IF EXISTS calendarobjects, calendars, calendarchanges, calendarsubscriptions, schedulingobjects');

        $queries = explode(
            ';',
            file_get_contents(__DIR__ . '/../../../../examples/sql/mysql.calendars.sql')
        );

        foreach($queries as $query) {
            $query = trim($query," \r\n\t");
            if ($query)
                $pdo->exec($query);
        }
        $this->pdo = $pdo;

    }

    function teardown() {

        $this->pdo = null;

    }

}
