<?php

declare(strict_types=0);

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Application\Api\Upnp;

use Ampache\Application\ApplicationInterface;
use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Upnp_Api;

final class MediaServiceDescriptionApplication implements ApplicationInterface
{
    public function run(): void
    {
        if (!AmpConfig::get('upnp_backend')) {
            echo T_("Disabled");

            return;
        }

        header("Content-Type:text/xml");
        $web_path = AmpConfig::get('local_web_path');

        echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
        <root xmlns="urn:schemas-upnp-org:device-1-0">
        <specVersion>
            <major>1</major>
            <minor>0</minor>
        </specVersion>
        <device>
            <deviceType>urn:schemas-upnp-org:device:MediaServer:1</deviceType>
            <friendlyName><?php echo scrub_out(AmpConfig::get('site_title')); ?></friendlyName>
            <manufacturer>ampache.org</manufacturer>
            <manufacturerURL>http://ampache.org</manufacturerURL>
            <modelDescription>A web based audio/video streaming application and file manager allowing you to access your music and videos from anywhere, using almost any Internet enabled device.</modelDescription>
            <modelName>Ampache</modelName>
            <modelNumber><?php echo AmpConfig::get('version'); ?></modelNumber>
            <modelURL>http://ampache.org</modelURL>
            <UDN>uuid:<?php echo Upnp_Api::get_uuidStr(); ?></UDN>
            <iconList>
                <icon>
                    <mimetype>image/png</mimetype>
                    <width>32</width>
                    <height>32</height>
                    <depth>24</depth>
                    <url><?php echo $web_path; ?>/upnp/images/icon32.png</url>
                </icon>
                <icon>
                    <mimetype>image/png</mimetype>
                    <width>48</width>
                    <height>48</height>
                    <depth>24</depth>
                    <url><?php echo $web_path; ?>/upnp/images/icon48.png</url>
                </icon>
                <icon>
                    <mimetype>image/png</mimetype>
                    <width>120</width>
                    <height>120</height>
                    <depth>24</depth>
                    <url><?php echo $web_path; ?>/upnp/images/icon120.png</url>
                </icon>
                <icon>
                    <mimetype>image/jpeg</mimetype>
                    <width>32</width>
                    <height>32</height>
                    <depth>24</depth>
                    <url><?php echo $web_path; ?>/upnp/images/icon32.jpg</url>
                </icon>
                <icon>
                    <mimetype>image/jpeg</mimetype>
                    <width>48</width>
                    <height>48</height>
                    <depth>24</depth>
                    <url><?php echo $web_path; ?>/upnp/images/icon48.jpg</url>
                </icon>
                <icon>
                    <mimetype>image/jpeg</mimetype>
                    <width>120</width>
                    <height>120</height>
                    <depth>24</depth>
                    <url><?php echo $web_path; ?>/upnp/images/icon120.jpg</url>
                </icon>
            </iconList>
            <serviceList>
                <service>
                    <serviceType>urn:schemas-upnp-org:service:ContentDirectory:1</serviceType>
                    <serviceId>urn:upnp-org:serviceId:ContentDirectory</serviceId>
                    <controlURL><?php echo $web_path; ?>/upnp/control-reply.php</controlURL>
                    <eventSubURL><?php echo $web_path; ?>/upnp/event-reply.php</eventSubURL>
                    <SCPDURL><?php echo $web_path; ?>/upnp/MediaServerContentDirectory.xml</SCPDURL>
                </service>
                <service>
                    <serviceType>urn:schemas-upnp-org:service:ConnectionManager:1</serviceType>
                    <serviceId>urn:upnp-org:serviceId:ConnectionManager</serviceId>
                    <controlURL><?php echo $web_path; ?>/upnp/cm-control-reply.php</controlURL>
                    <eventSubURL><?php echo $web_path; ?>/upnp/cm-event-reply.php</eventSubURL>
                    <SCPDURL><?php echo $web_path; ?>/upnp/MediaServerConnectionManager.xml</SCPDURL>
                </service>
            </serviceList>
        </device>
        </root><?php
    }
}
