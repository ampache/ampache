<?php

namespace Sabre\DAVACL\Property;

use Sabre\DAV;
use Sabre\HTTP;

class ACLRestrictionsTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $prop = new AclRestrictions();
        $this->assertInstanceOf('Sabre\DAVACL\Property\ACLRestrictions', $prop);

    }

    function testSerializeEmpty() {

        $dom = new \DOMDocument('1.0');
        $root = $dom->createElementNS('DAV:','d:root');

        $dom->appendChild($root);

        $acl = new AclRestrictions();
        $acl->serialize(new DAV\Server(), $root);

        $xml = $dom->saveXML();
        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:"><d:grant-only/><d:no-invert/></d:root>
';
        $this->assertEquals($expected, $xml);

    }


}
