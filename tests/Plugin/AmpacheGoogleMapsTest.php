<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Plugin;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class AmpacheGoogleMapsTest extends TestCase
{
    private AmpacheGoogleMaps $plugin;

    /**
     * geo_name is a free-text field on the API handshake call, so a marker's title is attacker-reachable (CWE-79).
     */
    public function testDisplayMapEscapesAHostileGeoName(): void
    {
        $points = [[
            'latitude' => '48.853',
            'longitude' => '2.348',
            'name' => '</script><script>alert(document.cookie)</script>',
            'last_date' => 0,
            'hits' => 1,
        ]];

        ob_start();
        $this->plugin->display_map($points);
        $output = (string) ob_get_clean();

        $escapedOpenTag = (string) json_encode('<', JSON_HEX_TAG);

        $this->assertStringNotContainsString('</script><script>alert', $output);
        $this->assertStringContainsString(trim($escapedOpenTag, '"'), $output);
    }

    public function testDisplayMapEscapesAQuoteBreakout(): void
    {
        $points = [[
            'latitude' => '48.853',
            'longitude' => '2.348',
            'name' => '"});alert(1);({x:"',
            'last_date' => 0,
            'hits' => 1,
        ]];

        ob_start();
        $this->plugin->display_map($points);
        $output = (string) ob_get_clean();

        $this->assertStringNotContainsString('"});alert(1);({x:"', $output);
    }

    protected function setUp(): void
    {
        $this->plugin = new AmpacheGoogleMaps();

        (new ReflectionProperty(AmpacheGoogleMaps::class, 'api_key'))->setValue($this->plugin, 'some-key');
    }
}
