<?php

namespace Sabre\CardDAV\Backend;

require_once 'Sabre/TestUtil.php';

class PDOSqliteTest extends AbstractPDOTest {

    function tearDown() {

        if (file_exists(SABRE_TEMPDIR . '/pdobackend')) unlink(SABRE_TEMPDIR . '/pdobackend');
        if (file_exists(SABRE_TEMPDIR . '/pdobackend2')) unlink(SABRE_TEMPDIR . '/pdobackend2');

    }

    /**
     * @return PDO
     */
    function getPDO() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $pdo = new \PDO('sqlite:'.SABRE_TEMPDIR.'/pdobackend');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);

        $pdo->query("DROP TABLE IF EXISTS addressbooks");
        $pdo->query("DROP TABLE IF EXISTS addressbookchanges");
        $pdo->query("DROP TABLE IF EXISTS cards");

        $queries = explode(
            ';',
            file_get_contents(__DIR__ . '/../../../../examples/sql/sqlite.addressbooks.sql')
        );

        foreach($queries as $query) {
            $query = trim($query," \r\n\t");
            if ($query)
                $pdo->exec($query);
        }

        return $pdo;

    }

}

