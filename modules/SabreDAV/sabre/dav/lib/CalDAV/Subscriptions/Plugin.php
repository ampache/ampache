<?php

namespace Sabre\CalDAV\Subscriptions;

use
    Sabre\DAV\INode,
    Sabre\DAV\PropFind,
    Sabre\DAV\ServerPlugin,
    Sabre\DAV\Server;

/**
 * This plugin adds calendar-subscription support to your CalDAV server.
 *
 * Some clients support 'managed subscriptions' server-side. This is basically
 * a list of subscription urls a user is using.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends ServerPlugin {

    /**
     * This initializes the plugin.
     *
     * This function is called by Sabre\DAV\Server, after
     * addPlugin is called.
     *
     * This method should set up the required event subscriptions.
     *
     * @param Server $server
     * @return void
     */
    function initialize(Server $server) {

        $server->resourceTypeMapping['Sabre\\CalDAV\\Subscriptions\\ISubscription'] =
            '{http://calendarserver.org/ns/}subscribed';

        $server->propertyMap['{http://calendarserver.org/ns/}source'] =
            'Sabre\\DAV\\Property\\Href';

        $server->on('propFind', [$this, 'propFind'], 150);

    }

    /**
     * This method should return a list of server-features.
     *
     * This is for example 'versioning' and is added to the DAV: header
     * in an OPTIONS response.
     *
     * @return array
     */
    function getFeatures() {

        return ['calendarserver-subscribed'];

    }

    /**
     * Triggered after properties have been fetched.
     *
     * @return void
     */
    function propFind(PropFind $propFind, INode $node) {

        // There's a bunch of properties that must appear as a self-closing
        // xml-element. This event handler ensures that this will be the case.
        $props = [
            '{http://calendarserver.org/ns/}subscribed-strip-alarms',
            '{http://calendarserver.org/ns/}subscribed-strip-attachments',
            '{http://calendarserver.org/ns/}subscribed-strip-todos',
        ];

        foreach($props as $prop) {

            if ($propFind->getStatus($prop)===200) {
                $propFind->set($prop, '', 200);
            }

        }

    }

}
