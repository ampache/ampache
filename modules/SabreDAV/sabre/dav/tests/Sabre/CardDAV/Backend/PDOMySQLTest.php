<?php

namespace Sabre\CardDAV\Backend;

require_once 'Sabre/TestUtil.php';

class PDOMySQLTest extends AbstractPDOTest {

    /**
     * @return PDO
     */
    public function getPDO() {

        if (!SABRE_HASMYSQL) $this->markTestSkipped('MySQL driver is not available, or not properly configured');

        $pdo = \Sabre\TestUtil::getMySQLDB();
        if (!$pdo) $this->markTestSkipped('Could not connect to MySQL database');

        $pdo->query("DROP TABLE IF EXISTS addressbooks, cards, addressbookchanges");

        $queries = explode(
            ';',
            file_get_contents(__DIR__ . '/../../../../examples/sql/mysql.addressbook.sql')
        );

        foreach($queries as $query) {
            $query = trim($query," \r\n\t");
            if ($query)
                $pdo->exec($query);
        }
        return $pdo;

    }

}

