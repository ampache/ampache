<?php

namespace Sabre\CardDAV;

use Sabre\DAV;

class PluginTest extends AbstractPluginTest {

    function testConstruct() {

        $this->assertEquals('card', $this->server->xmlNamespaces[Plugin::NS_CARDDAV]);
        $this->assertEquals('{' . Plugin::NS_CARDDAV . '}addressbook', $this->server->resourceTypeMapping['Sabre\\CardDAV\\IAddressBook']);

        $this->assertTrue(in_array('addressbook', $this->plugin->getFeatures()));

    }

    function testSupportedReportSet() {

        $this->assertEquals(array(
            '{' . Plugin::NS_CARDDAV . '}addressbook-multiget',
            '{' . Plugin::NS_CARDDAV . '}addressbook-query',
        ), $this->plugin->getSupportedReportSet('addressbooks/user1/book1'));

    }

    function testSupportedReportSetEmpty() {

        $this->assertEquals(array(
        ), $this->plugin->getSupportedReportSet(''));

    }

    function testAddressBookHomeSet() {

        $result = $this->server->getProperties('principals/user1', array('{' . Plugin::NS_CARDDAV . '}addressbook-home-set'));

        $this->assertEquals(1, count($result));
        $this->assertTrue(isset($result['{' . Plugin::NS_CARDDAV . '}addressbook-home-set']));
        $this->assertEquals('addressbooks/user1/', $result['{' . Plugin::NS_CARDDAV . '}addressbook-home-set']->getHref());

    }

    function testMeCardTest() {

        $result = $this->server->getProperties(
            'addressbooks/user1',
            array(
                '{http://calendarserver.org/ns/}me-card',
            )
        );

        $this->assertEquals(
            array(
                '{http://calendarserver.org/ns/}me-card' =>
                    new DAV\Property\Href('addressbooks/user1/book1/vcard1.vcf')
            ),
            $result
        );

    }

    function testDirectoryGateway() {

        $result = $this->server->getProperties('principals/user1', array('{' . Plugin::NS_CARDDAV . '}directory-gateway'));

        $this->assertEquals(1, count($result));
        $this->assertTrue(isset($result['{' . Plugin::NS_CARDDAV . '}directory-gateway']));
        $this->assertEquals(array('directory'), $result['{' . Plugin::NS_CARDDAV . '}directory-gateway']->getHrefs());

    }

    function testReportPassThrough() {

        $this->assertNull($this->plugin->report('{DAV:}foo', new \DomDocument()));

    }

    function testHTMLActionsPanel() {

        $output = '';
        $r = $this->server->emit('onHTMLActionsPanel', [$this->server->tree->getNodeForPath('addressbooks/user1'), &$output]);
        $this->assertFalse($r);

        $this->assertTrue(!!strpos($output,'Display name'));

    }

    function testBrowserPostAction() {

        $r = $this->server->emit('onBrowserPostAction', ['addressbooks/user1', 'mkaddressbook', [
            'name' => 'NEWADDRESSBOOK',
            '{DAV:}displayname' => 'foo',
        ]]);
        $this->assertFalse($r);

        $addressbooks = $this->backend->getAddressBooksforUser('principals/user1');
        $this->assertEquals(2, count($addressbooks));

        $newAddressBook = null;
        foreach($addressbooks as $addressbook) {
           if ($addressbook['uri'] === 'NEWADDRESSBOOK') {
                $newAddressBook = $addressbook;
                break;
           }
        }
        if (!$newAddressBook)
            $this->fail('Could not find newly created addressbook');

    }

    function testUpdatePropertiesMeCard() {

        $result = $this->server->updateProperties('addressbooks/user1', array(
            '{http://calendarserver.org/ns/}me-card' => new DAV\Property\Href('/addressbooks/user1/book1/vcard2',true),
        ));

        $this->assertEquals(
            array(
                '{http://calendarserver.org/ns/}me-card' => 200,
            ),
            $result
        );

    }

    function testUpdatePropertiesMeCardBadValue() {

        $result = $this->server->updateProperties('addressbooks/user1', array(
            '{http://calendarserver.org/ns/}me-card' => new DAV\Property\HrefList(array()),
        ));

        $this->assertEquals(
            array(
                '{http://calendarserver.org/ns/}me-card' => 400,
            ),
            $result
        );

    }

    function testAddressbookPluginProperties() {

        $ns = '{' . Plugin::NS_CARDDAV . '}';
        $propFind = new DAV\PropFind('addressbooks/user1/book1', [
            $ns . 'supported-address-data',
            $ns . 'supported-collation-set',
        ]);
        $node = $this->server->tree->getNodeForPath('addressbooks/user1/book1');
        $this->plugin->propFindEarly($propFind, $node);

        $this->assertInstanceOf(
            'Sabre\\CardDAV\\Property\\SupportedAddressData',
            $propFind->get($ns . 'supported-address-data')
        );
        $this->assertInstanceOf(
            'Sabre\\CardDAV\\Property\\SupportedCollationSet',
            $propFind->get($ns . 'supported-collation-set')
        );


    }

    function testGetTransform() {

        $request = new \Sabre\HTTP\Request('GET', '/addressbooks/user1/book1/card1', ['Accept: application/vcard+json']);
        $response = new \Sabre\HTTP\ResponseMock();
        $this->server->invokeMethod($request, $response);

        $this->assertEquals(200, $response->getStatus());

    }

}
