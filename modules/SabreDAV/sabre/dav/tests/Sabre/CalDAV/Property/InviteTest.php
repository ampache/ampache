<?php

namespace Sabre\CalDAV\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class InviteTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $sccs = new Invite(array());
        $this->assertInstanceOf('Sabre\CalDAV\Property\Invite', $sccs);

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new Invite(array(
            array(
                'href' => 'mailto:user1@example.org',
                'status' => CalDAV\SharingPlugin::STATUS_ACCEPTED,
                'readOnly' => false,
            ),
            array(
                'href' => 'mailto:user2@example.org',
                'commonName' => 'John Doe',
                'status' => CalDAV\SharingPlugin::STATUS_DECLINED,
                'readOnly' => true,
            ),
            array(
                'href' => 'mailto:user3@example.org',
                'commonName' => 'Joe Shmoe',
                'status' => CalDAV\SharingPlugin::STATUS_NORESPONSE,
                'readOnly' => true,
                'summary' => 'Something, something',
            ),
            array(
                'href' => 'mailto:user4@example.org',
                'commonName' => 'Hoe Boe',
                'status' => CalDAV\SharingPlugin::STATUS_INVALID,
                'readOnly' => true,
            ),
        ), array(
            'href' => 'mailto:thedoctor@example.org',
            'commonName' => 'The Doctor',
            'firstName' => 'The',
            'lastName' => 'Doctor',
        ));

        $doc = new \DOMDocument();
        $doc->formatOutput = true;
        $root = $doc->createElement('d:root');
        $root->setAttribute('xmlns:d','DAV:');
        $root->setAttribute('xmlns:cal',CalDAV\Plugin::NS_CALDAV);
        $root->setAttribute('xmlns:cs',CalDAV\Plugin::NS_CALENDARSERVER);

        $doc->appendChild($root);
        $server = new DAV\Server();

        $property->serialize($server, $root);

        $xml = $doc->saveXML();

        $this->assertEquals(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '" xmlns:cs="' . CalDAV\Plugin::NS_CALENDARSERVER . '">
  <cs:organizer>
    <d:href>mailto:thedoctor@example.org</d:href>
    <cs:common-name>The Doctor</cs:common-name>
    <cs:first-name>The</cs:first-name>
    <cs:last-name>Doctor</cs:last-name>
  </cs:organizer>
  <cs:user>
    <d:href>mailto:user1@example.org</d:href>
    <cs:invite-accepted/>
    <cs:access>
      <cs:read-write/>
    </cs:access>
  </cs:user>
  <cs:user>
    <d:href>mailto:user2@example.org</d:href>
    <cs:common-name>John Doe</cs:common-name>
    <cs:invite-declined/>
    <cs:access>
      <cs:read/>
    </cs:access>
  </cs:user>
  <cs:user>
    <d:href>mailto:user3@example.org</d:href>
    <cs:common-name>Joe Shmoe</cs:common-name>
    <cs:invite-noresponse/>
    <cs:access>
      <cs:read/>
    </cs:access>
    <cs:summary>Something, something</cs:summary>
  </cs:user>
  <cs:user>
    <d:href>mailto:user4@example.org</d:href>
    <cs:common-name>Hoe Boe</cs:common-name>
    <cs:invite-invalid/>
    <cs:access>
      <cs:read/>
    </cs:access>
  </cs:user>
</d:root>
', $xml);

    }

    /**
     * @depends testSerialize
     */
    public function testUnserialize() {

        $input = array(
            array(
                'href' => 'mailto:user1@example.org',
                'status' => CalDAV\SharingPlugin::STATUS_ACCEPTED,
                'readOnly' => false,
                'commonName' => '',
                'summary' => '',
            ),
            array(
                'href' => 'mailto:user2@example.org',
                'commonName' => 'John Doe',
                'status' => CalDAV\SharingPlugin::STATUS_DECLINED,
                'readOnly' => true,
                'summary' => '',
            ),
            array(
                'href' => 'mailto:user3@example.org',
                'commonName' => 'Joe Shmoe',
                'status' => CalDAV\SharingPlugin::STATUS_NORESPONSE,
                'readOnly' => true,
                'summary' => 'Something, something',
            ),
            array(
                'href' => 'mailto:user4@example.org',
                'commonName' => 'Hoe Boe',
                'status' => CalDAV\SharingPlugin::STATUS_INVALID,
                'readOnly' => true,
                'summary' => '',
            ),
        );

        // Creating the xml
        $doc = new \DOMDocument();
        $doc->formatOutput = true;
        $root = $doc->createElement('d:root');
        $root->setAttribute('xmlns:d','DAV:');
        $root->setAttribute('xmlns:cal',CalDAV\Plugin::NS_CALDAV);
        $root->setAttribute('xmlns:cs',CalDAV\Plugin::NS_CALENDARSERVER);

        $doc->appendChild($root);
        $server = new DAV\Server();

        $inputProperty = new Invite($input);
        $inputProperty->serialize($server, $root);

        $xml = $doc->saveXML();

        // Parsing it again

        $doc2 = DAV\XMLUtil::loadDOMDocument($xml);

        $outputProperty = Invite::unserialize($doc2->firstChild, array());

        $this->assertEquals($input, $outputProperty->getValue());

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    function testUnserializeNoStatus() {

$xml = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '" xmlns:cs="' . CalDAV\Plugin::NS_CALENDARSERVER . '">
  <cs:user>
    <d:href>mailto:user1@example.org</d:href>
    <!-- <cs:invite-accepted/> -->
    <cs:access>
      <cs:read-write/>
    </cs:access>
  </cs:user>
</d:root>';

        $doc2 = DAV\XMLUtil::loadDOMDocument($xml);
        $outputProperty = Invite::unserialize($doc2->firstChild, array());

    }

}
