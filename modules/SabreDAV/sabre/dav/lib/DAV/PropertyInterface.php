<?php

namespace Sabre\DAV;

/**
 * PropertyInterface
 *
 * Implement this interface to create new complex properties
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface PropertyInterface {

    /**
     * Serializes this property into an XML document.
     *
     * @param Server $server
     * @param \DOMElement $prop
     * @return void
     */
    function serialize(Server $server, \DOMElement $prop);

    /**
     * This method unserializes the property FROM an xml document.
     *
     * This method (often) must return an instance of itself. It acts therefore
     * a bit like a constructor. It is also valid to return a different object
     * or type.
     *
     * @param \DOMElement $prop
     * @param array $propertyMap
     * @return mixed
     */
    static function unserialize(\DOMElement $prop, array $propertyMap);

}

