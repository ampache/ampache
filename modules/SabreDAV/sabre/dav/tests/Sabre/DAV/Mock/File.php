<?php

namespace Sabre\DAV\Mock;

use Sabre\DAV;

/**
 * Mock File
 *
 * See the Collection in this directory for more details.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class File extends DAV\File {

    protected $name;
    protected $contents;
    protected $parent;

    /**
     * Creates the object
     *
     * @param string $name
     * @param array $children
     * @return void
     */
    public function __construct($name, $contents, Collection $parent) {

        $this->name = $name;
        $this->put($contents);
        $this->parent = $parent;

    }

    /**
     * Returns the name of the node.
     *
     * This is used to generate the url.
     *
     * @return string
     */
    public function getName() {

        return $this->name;

    }

    /**
     * Updates the data
     *
     * The data argument is a readable stream resource.
     *
     * After a succesful put operation, you may choose to return an ETag. The
     * etag must always be surrounded by double-quotes. These quotes must
     * appear in the actual string you're returning.
     *
     * Clients may use the ETag from a PUT request to later on make sure that
     * when they update the file, the contents haven't changed in the mean
     * time.
     *
     * If you don't plan to store the file byte-by-byte, and you return a
     * different object on a subsequent GET you are strongly recommended to not
     * return an ETag, and just return null.
     *
     * @param resource $data
     * @return string|null
     */
    public function put($data) {

        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }
        $this->contents = $data;
        return '"' . md5($data) . '"';

    }

    /**
     * Returns the data
     *
     * This method may either return a string or a readable stream resource
     *
     * @return mixed
     */
    public function get() {

        return $this->contents;

    }

    /**
     * Changes the name of the node.
     *
     * @return void
     */
    public function setName($newName) {

        $this->parent->deleteChild($this->name);
        $this->name = $newName;
        $this->parent->createFile($newName, $this->contents);

    }

    /**
     * Returns the ETag for a file
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     *
     * Return null if the ETag can not effectively be determined
     *
     * @return void
     */
    public function getETag() {

        return '"' . md5($this->contents) . '"';

    }

    /**
     * Returns the size of the node, in bytes
     *
     * @return int
     */
    public function getSize() {

        return strlen($this->contents);

    }

    /**
     * Delete the node
     *
     * @return void
     */
    public function delete() {

        $this->parent->deleteChild($this->name);

    }

}
