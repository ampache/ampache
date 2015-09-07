#!/usr/bin/env php
<?php

$windowsZonesUrl = 'http://unicode.org/repos/cldr/trunk/common/supplemental/windowsZones.xml';
$outputFile = __DIR__ . '/../lib/timezonedata/windowszones.php';

echo "Fetching timezone map from: " . $windowsZonesUrl, "\n";

$data = file_get_contents($windowsZonesUrl);

$xml = simplexml_load_string($data);

$map = array();

foreach($xml->xpath('//mapZone') as $mapZone) {

    $from = (string)$mapZone['other'];
    $to = (string)$mapZone['type'];

    list($to) = explode(' ', $to, 2);

    if (!isset($map[$from])) {
        $map[$from] = $to;
    }

}

ksort($map);
echo "Writing to: $outputFile\n";

$f = fopen($outputFile,'w');
fwrite($f, "<?php\n\n");
fwrite($f, "/**\n");
fwrite($f, " * Automatically generated timezone file\n");
fwrite($f, " *\n");
fwrite($f, " * Last update: " . date(DATE_W3C) . "\n");
fwrite($f, " * Source: " .$windowsZonesUrl . "\n");
fwrite($f, " *\n");
fwrite($f, " * @copyright Copyright (C) 2007-2014 fruux GmbH (https://fruux.com/).\n");
fwrite($f, " * @license http://sabre.io/license/ Modified BSD License\n");
fwrite($f, " */\n");
fwrite($f, "\n");
fwrite($f, "return ");
fwrite($f, var_export($map, true) . ';');
fclose($f);

echo "Done\n";
