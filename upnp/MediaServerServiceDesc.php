<?php
define('NO_SESSION','1');
require_once '../lib/init.php';

if (!AmpConfig::get('upnp_backend')) {
    echo "Disabled.";
    exit;
}

header ("Content-Type:text/xml");
$web_path = AmpConfig::get('raw_web_path');
?>
<?xml version="1.0" encoding="UTF-8"?>
<root xmlns="urn:schemas-upnp-org:device-1-0">
    <specVersion>
        <major>1</major>
        <minor>0</minor>
    </specVersion>
    <device>
        <deviceType>urn:schemas-upnp-org:device:MediaServer:1</deviceType>
        <friendlyName>Ampache</friendlyName>
        <manufacturer>ampache.org</manufacturer>
        <manufacturerURL>http://ampache.org</manufacturerURL>
        <modelDescription>Ampache - For the love of music</modelDescription>
        <modelName>Ampache</modelName>
        <modelNumber><?php echo AmpConfig::get('version'); ?></modelNumber>
        <modelURL>http://ampache.org</modelURL>
        <UDN>uuid:<?php echo Upnp_Api::UUIDSTR; ?></UDN>
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
</root>
