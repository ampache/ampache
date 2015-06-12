<?php

namespace Sabre\DAVACL\Property;

use Sabre\DAV;
use Sabre\HTTP;


class ACLTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $acl = new Acl(array());
        $this->assertInstanceOf('Sabre\DAVACL\Property\ACL', $acl);

    }

    function testSerializeEmpty() {

        $dom = new \DOMDocument('1.0');
        $root = $dom->createElementNS('DAV:','d:root');

        $dom->appendChild($root);

        $acl = new Acl(array());
        $acl->serialize(new DAV\Server(), $root);

        $xml = $dom->saveXML();
        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:"/>
';
        $this->assertEquals($expected, $xml);

    }

    function testSerialize() {

        $dom = new \DOMDocument('1.0');
        $root = $dom->createElementNS('DAV:','d:root');

        $dom->appendChild($root);

        $privileges = array(
            array(
                'principal' => 'principals/evert',
                'privilege' => '{DAV:}write',
                'uri'       => 'articles',
            ),
            array(
                'principal' => 'principals/foo',
                'privilege' => '{DAV:}read',
                'uri'       => 'articles',
                'protected' => true,
            ),
        );

        $acl = new Acl($privileges);
        $acl->serialize(new DAV\Server(), $root);

        $dom->formatOutput = true;

        $xml = $dom->saveXML();
        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:principal>
      <d:href>/principals/evert/</d:href>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:href>/principals/foo/</d:href>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:read/>
      </d:privilege>
    </d:grant>
    <d:protected/>
  </d:ace>
</d:root>
';
        $this->assertEquals($expected, $xml);

    }

    function testSerializeSpecialPrincipals() {

        $dom = new \DOMDocument('1.0');
        $root = $dom->createElementNS('DAV:','d:root');

        $dom->appendChild($root);

        $privileges = array(
            array(
                'principal' => '{DAV:}authenticated',
                'privilege' => '{DAV:}write',
                'uri'       => 'articles',
            ),
            array(
                'principal' => '{DAV:}unauthenticated',
                'privilege' => '{DAV:}write',
                'uri'       => 'articles',
            ),
            array(
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}write',
                'uri'       => 'articles',
            ),

        );

        $acl = new Acl($privileges);
        $acl->serialize(new DAV\Server(), $root);

        $dom->formatOutput = true;

        $xml = $dom->saveXML();
        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:principal>
      <d:authenticated/>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:unauthenticated/>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:all/>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
</d:root>
';
        $this->assertEquals($expected, $xml);

    }

    function testUnserialize() {

        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:principal>
      <d:href>/principals/evert/</d:href>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:href>/principals/foo/</d:href>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:read/>
      </d:privilege>
    </d:grant>
    <d:protected/>
  </d:ace>
</d:root>
';

        $dom = DAV\XMLUtil::loadDOMDocument($source);
        $result = Acl::unserialize($dom->firstChild, array());

        $this->assertInstanceOf('Sabre\\DAVACL\\Property\\ACL', $result);

        $expected = array(
            array(
                'principal' => '/principals/evert/',
                'protected' => false,
                'privilege' => '{DAV:}write',
            ),
            array(
                'principal' => '/principals/foo/',
                'protected' => true,
                'privilege' => '{DAV:}read',
            ),
        );

        $this->assertEquals($expected, $result->getPrivileges());


    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testUnserializeNoPrincipal() {

        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
</d:root>
';

        $dom = DAV\XMLUtil::loadDOMDocument($source);
        Acl::unserialize($dom->firstChild, array());

    }

    function testUnserializeOtherPrincipal() {

        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
    <d:principal><d:authenticated /></d:principal>
  </d:ace>
  <d:ace>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
    <d:principal><d:unauthenticated /></d:principal>
  </d:ace>
  <d:ace>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
    <d:principal><d:all /></d:principal>
  </d:ace>
</d:root>
';

        $dom = DAV\XMLUtil::loadDOMDocument($source);
        $result = Acl::unserialize($dom->firstChild, array());

        $this->assertInstanceOf('Sabre\\DAVACL\\Property\\Acl', $result);

        $expected = array(
            array(
                'principal' => '{DAV:}authenticated',
                'protected' => false,
                'privilege' => '{DAV:}write',
            ),
            array(
                'principal' => '{DAV:}unauthenticated',
                'protected' => false,
                'privilege' => '{DAV:}write',
            ),
            array(
                'principal' => '{DAV:}all',
                'protected' => false,
                'privilege' => '{DAV:}write',
            ),
        );

        $this->assertEquals($expected, $result->getPrivileges());


    }

    /**
     * @expectedException Sabre\DAV\Exception\NotImplemented
     */
    function testUnserializeDeny() {

        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:deny>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:deny>
    <d:principal><d:href>/principals/evert</d:href></d:principal>
  </d:ace>
</d:root>
';

        $dom = DAV\XMLUtil::loadDOMDocument($source);
        Acl::unserialize($dom->firstChild, array());
    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testUnserializeMissingPriv() {

        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:grant>
      <d:privilege />
    </d:grant>
    <d:principal><d:href>/principals/evert</d:href></d:principal>
  </d:ace>
</d:root>
';

        $dom = DAV\XMLUtil::loadDOMDocument($source);
        Acl::unserialize($dom->firstChild, array());

    }
}
