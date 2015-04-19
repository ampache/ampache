<?php

namespace Sabre\CalDAV\Property;

class EmailAddressSetTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $eas = new EmailAddressSet(['foo@example.org']);
        $this->assertEquals(['foo@example.org'], $eas->getValue());

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new EmailAddressSet(['foo@example.org']);

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:root');
        $root->setAttribute('xmlns:d','DAV:');
        $root->setAttribute('xmlns:cs',\Sabre\CalDAV\Plugin::NS_CALENDARSERVER);

        $doc->appendChild($root);
        $server = new \Sabre\DAV\Server();
        $server->addPlugin(new \Sabre\CalDAV\Plugin());

        $property->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cs="' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '">' .
'<cs:email-address>foo@example.org</cs:email-address>' .
'</d:root>
', $xml);

    }

}
