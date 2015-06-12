<?php

namespace Sabre\CardDAV;

require_once 'Sabre/CardDAV/AbstractPluginTest.php';

class ValidateFilterTest extends AbstractPluginTest {

    /**
     * @dataProvider data
     */
    function testFilter($input, $filters, $test, $result, $message = null) {

        if ($result) {
            $this->assertTrue($this->plugin->validateFilters($input, $filters, $test), $message);
        } else {
            $this->assertFalse($this->plugin->validateFilters($input, $filters, $test), $message);
        }

    }

    function data() {

        $body1 = <<<HELLO
BEGIN:VCARD
VERSION:3.0
ORG:Company;
TITLE:Title
TEL;TYPE=IPHONE;TYPE=pref:(222) 22 22 22
TEL;TYPE=HOME:(33) 333 66 66
TEL;TYPE=WORK:(444) 44 44 44
TEL;TYPE=MAIN:(55) 555 55 55
ITEM4.TEL:(111) 11 11 11
ITEM5.TEL:(6) 66 66 66 66
ITEM6.TEL:(77) 777 77 77
UID:3151DE6A-BC35-4612-B340-B53A034A2B27
ITEM1.EMAIL:1111@111.com
ITEM2.EMAIL:bbbbb@bbbb.com
ITEM3.EMAIL:ccccc@ccccc.com
FN:First Last
N:Last;First;Middle;Dr
BDAY:1985-07-20
ADR;TYPE=HOME:;;Street;City;;3556;Montenegro
ADR;TYPE=WORK:;;Street\\nStreet2;Harkema;;35444;Australia
URL:http://google.com
END:VCARD
HELLO;

        // Check if TITLE is defined
        $filter1 =
            array('name' => 'title', 'is-not-defined' => false, 'param-filters' => array(), 'text-matches' => array());

        // Check if FOO is defined
        $filter2 =
            array('name' => 'foo', 'is-not-defined' => false, 'param-filters' => array(), 'text-matches' => array());

        // Check if TITLE is not defined
        $filter3 =
            array('name' => 'title', 'is-not-defined' => true, 'param-filters' => array(), 'text-matches' => array());

        // Check if FOO is not defined
        $filter4 =
            array('name' => 'foo', 'is-not-defined' => true, 'param-filters' => array(), 'text-matches' => array());

        // Check if TEL[TYPE] is defined
        $filter5 =
            array(
                'name' => 'tel',
                'is-not-defined' => false,
                'test' => 'anyof',
                'param-filters' => array(
                    array(
                        'name' => 'type',
                        'is-not-defined' => false,
                        'text-match' => null
                    ),
                ),
                'text-matches' => array(),
            );

        // Check if TEL[FOO] is defined
        $filter6 = $filter5;
        $filter6['param-filters'][0]['name'] = 'FOO';

        // Check if TEL[TYPE] is not defined
        $filter7 = $filter5;
        $filter7['param-filters'][0]['is-not-defined'] = true;

        // Check if TEL[FOO] is not defined
        $filter8 = $filter5;
        $filter8['param-filters'][0]['name'] = 'FOO';
        $filter8['param-filters'][0]['is-not-defined'] = true;

        // Combining property filters
        $filter9 = $filter5;
        $filter9['param-filters'][] = $filter6['param-filters'][0];

        $filter10 = $filter5;
        $filter10['param-filters'][] = $filter6['param-filters'][0];
        $filter10['test'] = 'allof';

        // Check if URL contains 'google'
        $filter11 =
            array(
                'name' => 'url',
                'is-not-defined' => false,
                'test' => 'anyof',
                'param-filters' => array(),
                'text-matches' => array(
                    array(
                        'match-type' => 'contains',
                        'value' => 'google',
                        'negate-condition' => false,
                        'collation' => 'i;octet',
                    ),
                ),
            );

        // Check if URL contains 'bing'
        $filter12 = $filter11;
        $filter12['text-matches'][0]['value'] = 'bing';

        // Check if URL does not contain 'google'
        $filter13 = $filter11;
        $filter13['text-matches'][0]['negate-condition'] = true;

        // Check if URL does not contain 'bing'
        $filter14 = $filter11;
        $filter14['text-matches'][0]['value'] = 'bing';
        $filter14['text-matches'][0]['negate-condition'] = true;

        // Param filter with text
        $filter15 = $filter5;
        $filter15['param-filters'][0]['text-match'] = array(
            'match-type' => 'contains',
            'value' => 'WORK',
            'collation' => 'i;octet',
            'negate-condition' => false,
        );
        $filter16 = $filter15;
        $filter16['param-filters'][0]['text-match']['negate-condition'] = true;


        // Param filter + text filter
        $filter17 = $filter5;
        $filter17['test'] = 'anyof';
        $filter17['text-matches'][] = array(
            'match-type' => 'contains',
            'value' => '444',
            'collation' => 'i;octet',
            'negate-condition' => false,
        );

        $filter18 = $filter17;
        $filter18['text-matches'][0]['negate-condition'] = true;

        $filter18['test'] = 'allof';

        return array(

            // Basic filters
            array($body1, array($filter1), 'anyof',true),
            array($body1, array($filter2), 'anyof',false),
            array($body1, array($filter3), 'anyof',false),
            array($body1, array($filter4), 'anyof',true),

            // Combinations
            array($body1, array($filter1, $filter2), 'anyof',true),
            array($body1, array($filter1, $filter2), 'allof',false),
            array($body1, array($filter1, $filter4), 'anyof',true),
            array($body1, array($filter1, $filter4), 'allof',true),
            array($body1, array($filter2, $filter3), 'anyof',false),
            array($body1, array($filter2, $filter3), 'allof',false),

            // Basic parameters
            array($body1, array($filter5), 'anyof', true, 'TEL;TYPE is defined, so this should return true'),
            array($body1, array($filter6), 'anyof', false, 'TEL;FOO is not defined, so this should return false'),

            array($body1, array($filter7), 'anyof', false, 'TEL;TYPE is defined, so this should return false'),
            array($body1, array($filter8), 'anyof', true, 'TEL;TYPE is not defined, so this should return true'),

            // Combined parameters
            array($body1, array($filter9), 'anyof', true),
            array($body1, array($filter10), 'anyof', false),

            // Text-filters
            array($body1, array($filter11), 'anyof', true),
            array($body1, array($filter12), 'anyof', false),
            array($body1, array($filter13), 'anyof', false),
            array($body1, array($filter14), 'anyof', true),

            // Param filter with text-match
            array($body1, array($filter15), 'anyof', true),
            array($body1, array($filter16), 'anyof', false),

            // Param filter + text filter
            array($body1, array($filter17), 'anyof', true),
            array($body1, array($filter18), 'anyof', false),
            array($body1, array($filter18), 'anyof', false),
        );

    }

}
