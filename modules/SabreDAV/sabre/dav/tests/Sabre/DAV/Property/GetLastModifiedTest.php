<?php

namespace Sabre\DAV\Property;

use Sabre\DAV;
use Sabre\HTTP;

class GetLastModifiedTest extends \PHPUnit_Framework_TestCase {

    function testConstructDateTime() {

        $dt = new \DateTime('2010-03-14 16:35', new \DateTimeZone('UTC'));
        $lastMod = new GetLastModified($dt);
        $this->assertEquals($dt->format(\DateTime::ATOM), $lastMod->getTime()->format(\DateTime::ATOM));

    }

    function testConstructString() {

        $dt = new \DateTime('2010-03-14 16:35', new \DateTimeZone('UTC'));
        $lastMod = new GetLastModified('2010-03-14 16:35');
        $this->assertEquals($dt->format(\DateTime::ATOM), $lastMod->getTime()->format(\DateTime::ATOM));

    }

    function testConstructInt() {

        $dt = new \DateTime('2010-03-14 16:35', new \DateTimeZone('UTC'));
        $lastMod = new GetLastModified((int)$dt->format('U'));
        $this->assertEquals($dt->format(\DateTime::ATOM), $lastMod->getTime()->format(\DateTime::ATOM));

    }

    function testSerialize() {

        $dt = new \DateTime('2010-03-14 16:35', new \DateTimeZone('UTC'));
        $lastMod = new GetLastModified($dt);

        $doc = new \DOMDocument();
        $root = $doc->createElement('d:getlastmodified');
        $root->setAttribute('xmlns:d','DAV:');

        $doc->appendChild($root);
        $server = new DAV\Server();

        $lastMod->serialize($server, $root);

        $xml = $doc->saveXML();

        /*
        $this->assertEquals(
'<?xml version="1.0"?>
<d:getlastmodified xmlns:d="DAV:" xmlns:b="urn:uuid:c2f41010-65b3-11d1-a29f-00aa00c14882/" b:dt="dateTime.rfc1123">' .
HTTP\Util::toHTTPDate($dt) .
'</d:getlastmodified>
', $xml);
        */
        $this->assertEquals(
'<?xml version="1.0"?>
<d:getlastmodified xmlns:d="DAV:">' .
HTTP\Util::toHTTPDate($dt) .
'</d:getlastmodified>
', $xml);

        $ok = false;
        try {
            GetLastModified::unserialize(DAV\XMLUtil::loadDOMDocument($xml)->firstChild, array());
        } catch (DAV\Exception $e) {
            $ok = true;
        }
        if (!$ok) $this->markTestFailed('Unserialize should not be supported');

    }

}
