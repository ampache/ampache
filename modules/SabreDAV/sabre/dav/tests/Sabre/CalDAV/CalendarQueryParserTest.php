<?php

namespace Sabre\CalDAV;
use Sabre\DAV;

class CalendarQueryParserTest extends \PHPUnit_Framework_TestCase {

    function parse($xml) {

        $xml =
'<?xml version="1.0"?>
<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">
' . implode("\n", $xml) . '
</c:calendar-query>';

        $dom = DAV\XMLUtil::loadDOMDocument($xml);

        $q = new CalendarQueryParser($dom);
        $q->parse();
        return $q->filters;

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testNoFilter() {

        $xml = array();
        $this->parse($xml);

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testTwoCompFilter() {

        $xml = array(
            '<c:filter>',
            '  <c:comp-filter name="VEVENT" />',
            '  <c:comp-filter name="VEVENT" />',
            '</c:filter>'
        );
        $this->parse($xml);

    }

    function testBasicFilter() {

        $xml = array(
            '<c:filter>',
            '  <c:comp-filter name="VCALENDAR" />',
            '</c:filter>'
        );
        $result = $this->parse($xml);

        $expected = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => false
        );

        $this->assertEquals(
            $expected,
            $result
        );

    }

    function testCompIsNotDefined() {

        $xml = array(
            '<c:filter>',
            '  <c:comp-filter name="VCALENDAR">',
            '    <c:comp-filter name="VEVENT">',
            '       <c:is-not-defined/>',
            '    </c:comp-filter>',
            '  </c:comp-filter>',
            '</c:filter>'
        );
        $result = $this->parse($xml);

        $expected = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => true,
                    'time-range' => false
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => false
        );

        $this->assertEquals(
            $expected,
            $result
        );

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testCompTimeRangeOnVCALENDAR() {

        $xml = array(
            '<c:filter>',
            '  <c:comp-filter name="VCALENDAR">',
            '     <c:time-range start="20110101T000000Z" end="20111231T235959Z" />',
            '  </c:comp-filter>',
            '</c:filter>'
        );
        $result = $this->parse($xml);

    }

    function testCompTimeRange() {

        $xml = array(
            '<c:filter>',
            '  <c:comp-filter name="VCALENDAR">',
            '    <c:comp-filter name="VEVENT">',
            '       <c:time-range start="20110101T000000Z" end="20111231T235959Z" />',
            '    </c:comp-filter>',
            '    <c:comp-filter name="VTODO">',
            '       <c:time-range start="20110101T000000Z" />',
            '    </c:comp-filter>',
            '    <c:comp-filter name="VJOURNAL">',
            '       <c:time-range end="20111231T235959Z" />',
            '    </c:comp-filter>',
            '  </c:comp-filter>',
            '</c:filter>'
        );
        $result = $this->parse($xml);

        $expected = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => array(
                        'start' => new \DateTime('2011-01-01 00:00:00', new \DateTimeZone('GMT')),
                        'end' => new \DateTime('2011-12-31 23:59:59', new \DateTimeZone('GMT')),
                    ),
                ),
                array(
                    'name' => 'VTODO',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => array(
                        'start' => new \DateTime('2011-01-01 00:00:00', new \DateTimeZone('GMT')),
                        'end' => null,
                    ),
                ),
                array(
                    'name' => 'VJOURNAL',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => array(
                        'start' => null,
                        'end' => new \DateTime('2011-12-31 23:59:59', new \DateTimeZone('GMT')),
                    ),
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => false
        );

        $this->assertEquals(
            $expected,
            $result
        );

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testCompTimeRangeBadRange() {

        $xml = array(
            '<c:filter>',
            '  <c:comp-filter name="VCALENDAR">',
            '    <c:comp-filter name="VEVENT">',
            '       <c:time-range start="20110101T000000Z" end="20100101T000000Z" />',
            '    </c:comp-filter>',
            '  </c:comp-filter>',
            '</c:filter>'
        );
        $this->parse($xml);

    }

    function testProp() {

        $xml = array(
            '<c:filter>',
            '  <c:comp-filter name="VCALENDAR">',
            '    <c:comp-filter name="VEVENT">',
            '       <c:prop-filter name="SUMMARY">',
            '           <c:text-match>vacation</c:text-match>',
            '       </c:prop-filter>',
            '    </c:comp-filter>',
            '  </c:comp-filter>',
            '</c:filter>'
        );
        $result = $this->parse($xml);

        $expected = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'is-not-defined' => false,
                    'comp-filters' => array(),
                    'prop-filters' => array(
                        array(
                            'name' => 'SUMMARY',
                            'is-not-defined' => false,
                            'param-filters' => array(),
                            'text-match' => array(
                                'negate-condition' => false,
                                'collation' => 'i;ascii-casemap',
                                'value' => 'vacation',
                            ),
                            'time-range' => null,
                       ),
                    ),
                    'time-range' => null,
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => false
        );

        $this->assertEquals(
            $expected,
            $result
        );

    }

    function testComplex() {

        $xml = array(
            '<c:filter>',
            '  <c:comp-filter name="VCALENDAR">',
            '    <c:comp-filter name="VEVENT">',
            '       <c:prop-filter name="SUMMARY">',
            '           <c:text-match collation="i;unicode-casemap">vacation</c:text-match>',
            '       </c:prop-filter>',
            '       <c:prop-filter name="DTSTAMP">',
            '           <c:time-range start="20110704T000000Z" />',
            '       </c:prop-filter>',
            '       <c:prop-filter name="ORGANIZER">',
            '           <c:is-not-defined />',
            '       </c:prop-filter>',
            '       <c:prop-filter name="DTSTART">',
            '           <c:param-filter name="VALUE">',
            '               <c:text-match negate-condition="yes">DATE</c:text-match>',
            '           </c:param-filter>',
            '       </c:prop-filter>',
            '    </c:comp-filter>',
            '  </c:comp-filter>',
            '</c:filter>'
        );
        $result = $this->parse($xml);

        $expected = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'is-not-defined' => false,
                    'comp-filters' => array(),
                    'prop-filters' => array(
                        array(
                            'name' => 'SUMMARY',
                            'is-not-defined' => false,
                            'param-filters' => array(),
                            'text-match' => array(
                                'negate-condition' => false,
                                'collation' => 'i;unicode-casemap',
                                'value' => 'vacation',
                            ),
                            'time-range' => null,
                        ),
                        array(
                            'name' => 'DTSTAMP',
                            'is-not-defined' => false,
                            'param-filters' => array(),
                            'text-match' => null,
                            'time-range' => array(
                                'start' => new \DateTime('2011-07-04 00:00:00', new \DateTimeZone('GMT')),
                                'end' => null,
                            ),
                        ),
                        array(
                            'name' => 'ORGANIZER',
                            'is-not-defined' => true,
                            'param-filters' => array(),
                            'text-match' => null,
                            'time-range' => null,
                        ),
                        array(
                            'name' => 'DTSTART',
                            'is-not-defined' => false,
                            'param-filters' => array(
                                array(
                                    'name' => 'VALUE',
                                    'is-not-defined' => false,
                                    'text-match' => array(
                                        'negate-condition' => true,
                                        'value' => 'DATE',
                                        'collation' => 'i;ascii-casemap',
                                    ),
                                ),
                            ),
                            'text-match' => null,
                            'time-range' => null,
                        ),
                    ),
                    'time-range' => null,
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => false
        );

        $this->assertEquals(
            $expected,
            $result
        );

    }

    function testOther1() {

        // This body was exactly sent to us from the sabredav mailing list. Checking if this parses correctly.

        $body = <<<BLA
<?xml version="1.0" encoding="utf-8" ?>
<C:calendar-query xmlns:D="DAV:"
xmlns:C="urn:ietf:params:xml:ns:caldav">
 <D:prop>
   <C:calendar-data/>
   <D:getetag/>
 </D:prop>
 <C:filter>
   <C:comp-filter name="VCALENDAR">
     <C:comp-filter name="VEVENT">
       <C:time-range start="20090101T000000Z" end="20121202T000000Z"/>
     </C:comp-filter>
   </C:comp-filter>
 </C:filter>
</C:calendar-query>
BLA;

        $dom = DAV\XMLUtil::loadDOMDocument($body);

        $q = new CalendarQueryParser($dom);
        $q->parse();

        $this->assertEquals(array(
            '{urn:ietf:params:xml:ns:caldav}calendar-data',
            '{DAV:}getetag',
        ), $q->requestedProperties);

        $expectedFilters = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'time-range' => array(
                        'start' => new \DateTime('2009-01-01 00:00:00', new \DateTimeZone('UTC')),
                        'end' => new \DateTime('2012-12-02 00:00:00', new \DateTimeZone('UTC')),
                    ),
                    'is-not-defined' => false,
                ),
            ),
            'prop-filters' => array(),
            'time-range' => null,
            'is-not-defined' => false,
        );

        $this->assertEquals($expectedFilters, $q->filters);

    }

    function testExpand() {

        $xml = array(
            '<d:prop>',
            '  <c:calendar-data>',
            '     <c:expand start="20110101T000000Z" end="20120101T000000Z"/>',
            '  </c:calendar-data>',
            '</d:prop>',
            '<c:filter>',
            '  <c:comp-filter name="VCALENDAR" />',
            '</c:filter>'
        );

        $xml =
'<?xml version="1.0"?>
<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">
' . implode("\n", $xml) . '
</c:calendar-query>';

        $dom = DAV\XMLUtil::loadDOMDocument($xml);
        $q = new CalendarQueryParser($dom);
        $q->parse();


        $expected = array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => false
        );

        $this->assertEquals(
            $expected,
            $q->filters
        );

        $this->assertEquals(array(
            '{urn:ietf:params:xml:ns:caldav}calendar-data',
        ), $q->requestedProperties);

        $this->assertEquals(
            array(
                'start' => new \DateTime('2011-01-01 00:00:00', new \DateTimeZone('UTC')),
                'end' => new \DateTime('2012-01-01 00:00:00', new \DateTimeZone('UTC')),
            ),
            $q->expand
        );

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testExpandNoStart() {

        $xml = array(
            '<d:prop>',
            '  <c:calendar-data>',
            '     <c:expand end="20120101T000000Z"/>',
            '  </c:calendar-data>',
            '</d:prop>',
            '<c:filter>',
            '  <c:comp-filter name="VCALENDAR" />',
            '</c:filter>'
        );

        $xml =
'<?xml version="1.0"?>
<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">
' . implode("\n", $xml) . '
</c:calendar-query>';

        $dom = DAV\XMLUtil::loadDOMDocument($xml);
        $q = new CalendarQueryParser($dom);
        $q->parse();

    }
    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testExpandNoEnd() {

        $xml = array(
            '<d:prop>',
            '  <c:calendar-data>',
            '     <c:expand start="20120101T000000Z"/>',
            '  </c:calendar-data>',
            '</d:prop>',
            '<c:filter>',
            '  <c:comp-filter name="VCALENDAR" />',
            '</c:filter>'
        );

        $xml =
'<?xml version="1.0"?>
<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">
' . implode("\n", $xml) . '
</c:calendar-query>';

        $dom = DAV\XMLUtil::loadDOMDocument($xml);
        $q = new CalendarQueryParser($dom);
        $q->parse();

    }
    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testExpandBadTimes() {

        $xml = array(
            '<d:prop>',
            '  <c:calendar-data>',
            '     <c:expand start="20120101T000000Z" end="19980101T000000Z"/>',
            '  </c:calendar-data>',
            '</d:prop>',
            '<c:filter>',
            '  <c:comp-filter name="VCALENDAR" />',
            '</c:filter>'
        );

        $xml =
'<?xml version="1.0"?>
<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">
' . implode("\n", $xml) . '
</c:calendar-query>';

        $dom = DAV\XMLUtil::loadDOMDocument($xml);
        $q = new CalendarQueryParser($dom);
        $q->parse();

    }
}
