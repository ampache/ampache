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
 * `PRESETS` names preferences that `DEFAULTS` has to create, and the two drift silently: a preset can
 * assign a value to a preference that does not exist.
 */
class PreferenceCatalogueConsistencyTest extends TestCase
{
    /**
     * Preferences a Localplay controller or a plugin installs, so `set_defaults()` never creates them
     *
     * @var list<string>
     */
    private const array INSTALLED_ELSEWHERE = ['mpd_active'];

    public function testEveryDeclaredTypeIsOneTheRendererKnows(): void
    {
        foreach (Preference::DEFAULTS as $name => $row) {
            self::assertContains(
                $row[3],
                ['boolean', 'integer', 'string', 'special', 'transcoding'],
                sprintf('`%s` declares the unknown type `%s`, which falls back to a text field', $name, $row[3])
            );
        }
    }

    public function testEveryPresetPreferenceExists(): void
    {
        foreach (array_diff($this->preset(), self::INSTALLED_ELSEWHERE) as $name) {
            self::assertArrayHasKey(
                $name,
                Preference::DEFAULTS,
                sprintf('the presets set `%s`, which does not exist', $name)
            );
        }
    }

    public function testTheDefaultPresetIsNotRepeatedAlongsideTheCatalogue(): void
    {
        // it used to be copied here and had drifted on eleven values; `set_preset()` derives it now
        self::assertArrayNotHasKey(
            'default',
            Preference::PRESETS,
            'the `default` preset is derived from `DEFAULTS`, so a copy here can only drift away from it'
        );
    }

    public function testTheExemptionListEarnsItsKeep(): void
    {
        foreach (self::INSTALLED_ELSEWHERE as $name) {
            self::assertArrayNotHasKey(
                $name,
                Preference::DEFAULTS,
                sprintf('`%s` now ships by default, so it no longer needs an exemption', $name)
            );
        }
    }

    /** @return list<string> */
    private function preset(): array
    {
        $names = [];
        foreach (Preference::PRESETS as $levels) {
            foreach ($levels as $group) {
                $names = [...$names, ...$group];
            }
        }

        return array_values(array_unique($names));
    }
}
