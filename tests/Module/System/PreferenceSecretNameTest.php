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

namespace Ampache\Module\System;

use PHPUnit\Framework\TestCase;

/**
 * What counts as a secret decides three things at once: whether the field is masked, whether a blank
 * submit wipes it, and whether an export carries it. One list, or they drift.
 */
class PreferenceSecretNameTest extends TestCase
{
    public function testAnOrdinarySettingIsNotASecret(): void
    {
        foreach (['transcode', 'site_title', 'lastfm_grant_link', 'popular_threshold'] as $name) {
            $this->assertFalse(Preference::isSecretName($name), sprintf('`%s` is not a credential', $name));
        }
    }

    public function testEverySecretShippedByAmpacheStartsEmpty(): void
    {
        foreach (Preference::DEFAULTS as $name => $row) {
            if (Preference::isSecretName($name)) {
                $this->assertSame('', $row[0], sprintf('`%s` ships with a value, which would be a shared credential', $name));
            }
        }
    }

    public function testEverySuffixOnTheListIsRecognised(): void
    {
        $this->assertNotSame([], Preference::SECRET_SUFFIXES);

        foreach (Preference::SECRET_SUFFIXES as $suffix) {
            $this->assertTrue(
                Preference::isSecretName('something' . $suffix),
                sprintf('`%s` is on the list but not recognised', $suffix)
            );
        }
    }

    public function testTheNamesAmpacheActuallyShipsAreCovered(): void
    {
        foreach (['daap_pass', 'bitly_token', 'flickr_api_key', 'lastfm_challenge', 'librefm_challenge'] as $name) {
            $this->assertTrue(Preference::isSecretName($name), sprintf('`%s` holds a credential', $name));
        }
    }

    /**
     * A name secret on one side and not the other is wiped on the next save, so there is only one list
     */
    public function testTheUpdaterAsksTheSameQuestionRatherThanKeepingItsOwnList(): void
    {
        $source = (string) file_get_contents(__DIR__ . '/../../../src/Module/System/PreferencesFromRequestUpdater.php');

        $this->assertStringContainsString('Preference::isSecretName(', $source);

        foreach (Preference::SECRET_SUFFIXES as $suffix) {
            $this->assertStringNotContainsString(
                "'" . $suffix . "'",
                $source,
                sprintf('`%s` is spelled out here as well as on the list', $suffix)
            );
        }
    }
}
