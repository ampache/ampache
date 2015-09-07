<?php

namespace Sabre\DAV\PropertyStorage\Backend;

class PDOMySqlTest extends AbstractPDOTest {

    function getPDO() {

        $pdo = \Sabre\TestUtil::getMySQLDB();
        if (!$pdo) $this->markTestSkipped('MySQL is not enabled');

        $setupSql = file_get_contents(__DIR__ . '/../../../../../examples/sql/mysql.propertystorage.sql');
        // Sloppy multi-query, but it works
        $setupSql = explode(';', $setupSql);

        $pdo->exec('DROP TABLE IF EXISTS propertystorage');

        foreach($setupSql as $sql) {

            if (!trim($sql)) continue;
            $pdo->exec($sql);

        }
        $pdo->exec('INSERT INTO propertystorage (path, name, value) VALUES ("dir", "{DAV:}displayname", "Directory")');

        return $pdo;

    }

}

