<?php

namespace Sabre\DAVACL\Property;

use Sabre\DAV;
use Sabre\HTTP;



class SupportedPrivilegeSetTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $prop = new SupportedPrivilegeSet(array(
            'privilege' => '{DAV:}all',
        ));
        $this->assertInstanceOf('Sabre\DAVACL\Property\SupportedPrivilegeSet', $prop);

    }


    /**
     * @depends testSimple
     */
    function testSerializeSimple() {

        $prop = new SupportedPrivilegeSet(array(
            'privilege' => '{DAV:}all',
        ));

        $doc = new \DOMDocument();
        $root = $doc->createElementNS('DAV:', 'd:supported-privilege-set');

        $doc->appendChild($root);

        $server = new DAV\Server();
        $prop->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:supported-privilege-set xmlns:d="DAV:">' .
'<d:supported-privilege>' .
'<d:privilege>' .
'<d:all/>' .
'</d:privilege>' .
'</d:supported-privilege>' .
'</d:supported-privilege-set>
', $xml);

    }

    /**
     * @depends testSimple
     */
    function testSerializeAggregate() {

        $prop = new SupportedPrivilegeSet(array(
            'privilege' => '{DAV:}all',
            'abstract'  => true,
            'aggregates' => array(
                array(
                    'privilege' => '{DAV:}read',
                ),
                array(
                    'privilege' => '{DAV:}write',
                    'description' => 'booh',
                ),
            ),
        ));

        $doc = new \DOMDocument();
        $root = $doc->createElementNS('DAV:', 'd:supported-privilege-set');

        $doc->appendChild($root);

        $server = new DAV\Server();
        $prop->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:supported-privilege-set xmlns:d="DAV:">' .
'<d:supported-privilege>' .
'<d:privilege>' .
'<d:all/>' .
'</d:privilege>' .
'<d:abstract/>' .
'<d:supported-privilege>' .
'<d:privilege>' .
'<d:read/>' .
'</d:privilege>' .
'</d:supported-privilege>' .
'<d:supported-privilege>' .
'<d:privilege>' .
'<d:write/>' .
'</d:privilege>' .
'<d:description>booh</d:description>' .
'</d:supported-privilege>' .
'</d:supported-privilege>' .
'</d:supported-privilege-set>
', $xml);

    }
}
