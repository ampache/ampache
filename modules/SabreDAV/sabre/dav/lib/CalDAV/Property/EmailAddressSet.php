<?php

namespace Sabre\CalDAV\Property;

use Sabre\DAV;

/**
 * email-address-set property
 *
 * This property represents the email-address-set property in the
 * http://calendarserver.org/ns/ namespace.
 *
 * It's a list of email addresses associated with a user.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class EmailAddressSet extends DAV\Property {

    /**
     * emails
     *
     * @var array
     */
    private $emails;

    /**
     * __construct
     *
     * @param array $emails
     */
    function __construct(array $emails) {

        $this->emails = $emails;

    }

    /**
     * Returns the email addresses
     *
     * @return array
     */
    function getValue() {

        return $this->emails;

    }

    /**
     * Serializes this property.
     *
     * It will additionally prepend the href property with the server's base uri.
     *
     * @param DAV\Server $server
     * @param \DOMElement $dom
     * @return void
     */
    function serialize(DAV\Server $server,\DOMElement $dom) {

        $prefix = $server->xmlNamespaces['http://calendarserver.org/ns/'];

        foreach($this->emails as $email) {

            $elem = $dom->ownerDocument->createElement($prefix . ':email-address');
            $elem->appendChild($dom->ownerDocument->createTextNode($email));
            $dom->appendChild($elem);

        }

    }

}
