<?php

namespace Sabre\CalDAV;

use Sabre\DAVACL;

class ShareableCalendarTest extends \PHPUnit_Framework_TestCase {

    protected $backend;
    protected $instance;

    function setUp() {

        $props = array(
            'id' => 1,
        );

        $this->backend = new Backend\MockSharing(
            array($props)
        );
        $this->backend->updateShares(1, array(
            array(
                'href' => 'mailto:removeme@example.org',
                'commonName' => 'To be removed',
                'readOnly' => true,
            ),
        ), array());

        $this->instance = new ShareableCalendar($this->backend, $props);

    }

    function testUpdateShares() {

        $this->instance->updateShares(array(
            array(
                'href' => 'mailto:test@example.org',
                'commonName' => 'Foo Bar',
                'summary' => 'Booh',
                'readOnly' => false,
            ),
        ), array('mailto:removeme@example.org'));

        $this->assertEquals(array(array(
            'href' => 'mailto:test@example.org',
            'commonName' => 'Foo Bar',
            'summary' => 'Booh',
            'readOnly' => false,
            'status' => SharingPlugin::STATUS_NORESPONSE,
        )), $this->instance->getShares());

    }

    function testPublish() {

        $this->assertNull($this->instance->setPublishStatus(true));
        $this->assertNull($this->instance->setPublishStatus(false));

    }
}
