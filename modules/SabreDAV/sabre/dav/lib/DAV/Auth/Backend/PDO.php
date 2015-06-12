<?php

namespace Sabre\DAV\Auth\Backend;

/**
 * This is an authentication backend that uses a database to manage passwords.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PDO extends AbstractDigest {

    /**
     * Reference to PDO connection
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * PDO table name we'll be using
     *
     * @var string
     */
    public $tableName = 'users';


    /**
     * Creates the backend object.
     *
     * If the filename argument is passed in, it will parse out the specified file fist.
     *
     * @param PDO $pdo
     * @param string $tableName The PDO table name to use
     * @deprecated The tableName argument will be removed from a future version
     *             of sabredav. Use the public property instead.
     */
    function __construct(\PDO $pdo, $tableName = 'users') {

        $this->pdo = $pdo;
        $this->tableName = $tableName;

    }

    /**
     * Returns the digest hash for a user.
     *
     * @param string $realm
     * @param string $username
     * @return string|null
     */
    function getDigestHash($realm,$username) {

        $stmt = $this->pdo->prepare('SELECT digesta1 FROM '.$this->tableName.' WHERE username = ?');
        $stmt->execute([$username]);
        return $stmt->fetchColumn() ?: null;

    }

}
