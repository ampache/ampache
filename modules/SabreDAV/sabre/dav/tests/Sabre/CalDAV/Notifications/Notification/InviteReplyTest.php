<?php

namespace Sabre\CalDAV\Notifications\Notification;

use Sabre\CalDAV;
use Sabre\DAV;

class InviteReplyTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider dataProvider
     */
    function testSerializers($notification, $expected) {

        $notification = new InviteReply($notification);

        $this->assertEquals('foo', $notification->getId());
        $this->assertEquals('"1"', $notification->getETag());

        $simpleExpected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<cs:root xmlns:cs="http://calendarserver.org/ns/"><cs:invite-reply/></cs:root>' . "\n";

        $dom = new \DOMDocument('1.0','UTF-8');
        $elem = $dom->createElement('cs:root');
        $elem->setAttribute('xmlns:cs',CalDAV\Plugin::NS_CALENDARSERVER);
        $dom->appendChild($elem);
        $notification->serialize(new DAV\Server(), $elem);
        $this->assertEquals($simpleExpected, $dom->saveXML());

        $dom = new \DOMDocument('1.0','UTF-8');
        $dom->formatOutput = true;
        $elem = $dom->createElement('cs:root');
        $elem->setAttribute('xmlns:cs',CalDAV\Plugin::NS_CALENDARSERVER);
        $elem->setAttribute('xmlns:d','DAV:');
        $dom->appendChild($elem);
        $notification->serializeBody(new DAV\Server(), $elem);
        $this->assertEquals($expected, $dom->saveXML());


    }

    function dataProvider() {

        $dtStamp = new \DateTime('2012-01-01 00:00:00 GMT');
        return array(
            array(
                array(
                    'id' => 'foo',
                    'dtStamp' => $dtStamp,
                    'etag' => '"1"',
                    'inReplyTo' => 'bar',
                    'href' => 'mailto:foo@example.org',
                    'type' => CalDAV\SharingPlugin::STATUS_ACCEPTED,
                    'hostUrl' => 'calendar'
                ),
<<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
  <cs:dtstamp>20120101T000000Z</cs:dtstamp>
  <cs:invite-reply>
    <cs:uid>foo</cs:uid>
    <cs:in-reply-to>bar</cs:in-reply-to>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-accepted/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
  </cs:invite-reply>
</cs:root>

FOO
            ),
            array(
                array(
                    'id' => 'foo',
                    'dtStamp' => $dtStamp,
                    'etag' => '"1"',
                    'inReplyTo' => 'bar',
                    'href' => 'mailto:foo@example.org',
                    'type' => CalDAV\SharingPlugin::STATUS_DECLINED,
                    'hostUrl' => 'calendar',
                    'summary' => 'Summary!'
                ),
<<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
  <cs:dtstamp>20120101T000000Z</cs:dtstamp>
  <cs:invite-reply>
    <cs:uid>foo</cs:uid>
    <cs:in-reply-to>bar</cs:in-reply-to>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-declined/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
    <cs:summary>Summary!</cs:summary>
  </cs:invite-reply>
</cs:root>

FOO
            ),

        );

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testMissingArg() {

        new InviteReply(array());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testUnknownArg() {

        new InviteReply(array(
            'foo-i-will-break' => true,

            'id' => 1,
            'etag' => '"bla"',
            'href' => 'abc',
            'dtStamp' => 'def',
            'inReplyTo' => 'qrs',
            'type' => 'ghi',
            'hostUrl' => 'jkl',
        ));

    }

}
