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

namespace Ampache\Module\Api\OpenSubsonic;

use Ampache\Module\Api\OpenSubsonic_Json_Data;
use Ampache\Module\Api\OpenSubsonic_Xml_Data;
use PHPUnit\Framework\TestCase;

class OpenSubsonicResponseHandlerTest extends TestCase
{
    public function testJsonpOutputDropsACallbackThatIsNotAnIdentifier(): void
    {
        $body = json_encode(['ok' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->expectOutputString('jsonp(' . $body . ')');

        $this->createSubject()->jsonpOutput(['ok' => true], '</script><img src=x onerror=alert(1)>');
    }

    public function testJsonpOutputKeepsAPlainCallback(): void
    {
        $body = json_encode(['ok' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->expectOutputString('angular.callbacks._0(' . $body . ')');

        $this->createSubject()->jsonpOutput(['ok' => true], 'angular.callbacks._0');
    }

    private function createSubject(): OpenSubsonicResponseHandler
    {
        return new OpenSubsonicResponseHandler(
            $this->createMock(OpenSubsonic_Json_Data::class),
            $this->createMock(OpenSubsonic_Xml_Data::class),
        );
    }
}
