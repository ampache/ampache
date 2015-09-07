<?php

namespace Sabre\DAV\PropertyStorage\Backend;

class PDOSqliteTest extends AbstractPDOTest {

    function getPDO() {

        $pdo = \Sabre\TestUtil::getSqliteDB();
        if (!$pdo) $this->markTestSkipped('Sqlite is not enabled');

        $setupSql = file_get_contents(__DIR__ . '/../../../../../examples/sql/sqlite.propertystorage.sql');
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

    function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

}

